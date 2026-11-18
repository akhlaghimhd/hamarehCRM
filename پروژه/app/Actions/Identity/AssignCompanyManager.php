<?php

namespace App\\Actions\\Identity;

use App\\Models\\Company;
use App\\Models\\Permission;
use App\\Models\\Role;
use App\\Models\\User;
use App\\Support\\CompanyMembershipAccess;
use Illuminate\\Auth\\Access\\AuthorizationException;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Validator;

class AssignCompanyManager
{
    /**
     * Assign an active existing account or create a new account as the company's administrator.
     *
     * @param array<string, mixed> $data
     */
    public function handle(Company $record, array $data, User $actor): User
    {
        if (! $actor->is_active || (! $actor->isPlatformOwner() && ! app(CompanyMembershipAccess::class)->canManage($actor, (int) $record->id))) {
            throw new AuthorizationException('اجازه تعیین مدیر این شرکت را ندارید.');
        }

        $mode = $data['mode'] ?? 'existing';

        return DB::transaction(function () use ($record, $data, $actor, $mode): User {
            $company = Company::query()->whereKey($record->id)->lockForUpdate()->firstOrFail();

            if (! $company->is_active) {
                throw new AuthorizationException('شرکت غیرفعال است.');
            }

            if (! $actor->isPlatformOwner() && ! app(CompanyMembershipAccess::class)->canManage($actor, (int) $company->id)) {
                throw new AuthorizationException('دسترسی مدیریت شرکت در همین فاصله لغو شده است.');
            }

            if ($mode === 'new') {
                $validated = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'username' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:users,username'],
                    'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                    'password' => ['required', 'string', 'min:8', 'same:password_confirmation'],
                    'password_confirmation' => ['required', 'string'],
                ], [
                    'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
                    'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
                    'password.min' => 'گذرواژه باید حداقل ۸ نویسه باشد.',
                    'password.same' => 'تکرار گذرواژه با گذرواژه یکسان نیست.',
                ])->validate();

                $user = User::query()->create([
                    'name' => $validated['name'],
                    'username' => $validated['username'],
                    'email' => $validated['email'],
                    'password' => $validated['password'],
                    'is_active' => true,
                ]);
            } elseif ($mode === 'existing') {
                $user = User::query()
                    ->whereKey($data['user_id'] ?? 0)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();
            } else {
                throw new \\Illuminate\\Validation\\ValidationException(
                    Validator::make([], ['mode' => ['required', 'in:existing,new']])->errors()
                );
            }

            $role = Role::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => 'admin'],
                ['label' => 'مدیر شرکت', 'is_system' => true, 'inherit_permissions' => false],
            );

            if ($role->wasRecentlyCreated) {
                $role->permissions()->sync(Permission::query()->pluck('id'));
            }

            $membership = DB::table('company_user')
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $oldValues = $membership ? [
                'role_id' => $membership->role_id,
                'manager_user_id' => $membership->manager_user_id,
            ] : [];

            if ($membership) {
                DB::table('company_user')->where('id', $membership->id)->update([
                    'role_id' => $role->id,
                    'updated_at' => now(),
                ]);
                DB::table('company_user_role')->where('company_user_id', $membership->id)->delete();
                DB::table('company_user_role')->insert([
                    'company_user_id' => $membership->id,
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $membershipId = DB::table('company_user')->insertGetId([
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'manager_user_id' => null,
                    'is_default' => ! DB::table('company_user')->where('user_id', $user->id)->where('is_default', true)->exists(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('company_user_role')->insert([
                    'company_user_id' => $membershipId,
                    'role_id' => $role->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('audit_events')->insert([
                'company_id' => $company->id,
                'auditable_type' => $user->getMorphClass(),
                'auditable_id' => $user->id,
                'actor_user_id' => $actor->id,
                'event' => 'company_manager_assigned',
                'old_values' => json_encode($oldValues, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode(['user_id' => $user->id, 'role_id' => $role->id], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'metadata' => json_encode(['source' => 'company_row_action'], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user->refresh();
        });
    }
}
