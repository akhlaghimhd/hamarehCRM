<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SwitchCompany extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'تعویض شرکت';

    protected static ?string $title = 'تعویض شرکت جاری';

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
            return ['companyOptions' => [], 'optionsCount' => 0];
        }

        $options = DB::table('company_user')
            ->join('companies', 'companies.id', '=', 'company_user.company_id')
            ->where('company_user.user_id', $user->id)
            ->whereNull('companies.deleted_at')
            ->orderBy('companies.name')
            ->get([
                'companies.id as id',
                'companies.name as name',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
            ])
            ->values()
            ->all();

        return [
            'companyOptions' => $options,
            'optionsCount' => count($options),
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

        Notification::make()
            ->title('شرکت تغییر کرد')
            ->body('شرکت جاری به‌روز شد.')
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

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
