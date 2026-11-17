<?php

namespace App\Filament\Pages;

use App\Actions\Identity\ActivateCompanyForMember;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SwitchCompany extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'تعویض شرکت';

    protected static ?string $title = 'تعویض شرکت';

    protected static ?string $slug = 'switch-company';

    protected static ?int $navigationSort = 100;

    /**
     * @var view-string
     */
    protected string $view = 'filament.pages.switch-company';

    public ?int $company_id = null;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->redirect(Dashboard::getUrl());

            return;
        }

        $companyContext = app(CompanyContext::class);
        $currentCompanyId = $companyContext->currentCompanyId($user);
        $activeMembershipCount = $this->membershipCount((int) $user->id);
        $canRecoverCompany = app(ActivateCompanyForMember::class)->hasRecoverableCompany($user);

        if (
            ($activeMembershipCount === 0 && ! $canRecoverCompany)
            || ($currentCompanyId !== null && $activeMembershipCount < 2 && ! $canRecoverCompany)
        ) {
            $this->redirect(Dashboard::getUrl());

            return;
        }

        $this->company_id = $currentCompanyId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->emptyViewData();
        }

        $memberships = DB::table('company_user as cu')
            ->join('companies as c', 'c.id', '=', 'cu.company_id')
            ->where('cu.user_id', $user->id)
            ->orderBy('c.name')
            ->get([
                'cu.id as membership_id',
                'cu.role_id',
                'cu.is_default',
                'c.id',
                'c.name',
                'c.slug',
                'c.national_id',
                'c.phone',
                'c.email',
                'c.is_active',
                'c.deleted_at',
            ]);

        $currentId = app(CompanyContext::class)->currentCompanyId($user);

        $companyOptions = $memberships->map(function ($row) use ($user, $currentId): array {
            $roleIds = DB::table('company_user_role')
                ->where('company_user_id', $row->membership_id)
                ->orderBy('id')
                ->pluck('role_id');

            if ($roleIds->isEmpty() && $row->role_id !== null) {
                $roleIds = collect([(int) $row->role_id]);
            }

            $roles = Role::query()
                ->where('company_id', $row->id)
                ->whereIn('id', $roleIds)
                ->get(['name', 'label']);

            $roleLabel = $roles
                ->map(fn (Role $role): string => (string) ($role->label ?: $role->name))
                ->filter()
                ->implode('، ');

            return [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) ($row->slug ?? ''),
                'national_id' => (string) ($row->national_id ?: '—'),
                'phone' => (string) ($row->phone ?: ''),
                'email' => (string) ($row->email ?: '—'),
                'role_label' => $roleLabel !== '' ? $roleLabel : '—',
                'is_default' => (bool) $row->is_default,
                'is_active' => (bool) $row->is_active,
                'is_deleted' => $row->deleted_at !== null,
                'is_current' => (int) $row->id === $currentId,
                'can_activate' => app(ActivateCompanyForMember::class)->canActivate($user, (int) $row->id),
            ];
        })->values();

        $current = $currentId === null ? null : $companyOptions->firstWhere('id', $currentId);

        return [
            'userMeta' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'username' => (string) ($user->username ?? '—'),
                'email' => (string) ($user->email ?? '—'),
            ],
            'currentCompany' => $current,
            'companyOptions' => $companyOptions->all(),
        ];
    }

    public function switch(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(CompanyContext::class)->selectForUser($user, (int) $this->company_id);
        } catch (RuntimeException) {
            Notification::make()
                ->title('عدم دسترسی')
                ->body('شرکت انتخاب‌شده فعال نیست یا شما عضو آن نیستید.')
                ->danger()
                ->send();

            return;
        }

        $name = DB::table('companies')
            ->where('id', $this->company_id)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->value('name') ?? 'شرکت';

        Notification::make()
            ->title('شرکت جاری تغییر کرد')
            ->body($name)
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl());
    }

    public function activateCompany(int $companyId): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        try {
            app(ActivateCompanyForMember::class)->handle($companyId, $user);
        } catch (ValidationException | RuntimeException) {
            Notification::make()
                ->title('فعال‌سازی انجام نشد')
                ->body('فقط مدیر همان شرکت می‌تواند شرکت عضو‌شده را دوباره فعال کند.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('شرکت دوباره فعال شد')
            ->body('اکنون وارد فضای کاری این شرکت شده‌اید.')
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl());
    }

    private function membershipCount(int $userId): int
    {
        return (int) DB::table('company_user as cu')
            ->join('companies as c', 'c.id', '=', 'cu.company_id')
            ->where('cu.user_id', $userId)
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->count();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyViewData(): array
    {
        return [
            'userMeta' => null,
            'currentCompany' => null,
            'companyOptions' => [],
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User || ! (bool) $user->is_active) {
            return false;
        }

        $activeMembershipCount = DB::table('company_user as cu')
            ->join('companies as c', 'c.id', '=', 'cu.company_id')
            ->where('cu.user_id', $user->id)
            ->where('c.is_active', true)
            ->whereNull('c.deleted_at')
            ->count();

        $currentCompanyId = app(CompanyContext::class)->currentCompanyId($user);

        return $activeMembershipCount > 1
            || ($currentCompanyId === null && $activeMembershipCount > 0)
            || app(ActivateCompanyForMember::class)->hasRecoverableCompany($user);
    }
}
