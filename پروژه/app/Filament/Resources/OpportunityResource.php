<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use App\Support\Fa;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OpportunityResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'opportunities.view';

    protected static ?string $permissionManage = 'opportunities.manage';

    protected static ?string $model = Opportunity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $modelLabel = 'فرصت';

    protected static ?string $pluralModelLabel = 'فرصت‌ها';

    protected static ?string $navigationLabel = 'فرصت‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی (جدید)';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        $isAdmin = fn () => auth()->user()?->isCompanyAdmin() ?? false;

        return $schema
            ->columns(1)
            ->components([
                Section::make('۱. سازمان و تماس')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsible()
                    ->schema([
                        TextInput::make('organization_name')
                            ->label('نام سازمان / شرکت')
                            ->placeholder('مثال: شرکت صنایع غذایی آفتاب')
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (blank($get('title')) && filled($state)) {
                                    $set('title', 'فرصت ' . $state);
                                }
                            })
                            ->rules(['required', 'max:120'])
                            ->markAsRequired()
                            ->validationMessages([
                                'required' => 'نام سازمان را وارد کنید.',
                                'max' => 'نام سازمان نباید بیشتر از ۱۲۰ کاراکتر باشد.',
                            ])
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Select::make('product_interest')
                            ->label('محصولات مورد علاقه')
                            ->options(Opportunity::productInterestOptions())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('انتخاب محصول')
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Select::make('industry')
                            ->label('صنعت')
                            ->options(Opportunity::industryOptions())
                            ->native(false)
                            ->live()
                            ->placeholder('— انتخاب —'),
                        TextInput::make('industry_other')
                            ->label('توضیح صنعت (سایر)')
                            ->visible(fn (Get $get) => $get('industry') === 'other')
                            ->maxLength(80),
                        TextInput::make('contact_name')
                            ->label('نام فرد تماس')
                            ->placeholder('نام شخص تماس')
                            ->maxLength(80),
                        Select::make('contact_role')
                            ->label('نقش فرد در خرید')
                            ->options(Opportunity::decisionRoleOptions())
                            ->native(false)
                            ->placeholder('— انتخاب —'),
                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->placeholder('۰۹۱۲۱۲۳۴۵۶۷')
                            ->maxLength(11)
                            ->extraInputAttributes(['inputmode' => 'numeric', 'maxlength' => '11'])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state === null || $state === '') {
                                    return;
                                }
                                $en = Fa::toEnglishDigits((string) $state);
                                $digits = preg_replace('/\D+/', '', $en) ?: '';
                                $set('mobile', Fa::digits($digits));
                            })
                            ->rules([
                                'nullable',
                                function () {
                                    return function (string $attribute, $value, \Closure $fail): void {
                                        if ($value === null || $value === '') {
                                            return;
                                        }
                                        $digits = preg_replace('/\D+/', '', Fa::toEnglishDigits((string) $value)) ?: '';
                                        if (! preg_match('/^09\d{9}$/', $digits)) {
                                            $fail('شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.');
                                        }
                                    };
                                },
                            ])
                            ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== ''
                                ? preg_replace('/\D+/', '', Fa::toEnglishDigits((string) $state))
                                : null),
                        TextInput::make('phone')
                            ->label('تلفن ثابت')
                            ->placeholder('۰۲۱۱۲۳۴۵۶۷۸')
                            ->maxLength(11)
                            ->extraInputAttributes(['inputmode' => 'numeric', 'maxlength' => '11'])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state === null || $state === '') {
                                    return;
                                }
                                $en = Fa::toEnglishDigits((string) $state);
                                $digits = preg_replace('/\D+/', '', $en) ?: '';
                                $set('phone', Fa::digits($digits));
                            })
                            ->rules([
                                'nullable',
                                function () {
                                    return function (string $attribute, $value, \Closure $fail): void {
                                        if ($value === null || $value === '') {
                                            return;
                                        }
                                        $digits = preg_replace('/\D+/', '', Fa::toEnglishDigits((string) $value)) ?: '';
                                        if (! preg_match('/^0\d{10}$/', $digits)) {
                                            $fail('ت ثابت باید ۱۱ رقم و با صفر شروع شود.');
                                        }
                                    };
                                },
                            ])
                            ->dehydrateStateUsing(fn ($state) => $state !== null && $state !== ''
                                ? preg_replace('/\D+/', '', Fa::toEnglishDigits((string) $state))
                                : null),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->columnSpanFull(),

                Section::make('۲. ایمیل و آدرس')
                    ->icon('heroicon-o-envelope')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->placeholder('name@company.com')
                            ->maxLength(120)
                            ->rules(['nullable', 'email', 'max:120'])
                            ->validationMessages([
                                'email' => 'ایمیل معتبر وارد کنید.',
                            ]),
                        TextInput::make('address')
                            ->label('آدرس / محل فعالیت')
                            ->placeholder('شهر و آدرس تقریبی')
                            ->maxLength(200),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('۳. نیاز، بودجه و ارزش')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextInput::make('purchase_use')
                            ->label('کاربرد خرید / نیاز')
                            ->placeholder('هدیه سازمانی، برندینگ، نمایشگاه...')
                            ->maxLength(120),
                        TextInput::make('qty_approx')
                            ->label('تیراژ تقریبی')
                            ->placeholder('مثلاً ۲۵۰۰ جلد')
                            ->maxLength(40),
                        TextInput::make('amount')
                            ->label('بودجه تقریبی (ریال)')
                            ->placeholder('۵۰٬۰۰۰٬۰۰۰')
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->formatStateUsing(function ($state) {
                                if ($state === null || $state === '') {
                                    return null;
                                }
                                if (is_numeric($state)) {
                                    return Fa::number($state);
                                }

                                return Fa::digits(Fa::toEnglishDigits((string) $state));
                            })
                            ->live(debounce: 300)
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state === null || $state === '') {
                                    $set('amount', null);

                                    return;
                                }
                                $en = Fa::toEnglishDigits((string) $state);
                                $digits = preg_replace('/\D+/', '', $en) ?: '';
                                if ($digits === '') {
                                    $set('amount', null);

                                    return;
                                }
                                $formatted = number_format((int) $digits, 0, '', ',');
                                $set('amount', Fa::digits($formatted));
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if ($state === null || $state === '') {
                                    return null;
                                }
                                $en = Fa::toEnglishDigits((string) $state);

                                return (int) (preg_replace('/\D+/', '', $en) ?: 0);
                            })
                            ->rules([
                                'nullable',
                                function () {
                                    return function (string $attribute, $value, \Closure $fail): void {
                                        if ($value === null || $value === '') {
                                            return;
                                        }
                                        $digits = preg_replace('/\D+/', '', Fa::toEnglishDigits((string) $value)) ?: '';
                                        if ($digits === '' || ! ctype_digit($digits)) {
                                            $fail('بودجه باید عدد باشد.');
                                        }
                                    };
                                },
                            ]),
                        TextInput::make('buy_timing')
                            ->label('زمان خرید احتمالی')
                            ->placeholder('فوری / تا پایان ماه / فصل بعد')
                            ->maxLength(60),
                        Select::make('value_focus')
                            ->label('ارزش مورد توجه مشتری')
                            ->options(Opportunity::valueFocusOptions())
                            ->native(false)
                            ->live()
                            ->placeholder('— انتخاب —'),
                        TextInput::make('value_other')
                            ->label('توضیح ارزش (سایر)')
                            ->visible(fn (Get $get) => $get('value_focus') === 'other')
                            ->maxLength(80),
                        Textarea::make('main_objection')
                            ->label('نگرانی یا اعتراض اصلی')
                            ->placeholder('اگر مخالفت یا دغدغه‌ای مطرح شده')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->columnSpanFull(),

                Section::make('۴. مرحله قیف و وضعیت')
                    ->icon('heroicon-o-funnel')
                    ->collapsible()
                    ->schema([
                        Select::make('stage_id')
                            ->label('مرحله قیف')
                            ->options(function () {
                                $stages = OpportunityStage::query()
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->pluck('name', 'id');
                                if ($stages->isEmpty()) {
                                    $stages = OpportunityStage::query()
                                        ->withoutGlobalScopes()
                                        ->where('is_active', true)
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id');
                                }

                                return $stages;
                            })
                            ->native(false)
                            ->default(function () {
                                return OpportunityStage::query()
                                    ->where('code', 'lead')
                                    ->where('is_active', true)
                                    ->value('id')
                                    ?? OpportunityStage::query()
                                        ->withoutGlobalScopes()
                                        ->where('code', 'lead')
                                        ->value('id');
                            })
                            ->rules(['required'])
                            ->markAsRequired()
                            ->validationMessages([
                                'required' => 'مرحله قیف را انتخاب کنید.',
                            ]),
                        Select::make('status')
                            ->label('وضعیت فرصت')
                            ->options(Opportunity::statusOptions())
                            ->default(Opportunity::STATUS_ACTIVE)
                            ->native(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->validationMessages([
                                'required' => 'وضعیت فرصت را انتخاب کنید.',
                            ]),
                        Select::make('owner_user_id')
                            ->label('مالک فرصت')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->visible($isAdmin)
                            ->dehydrated()
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('followup_user_id')
                            ->label('مسئول پیگیری')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->visible($isAdmin)
                            ->dehydrated(),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('۵. گام پیگیری')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->collapsible()
                    ->schema([
                        Textarea::make('next_step')
                            ->label('گام بعدی')
                            ->placeholder('مثال: تماس سه‌شنبه برای هماهنگی جلسه با مدیر خرید')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->rules(fn (string $operation) => $operation === 'create' ? ['required'] : [])
                            ->markAsRequired(fn (string $operation) => $operation === 'create')
                            ->validationMessages([
                                'required' => 'گام بعدی را بنویسید تا پیگیری از دست نرود.',
                            ]),
                        TextInput::make('next_step_due')
                            ->label('مهلت گام (شمسی)')
                            ->placeholder('۱۴۰۴/۰۸/۲۵')
                            ->default(fn () => Fa::dateTime(now()->addDays(3), 'Y/m/d'))
                            ->maxLength(10)
                            ->extraInputAttributes(['inputmode' => 'numeric', 'dir' => 'ltr', 'class' => 'text-left'])
                            ->rules([
                                'required',
                                function () {
                                    return function (string $attribute, $value, \Closure $fail): void {
                                        if ($value === null || $value === '') {
                                            return;
                                        }
                                        if (! Fa::parseJalaliDate((string) $value)) {
                                            $fail('تاریخ را به صورت شمسی وارد کنید؛ مثال: ۱۴۰۴/۰۸/۲۵');
                                        }
                                    };
                                },
                            ])
                            ->markAsRequired()
                            ->validationMessages([
                                'required' => 'مهلت گام را مشخص کنید.',
                            ])
                            ->formatStateUsing(function ($state) {
                                if ($state === null || $state === '') {
                                    return null;
                                }
                                if (is_string($state) && str_contains($state, '/')) {
                                    $en = Fa::toEnglishDigits($state);
                                    if (preg_match('/^\d{4}\//', $en) && (int) substr($en, 0, 4) < 1700) {
                                        return Fa::digits($en);
                                    }
                                }
                                try {
                                    return Fa::dateTime($state, 'Y/m/d');
                                } catch (\Throwable) {
                                    return $state;
                                }
                            })
                            ->dehydrateStateUsing(function ($state) {
                                if ($state === null || $state === '') {
                                    return null;
                                }
                                $parsed = Fa::parseJalaliDate((string) $state);

                                return $parsed?->toDateString();
                            })
                            ->columnSpan(1),
                        Textarea::make('notes')
                            ->label('یادداشت شروع')
                            ->rows(2)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                        TextInput::make('source')
                            ->label('منبع / کمپین')
                            ->placeholder('تماس ورودی، نمایشگاه، معرفی...')
                            ->maxLength(80),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                TextInput::make('title')
                    ->hidden()
                    ->dehydrated(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(36)
                    ->description(fn ($record) => $record->organization_name ?: $record->customer?->name),
                TextColumn::make('contact_name')
                    ->label('تماس')
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('product_interest')
                    ->label('محصول')
                    ->formatStateUsing(fn ($record) => $record->productInterestLabel())
                    ->toggleable()
                    ->wrap(),
                TextColumn::make('stage.name')
                    ->label('مرحله')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Opportunity::statusOptions()[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'nurturing' => 'info',
                        'paused' => 'warning',
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('next_step')
                    ->label('گام بعدی')
                    ->limit(28)
                    ->toggleable()
                    ->placeholder('—'),
                TextColumn::make('next_step_due')
                    ->label('مهلت')
                    ->formatStateUsing(fn ($state) => $state ? Fa::dateTime($state, 'Y/m/d') : '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('owner.name')
                    ->label('مالک')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('بودجه')
                    ->formatStateUsing(fn ($state) => $state !== null ? Fa::number($state) : '—')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('به‌روزرسانی')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('stage_id')
                    ->label('مرحله')
                    ->options(fn () => OpportunityStage::query()->orderBy('sort_order')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(Opportunity::statusOptions()),
                Filter::make('mine')
                    ->label('مال من')
                    ->query(fn (Builder $query) => $query->where('owner_user_id', auth()->id()))
                    ->toggle(),
            ])
            ->emptyStateHeading('فرصتی ثبت نشده')
            ->emptyStateDescription('اولین فرصت فروش را ایجاد کنید.')
            ->emptyStateActions([
                \Filament\Actions\Action::make('quick')->label('ثبت سریع')->url(static::getUrl('quick-create'))->icon('heroicon-o-bolt'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'quick-create' => Pages\QuickCreateOpportunity::route('/quick-create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
