<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use BelongsToCompany;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'type',
        'display_name',
        'first_name',
        'last_name',
        'national_id',
        'organization_name',
        'brand_name',
        'national_id_org',
        'economic_code',
        'registration_number',
        'mobile',
        'phone',
        'email',
        'province',
        'city',
        'address',
        'postal_code',
        'owner_user_id',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }
}
