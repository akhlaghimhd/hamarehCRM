<?php

namespace App\Enums;

enum CustomerType: string
{
    case Individual = 'individual';
    case Organization = 'organization';

    public function label(): string
    {
        return match ($this) {
            self::Individual => 'حقیقی',
            self::Organization => 'حقوقی',
        };
    }
}
