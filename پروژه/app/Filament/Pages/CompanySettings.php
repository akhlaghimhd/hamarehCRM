<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use App\Models\User;
use App\Support\CompanyContext;
use App\Support\CompanySettings as CompanySettingsService;
use App\Support\CompanyTheme;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use JsonException;
use UnitEnum;

class CompanySettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'تنظیمات شرکت';

    protected static ?string $title = 'تنظیمات شرکت';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'company-settings';

    protected string $view = 'filament.pages.company-settings';

    public ?int $editingSettingId = null;

    public string $key = '';

    public string $type = 'string';

    public string $value = '';

    public bool $sensitive = false;

    public string $validationRules = '';

    public string $themePalette = 'default';

    public string $themePrimary = '#087E78';

    public string $themeSecondary = '#3976B8';

    public string $themeDensity = 'balanced';

    public function mount(): void
    {
        $theme = app(CompanyTheme::class)->current();

        $this->themePalette = $theme['palette'];
        $this->themePrimary = $theme['primary'];
        $this->themeSecondary = $theme['secondary'];
        $this->themeDensity = $theme['density'];
    }

    public function updatedThemePalette(string $palette): void
    {
        $preset = app(CompanyTheme::class)->preset($palette);

        if ($preset === null) {
            return;
        }

        $this->themePrimary = $preset['primary'];
        $this->themeSecondary = $preset['secondary'];
    }

    public function updatedThemePrimary(): void
    {
        $this->themePalette = 'custom';
    }

    public function updatedThemeSecondary(): void
    {
        $this->themePalette = 'custom';
    }

    public function saveTheme(): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'themePalette' => ['required', 'in:default,ocean,forest,violet,graphite,custom'],
            'themePrimary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'themeSecondary' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'themeDensity' => ['required', 'in:compact,balanced,comfortable'],
        ], [
            'themePrimary.regex' => 'رنگ اصلی معتبر نیست.',
            'themeSecondary.regex' => 'رنگ ثانویه معتبر نیست.',
        ]);

        app(CompanyTheme::class)->save([
            'palette' => $this->themePalette,
            'primary' => $this->themePrimary,
            'secondary' => $this->themeSecondary,
            'density' => $this->themeDensity,
        ]);

        Notification::make()
            ->title('پوسته شرکت ذخیره شد')
            ->body('رنگ‌ها و تراکم پس از بارگذاری مجدد پنل اعمال می‌شوند.')
            ->success()
            ->send();

        $this->redirect(static::getUrl());
    }

    public function resetTheme(): void
    {
        $preset = app(CompanyTheme::class)->preset('default');

        $this->themePalette = 'default';
        $this->themePrimary = $preset['primary'];
        $this->themeSecondary = $preset['secondary'];
        $this->themeDensity = 'balanced';

        $this->saveTheme();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && (bool) $user->is_active
            && $user->isCompanyAdmin()
            && $user->currentCompanyId() !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function editSetting(int $settingId): void
    {
        $this->authorizeAdmin();

        $setting = CompanySetting::query()->findOrFail($settingId);
        abort_if($setting->key === 'ui.theme', 404);
        $this->editingSettingId = (int) $setting->id;
        $this->key = $setting->key;
        $this->type = $setting->type;
        $this->sensitive = (bool) $setting->is_sensitive;
        $this->validationRules = json_encode($setting->validation_rules ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Never put an existing secret back into the browser's Livewire state.
        $currentValue = $this->sensitive
            ? null
            : app(CompanySettingsService::class)->get(
                app(CompanyContext::class)->requireCompanyId(),
                $setting->key,
            );

        $this->value = $currentValue === null
            ? ''
            : ($this->type === 'json'
                ? json_encode($currentValue, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : (is_bool($currentValue) ? ($currentValue ? '1' : '0') : (string) $currentValue));
    }

    public function saveSetting(): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'key' => ['required', 'string', 'max:100', 'not_in:ui.theme', 'regex:/^[a-z][a-z0-9_.-]{1,99}$/'],
            'type' => ['required', 'in:string,integer,boolean,decimal,json'],
            'value' => ['required', 'string'],
            'validationRules' => ['nullable', 'string'],
        ], [
            'key.regex' => 'کلید باید با حرف انگلیسی کوچک شروع شود و فقط شامل حروف کوچک، عدد، نقطه، خط تیره یا زیرخط باشد.',
            'key.not_in' => 'کلید ui.theme رزرو شده و از بخش پوسته شرکت مدیریت می‌شود.',
            'type.in' => 'نوع داده انتخاب‌شده معتبر نیست.',
            'value.required' => 'مقدار تنظیم را وارد کنید.',
        ]);

        $parsedValue = $this->value;

        try {
            $parsedValue = match ($this->type) {
                'integer' => filter_var($this->value, FILTER_VALIDATE_INT) !== false
                    ? (int) $this->value
                    : throw new JsonException('مقدار باید عدد صحیح باشد.'),
                'decimal' => is_numeric($this->value)
                    ? (float) $this->value
                    : throw new JsonException('مقدار باید عددی باشد.'),
                'boolean' => in_array($this->value, ['1', '0'], true)
                    ? $this->value === '1'
                    : throw new JsonException('مقدار بولی باید ۱ یا ۰ باشد.'),
                'json' => json_decode($this->value, true, 512, JSON_THROW_ON_ERROR),
                default => $this->value,
            };
        } catch (JsonException $exception) {
            $this->addError('value', $exception->getMessage());

            return;
        }

        try {
            $validationRules = blank($this->validationRules)
                ? []
                : json_decode($this->validationRules, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->addError('validationRules', 'قواعد اعتبارسنجی باید JSON معتبر باشند.');

            return;
        }

        if (! is_array($validationRules) || ! array_is_list($validationRules)
            || collect($validationRules)->contains(fn ($rule) => ! is_string($rule) || trim($rule) === '')) {
            $this->addError('validationRules', 'قواعد باید آرایه‌ای از رشته‌ها باشند.');

            return;
        }

        app(CompanySettingsService::class)->set(
            app(CompanyContext::class)->requireCompanyId(),
            $this->key,
            $this->type,
            $parsedValue,
            $validationRules,
            $this->sensitive,
        );

        $this->resetEditor();
        Notification::make()
            ->title('تنظیم شرکت ذخیره شد')
            ->success()
            ->send();
    }

    public function deleteSetting(int $settingId): void
    {
        $this->authorizeAdmin();

        $setting = CompanySetting::query()->findOrFail($settingId);
        abort_if($setting->key === 'ui.theme', 404);
        app(CompanySettingsService::class)->forget(
            app(CompanyContext::class)->requireCompanyId(),
            $setting->key,
        );

        if ($this->editingSettingId === $settingId) {
            $this->resetEditor();
        }

        Notification::make()
            ->title('تنظیم حذف شد')
            ->success()
            ->send();
    }

    public function cancelEdit(): void
    {
        $this->authorizeAdmin();
        $this->resetEditor();
    }

    protected function getViewData(): array
    {
        $this->authorizeAdmin();

        return [
            'settings' => CompanySetting::query()
                ->where('key', '<>', 'ui.theme')
                ->orderBy('key')
                ->get(['id', 'key', 'type', 'is_sensitive', 'updated_at']),
            'themePalettes' => CompanyTheme::paletteOptions(),
        ];
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->is_active || ! $user->isCompanyAdmin()) {
            throw new AuthorizationException('فقط مدیر شرکت مجاز به مدیریت تنظیمات است.');
        }

        app(CompanyContext::class)->requireCompanyId();
    }

    private function resetEditor(): void
    {
        $this->reset(['editingSettingId', 'key', 'value', 'sensitive', 'validationRules']);
        $this->type = 'string';
        $this->resetValidation();
    }
}
