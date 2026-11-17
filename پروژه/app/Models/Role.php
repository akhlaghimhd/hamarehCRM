<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Role extends Model
{
    protected $fillable = [
        'company_id',
        'parent_role_id',
        'inherit_permissions',
        'name',
        'label',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'inherit_permissions' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_role_id');
    }

    public function childRoles(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(self::class, 'parent_role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * Return direct and inherited permission IDs without traversing cycles
     * or crossing company boundaries.
     *
     * @return Collection<int, int>
     */
    public function effectivePermissionIds(): Collection
    {
        $permissionIds = collect();
        $visited = [];
        $role = $this;

        while ($role && ! in_array((int) $role->id, $visited, true)) {
            $visited[] = (int) $role->id;

            if ($role->company_id !== $this->company_id) {
                break;
            }

            if ($this->name !== 'admin' && $role->name === 'admin') {
                break;
            }

            $permissionQuery = $role->permissions();

            if ($this->name !== 'admin') {
                $permissionQuery
                    ->whereNotIn('permissions.group', Permission::SYSTEM_GROUPS)
                    ->whereNotIn('permissions.name', Permission::SYSTEM_NAMES);
            }

            $permissionIds = $permissionIds->merge(
                $permissionQuery->pluck('permissions.id')
            );

            if (! $role->inherit_permissions || ! $role->parent_role_id) {
                break;
            }

            $role = self::query()
                ->where('company_id', $this->company_id)
                ->find($role->parent_role_id);
        }

        return $permissionIds->unique()->values();
    }
}
