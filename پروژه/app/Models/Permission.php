<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    public const SYSTEM_GROUPS = ['سیستم', 'system'];

    public const SYSTEM_NAMES = ['roles.manage'];

    protected $fillable = [
        'name',
        'label',
        'group',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
}
