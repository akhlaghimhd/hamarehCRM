<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use App\Models\User;
use App\Support\CompanyContext;
use App\Support\CompanySettings as CompanySettingsService;
use App\Support\CompanyTheme;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CompanySettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'تنظیمات شرکت';

    protected static string|UnitEnum|null $navigationGroup = 'سازمان';

    protected static ?int $navigationSort = 90;

    protected static ?string $title = 'تنظیمات شرکت';

    protected string $view = 'filament.pages.company-settings';

    /** @var array<string, mixed> */
    public array $data = [];

    public string $themePalette = 'ocean';

    public string $themePrimary = '#0f766e';

    public string $themeSecondary = '#0369a1';

    public ?int $editingSettingId = null;

    public string $editorKey = '';

    public string $editorValue = '';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->is_active) {
            return false;
        }

        // Project permission API (not Spatie). company.settings is not seeded;
        // companies.manage / company admin / platform owner may open settings.
        return $user->isPlatformOwner()
            || $user->isCompanyAdmin()
            || $user->hasPermission('companies.manage');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $this->authorizeAdmin();

        $companyId = app(CompanyContext::class)->id();
        abort_if($companyId === null, 403);

        $settings = CompanySettingsService::forCompany($companyId);
        $theme = app(CompanyTheme::class);

        $this->themePalette = (string) ($settings['theme_palette'] ?? 'ocean');
        $this->themePrimary = (string) ($settings['theme_primary'] ?? $theme->primary());
        $this->themeSecondary = (string) ($settings['theme_secondary'] ?? $theme->secondary());

        $this->form->fill([
            'timezone' => $settings['timezone'] ?? config('app.timezone'),
            'locale' => $settings['locale'] ?? 'fa',
            'currency' => $settings['currency'] ?? 'IRR',
            'date_format' => $settings['date_format'] ?? 'Y/m/d',
            'theme_density' => $settings['theme_density'] ?? 'comfortable',
            'notifications_email' => (bool) ($settings['notifications_email'] ?? true),
            'notifications_sms' => (bool) ($settings['notifications_sms'] ?? false),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('عمومی')
                    ->schema([
                        Select::make('timezone')
                            ->label('منطقه زمانی')
                            ->options([
                                'Asia/Tehran' => 'تهران',
                                'UTC' => 'UTC',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('locale')
                            ->label('زبان')
                            ->options([
                                'fa' => 'فارسی',
                                'en' => 'English',
                            ])
                            ->required()
                            ->native(false),
                        Select::make('currency')
                            ->label('واحد پول')
                            ->options([
                                'IRR' => 'ریال',
                                'IRT' => 'تومان',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('date_format')
                            ->label('قالب تاریخ')
                            ->required()
                            ->maxLength(30),
                        Select::make('theme_density')
                            ->label('چگالی رابط')
                            ->options([
                                'comfortable' => 'عادی',
                                'compact' => 'فشرده',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
                Section::make('اعلان‌ها')
                    ->schema([
                        Toggle::make('notifications_email')
                            ->label('اعلان ایمیل')
                            ->inline(false),
                        Toggle::make('notifications_sms')
                            ->label('اعلان پیامک')
                            ->inline(false),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->authorizeAdmin();

        $companyId = app(CompanyContext::class)->id();
        abort_if($companyId === null, 403);

        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            CompanySetting::query()->updateOrCreate(
                [
                    'company_id' => $companyId,
                    'key' => $key,
                ],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                ],
            );
        }

        app(CompanyTheme::class)->forget($companyId);

        Notification::make()
            ->title('تنظیمات ذخیره شد')
            ->success()
            ->send();
    }

    protected function authorizeAdmin(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
