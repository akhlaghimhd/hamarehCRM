<?php

namespace App\Filament\Resources\OpportunityResource\Pages;

use App\Filament\Resources\OpportunityResource;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Services\OpportunityQuickCreate;
use App\Support\Fa;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class QuickCreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    protected static ?string $title = 'ثبت سریع فرصت';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'فرصت ثبت شد';
    }

    public function getBreadcrumb(): string
    {
        return 'ثبت سریع';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('full')
                ->label('فرم کامل')
                ->url(OpportunityResource::getUrl('create'))
                ->color('gray'),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        Section::make('هویت طرف حساب')
                            ->schema([
                                TextInput::make('organization_name')
                                    ->label('نام سازمان / شرکت')
                                    ->placeholder('مثال: صنایع غذایی آفتاب')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if (blank($get('title')) && filled($state)) {
                                            $set('title', 'فرصت '.$state);
                                        }
                                    })
                                    ->validationMessages([
                                        'required' => 'وارد کردن نام سازمان الزامی است.',
                                        'max' => 'نام سازمان نباید بیش از ۲۵۵ نویسه باشد.',
                                    ]),
                                TextInput::make('contact_name')
                                    ->label('نام فرد تماس')
                                    ->placeholder('مثلاً خانم رضایی')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                TextInput::make('mobile')
                                    ->label('موبایل')
                                    ->placeholder('۰۹۱۲۱۲۳۴۵۶۷')
                                    ->tel()
                                    ->required()
                                    ->maxLength(11)
                                    ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric', 'lang' => 'fa', 'maxlength' => '11'])
                                    ->formatStateUsing(fn ($state) => filled($state) ? Fa::digits($state) : $state)
                                    ->live(debounce: 200)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state === null || $state === '') {
                                            $set('mobile', null);

                                            return;
                                        }
                                        $en = Fa::toEnglishDigits((string) $state);
                                        $digits = preg_replace('/\D+/', '', $en) ?? '';
                                        $digits = substr($digits, 0, 11);
                                        $set('mobile', $digits === '' ? null : Fa::digits($digits));
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if ($state === null || $state === '') {
                                            return $state;
                                        }
                                        $en = Fa::toEnglishDigits((string) $state);

                                        return preg_replace('/\D+/', '', $en) ?? $en;
                                    })
                                    ->rule(function () {
                                        return function (string $attribute, mixed $value, \Closure $fail): void {
                                            if ($value === null || $value === '') {
                                                return;
                                            }
                                            $en = Fa::toEnglishDigits((string) $value);
                                            $en = preg_replace('/\D+/', '', $en) ?? $en;
                                            if (strlen($en) !== 11 || ! preg_match('/^09\d{9}$/', $en)) {
                                                $fail('شماره موبایل باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود.');
                                            }
                                        };
                                    })
                                    ->validationMessages([
                                        'required' => 'وارد کردن موبایل الزامی است.',
                                        'max' => 'شماره موبایل حداکثر ۱۱ رقم است.',
                                    ]),
                                TextInput::make('phone')
                                    ->label('تلفن ثابت')
                                    ->tel()
                                    ->maxLength(11)
                                    ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric', 'lang' => 'fa', 'maxlength' => '11'])
                                    ->formatStateUsing(fn ($state) => filled($state) ? Fa::digits($state) : $state)
                                    ->live(debounce: 200)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state === null || $state === '') {
                                            $set('phone', null);

                                            return;
                                        }
                                        $en = Fa::toEnglishDigits((string) $state);
                                        $digits = preg_replace('/\D+/', '', $en) ?? '';
                                        $digits = substr($digits, 0, 11);
                                        $set('phone', $digits === '' ? null : Fa::digits($digits));
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if ($state === null || $state === '') {
                                            return $state;
                                        }
                                        $en = Fa::toEnglishDigits((string) $state);

                                        return preg_replace('/\D+/', '', $en) ?? $en;
                                    })
                                    ->rule(function () {
                                        return function (string $attribute, mixed $value, \Closure $fail): void {
                                            if ($value === null || $value === '') {
                                                return;
                                            }
                                            $en = Fa::toEnglishDigits((string) $value);
                                            $en = preg_replace('/\D+/', '', $en) ?? $en;
                                            if (strlen($en) > 11) {
                                                $fail('شماره تلفن حداکثر ۱۱ رقم است.');
                                            }
                                        };
                                    }),
                                TextInput::make('email')
                                    ->label('ایمیل')
                                    ->email()
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'email'])
                                    ->validationMessages([
                                        'email' => 'ایمیل واردشده معتبر نیست.',
                                    ]),
                                Select::make('decision_role')
                                    ->label('نقش طرف تماس در خرید')
                                    ->options([
                                        'decision_maker' => 'تصمیم‌گیرنده نهایی',
                                        'influencer' => 'تأثیرگذار روی خرید',
                                        'user' => 'مصرف‌کننده / کاربر',
                                        'gatekeeper' => 'رابط سازمانی',
                                        'other' => 'سایر',
                                    ])
                                    ->placeholder('— اختیاری —')
                                    ->native(false),
                            ])
                            ->columns(2)
                            ->columnSpan(1),

                        Section::make('محصول و مرحله')
                            ->schema([
                                TextInput::make('title')
                                    ->label('عنوان فرصت')
                                    ->maxLength(255)
                                    ->placeholder('خالی بماند تا از نام سازمان ساخته شود')
                                    ->columnSpanFull(),
                                Select::make('product_interest')
                                    ->label('خطوط محصول')
                                    ->multiple()
                                    ->options(fn () => \App\Models\ProductLine::query()
                                        ->where('is_active', true)
                                        ->orderBy('sort')
                                        ->orderBy('name')
                                        ->pluck('name', 'code')
                                        ->all())
                                    ->required()
                                    ->native(false)
                                    ->validationMessages([
                                        'required' => 'حداقل یک خط محصول را انتخاب کنید.',
                                    ]),
                                Select::make('stage_id')
                                    ->label('مرحله قیف')
                                    ->options(function () {
                                        OpportunityStage::ensureDefaults();

                                        return OpportunityStage::query()
                                            ->where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->default(fn () => OpportunityStage::query()->where('is_active', true)->orderBy('sort_order')->value('id'))
                                    ->required()
                                    ->native(false)
                                    ->validationMessages([
                                        'required' => 'انتخاب مرحله قیف الزامی است.',
                                    ]),
                                TextInput::make('amount')
                                    ->label('مبلغ تخمینی (ریال)')
                                    ->extraInputAttributes([
                                        'dir' => 'ltr',
                                        'inputmode' => 'numeric',
                                        'lang' => 'fa',
                                        'class' => 'hm-amount-input',
                                        'placeholder' => 'مثلاً ۱,۲۵۰,۰۰۰',
                                    ])
                                    ->formatStateUsing(function ($state) {
                                        if ($state === null || $state === '') {
                                            return null;
                                        }

                                        return Fa::number($state, 0);
                                    })
                                    ->live(debounce: 250)
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state === null || $state === '') {
                                            $set('amount', null);

                                            return;
                                        }
                                        $en = Fa::toEnglishDigits((string) $state);
                                        $digits = preg_replace('/\D+/', '', $en) ?? '';
                                        if ($digits === '') {
                                            $set('amount', null);

                                            return;
                                        }
                                        if (strlen($digits) > 15) {
                                            $digits = substr($digits, 0, 15);
                                        }
                                        $set('amount', Fa::number($digits, 0));
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        if ($state === null || $state === '') {
                                            return null;
                                        }
                                        $en = Fa::toEnglishDigits((string) $state);
                                        $digits = preg_replace('/\D+/', '', $en) ?? '';

                                        return $digits === '' ? null : (int) $digits;
                                    })
                                    ->rule(function () {
                                        return function (string $attribute, mixed $value, \Closure $fail): void {
                                            if ($value === null || $value === '') {
                                                return;
                                            }
                                            $en = Fa::toEnglishDigits((string) $value);
                                            $digits = preg_replace('/\D+/', '', $en) ?? '';
                                            if ($digits === '' || ! ctype_digit($digits)) {
                                                $fail('مبلغ باید عدد معتبر باشد.');
                                            }
                                        };
                                    }),
                                TextInput::make('source')
                                    ->label('منبع')
                                    ->placeholder('تماس / نمایشگاه / معرفی')
                                    ->default('ثبت سریع')
                                    ->maxLength(255),
                            ])
                            ->columns(2)
                            ->columnSpan(1),
                    ]),

                Section::make('گام اول پیگیری')
                    ->schema([
                        Textarea::make('next_step')
                            ->label('گام بعدی')
                            ->placeholder('خالی = پیشنهاد خودکار سیستم')
                            ->rows(2)
                            ->extraInputAttributes([
                                'style' => 'resize:vertical;min-height:2.75rem;height:auto;overflow:auto;line-height:1.5;',
                            ])
                            ->columnSpanFull(),
                        DatePicker::make('next_step_due')
                            ->label('مهلت')
                            ->native(false)
                            ->displayFormat('Y/m/d')
                            ->default(now()->addDays(3))
                            ->required()
                            ->extraInputAttributes(['lang' => 'fa', 'data-fa-digits' => '1', 'class' => 'hm-fa-digits'])
                            ->validationMessages([
                                'required' => 'تعیین مهلت پیگیری الزامی است.',
                            ]),
                        Textarea::make('notes')
                            ->label('یادداشت کوتاه')
                            ->rows(2)
                            ->extraInputAttributes([
                                'style' => 'resize:vertical;min-height:2.75rem;height:auto;overflow:auto;line-height:1.5;',
                            ])
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['owner_user_id'] = auth()->id();
        $data['followup_user_id'] = auth()->id();

        return app(OpportunityQuickCreate::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return OpportunityResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('ثبت سریع')
            ->icon('heroicon-o-bolt');
    }
}
