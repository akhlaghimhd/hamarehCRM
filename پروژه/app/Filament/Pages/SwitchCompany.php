<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

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

    /** @var array<int, string> */
    public array $companyOptions = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $this->companyOptions = $user->companies()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->name])
            ->all();

        $this->company_id = (int) (session('current_company_id') ?? $user->currentCompanyId());
    }

    public function switch(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $companyId = (int) $this->company_id;

        $allowed = $user->companies()->where('companies.id', $companyId)->exists();

        if (! $allowed) {
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

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
