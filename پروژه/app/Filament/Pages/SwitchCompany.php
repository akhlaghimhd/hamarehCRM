<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        if (! $user) {
            return;
        }

        // تک‌شرکتی: اصلاً این صفحه معنا ندارد
        if ($this->membershipCount((int) $user->id) < 2) {
            $this->redirect(Dashboard::getUrl());

            return;
        }

        $allowedIds = $this->allowedCompanyIds((int) $user->id);
        $current = (int) (session('current_company_id') ?? 0);

        $this->company_id = in_array($current, $allowedIds, true)
            ? $current
            : ($allowedIds[0] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::user();

        if (! $user) {
            return $this->emptyViewData();
        }

        $memberships = DB::table('company_user as cu')
            ->join('companies as c', 'c.id', '=', 'cu.company_id')
            ->leftJoin('roles as r', 'r.id', '=', 'cu.role_id')
            ->where('cu.user_id', $user->id)
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.name',
                'c.slug',
                'c.national_id',
                'c.phone',
                'c.email',
                'c.is_active',
                'cu.is_default',
                'r.label as role_label',
                'r.name as role_name',
            ]);

        $currentId = (int) (session('current_company_id') ?? $this->company_id ?? 0);
        $current = $memberships->firstWhere('id', $currentId) ?? $memberships->first();

        return [
            'userMeta' => [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'username' => (string) ($user->username ?? '—'),
                'email' => (string) ($user->email ?? '—'),
            ],
            'currentCompany' => $current ? [
                'id' => (int) $current->id,
                'name' => (string) $current->name,
                'slug' => (string) ($current->slug ?? '—'),
                'national_id' => (string) ($current->national_id ?: '—'),
                'phone' => (string) ($current->phone ?: '—'),
                'email' => (string) ($current->email ?: '—'),
                'is_active' => (bool) $current->is_active,
                'role_label' => (string) ($current->role_label ?: $current->role_name ?: '—'),
            ] : null,
            'companyOptions' => $memberships->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) ($row->slug ?? ''),
                'phone' => (string) ($row->phone ?: ''),
                'role_label' => (string) ($row->role_label ?: $row->role_name ?: '—'),
                'is_default' => (bool) $row->is_default,
                'is_active' => (bool) $row->is_active,
                'is_current' => (int) $row->id === $currentId,
            ])->values()->all(),
        ];
    }

    public function switch(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $companyId = (int) $this->company_id;
        $allowedIds = $this->allowedCompanyIds((int) $user->id);

        if (! in_array($companyId, $allowedIds, true)) {
            Notification::make()
                ->title('عدم دسترسی')
                ->body('شما به این شرکت دسترسی ندارید.')
                ->danger()
                ->send();

            return;
        }

        session(['current_company_id' => $companyId]);

        $name = DB::table('companies')->where('id', $companyId)->value('name') ?? 'شرکت';

        Notification::make()
            ->title('شرکت جاری تغییر کرد')
            ->body($name)
            ->success()
            ->send();

        $this->redirect(Dashboard::getUrl());
    }

    /**
     * @return list<int>
     */
    private function allowedCompanyIds(int $userId): array
    {
        return DB::table('company_user')
            ->where('user_id', $userId)
            ->pluck('company_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function membershipCount(int $userId): int
    {
        return (int) DB::table('company_user')->where('user_id', $userId)->count();
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

        if (! $user) {
            return false;
        }

        return DB::table('company_user')->where('user_id', $user->id)->count() > 1;
    }
}
