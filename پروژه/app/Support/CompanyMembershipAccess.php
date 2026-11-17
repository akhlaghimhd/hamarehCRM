<?php

namespace App\Support;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyMembershipAccess
{
    public function isMember(?User $user, int $companyId): bool
    {
        return $user !== null
            && $user->is_active
            && DB::table('company_user')
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->exists();
    }

    public function isAdmin(?User $user, int $companyId): bool
    {
        if (! $this->isMember($user, $companyId)) {
            return false;
        }

        $membership = DB::table('company_user')
            ->where('user_id', $user->id)
            ->where('company_id', $companyId)
            ->first(['id', 'role_id']);

        if (! $membership) {
            return false;
        }

        $roleIds = DB::table('company_user_role')
            ->where('company_user_id', $membership->id)
            ->pluck('role_id');

        if ($roleIds->isEmpty() && $membership->role_id !== null) {
            $roleIds = collect([(int) $membership->role_id]);
        }

        return $roleIds->isNotEmpty()
            && Role::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $roleIds)
                ->where('name', 'admin')
                ->exists();
    }
}
