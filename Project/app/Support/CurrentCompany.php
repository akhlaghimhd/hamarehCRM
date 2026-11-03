<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Session;

class CurrentCompany
{
    public const SESSION_KEY = 'current_company_id';

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_KEY);

        return $id ? (int) $id : null;
    }

    public static function get(): ?Company
    {
        $id = self::id();

        return $id ? Company::query()->find($id) : null;
    }

    public static function set(Company|int|null $company): void
    {
        if ($company === null) {
            Session::forget(self::SESSION_KEY);

            return;
        }

        $id = $company instanceof Company ? $company->id : $company;
        Session::put(self::SESSION_KEY, $id);
    }

    public static function ensureFor(User $user): ?Company
    {
        $currentId = self::id();

        if ($currentId && $user->companies()->where('companies.id', $currentId)->exists()) {
            return Company::query()->find($currentId);
        }

        $default = $user->companies()
            ->orderByDesc('company_user.is_default')
            ->orderBy('companies.id')
            ->first();

        if ($default) {
            self::set($default);
        }

        return $default;
    }
}
