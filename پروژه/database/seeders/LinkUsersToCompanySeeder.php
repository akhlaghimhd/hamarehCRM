<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class LinkUsersToCompanySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->first();
        $role = Role::query()->where('name', 'admin')->first();

        if (! $company || ! $role) {
            $this->command?->error('company or admin role missing');
            return;
        }

        foreach (User::query()->get() as $user) {
            if (empty($user->username)) {
                $user->username = 'user' . $user->id;
            }
            $user->is_active = true;
            $user->save();

            $user->companies()->syncWithoutDetaching([
                $company->id => [
                    'role_id' => $role->id,
                    'is_default' => true,
                ],
            ]);

            $this->command?->info("linked user {$user->id} ({$user->email})");
        }
    }
}
