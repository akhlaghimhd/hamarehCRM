<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use App\Models\User;
use App\Support\CompanyContext;
use App\Support\CompanySettings as CompanySettingsService;
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
        $this->editingSettingId = (int) $setting->id;
        $this->key = $setting->key;
        $this->type = $setting->type;
        $this->sensitive = (bool) $setting->is_sensitive;

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
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_.-]{1,99}$/'],
            'type' => ['required', 'in:string,integer,boolean,decimal,json'],
            'value' => ['required', 'string'],
        ], [
            'key.regex' => 'کلید باید با حرف انگلیسی کوچک شروع شود و فقط شامل حروف کوچک، عدد، نقطه، خط تیره یا زیرخط باشد.',
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

        app(CompanySettingsService::class)->set(
            app(CompanyContext::class)->requireCompanyId(),
            $this->key,
            $this->type,
            $parsedValue,
            [],
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
                ->orderBy('key')
                ->get(['id', 'key', 'type', 'is_sensitive', 'updated_at']),
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
        $this->reset(['editingSettingId', 'key', 'value', 'sensitive']);
        $this->type = 'string';
        $this->resetValidation();
    }
}
