<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
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
                    ->description('اطلاعات اصلی برای ثبت فرصت')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsible()
                    ->schema([
                        TextInput::make('organization_name')
                            ->label('نام سازمان / شرکت')
                            ->placeholder('مثال: شرکت صنایع غذایی آفتاب')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (blank($get('title')) && filled($state)) {
                                    $set('title', 'فرصت ' . $state);
                                }
                            })
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
                            ->placeholder('انتخاب از محصولات تعریف‌شده')
                            ->helperText('فقط محصولاتی که در کاتالوگ شرکت تعریف شده‌اند')
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
                            ->placeholder('— انتخاب —')
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'تصمیم‌گیرنده نهایی: کسی که امضا می‌کند. تأثیرگذار: نظرش مهم است. کاربر: مصرف‌کننده. معرف: واسطه معرفی.'),
                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->placeholder('۰۹۱۲۱۲۳۴۵۶۷')
                            ->tel()
                            ->maxLength(15)
                            ->rules(['nullable', 'regex:/^(0?9\d{9}|\+989\d{9}|989\d{9})$/'])
                            ->validationMessages([
                                'regex' => 'شماره موبایل معتبر ایران وارد کنید (مثال: ۰۹۱۲۱۲۳۴۵۶۷).',
                            ]),
                        TextInput::make('phone')
                            ->label('تلفن ثابت')
                            ->placeholder('۰۲۱۱۲۳۴۵۶۷۸')
                            ->tel()
                            ->maxLength(15),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->columnSpanFull(),

                Section::make('۲. ایمیل و آدرس')
                    ->description('اختیاری')
                    ->icon('heroicon-o-envelope')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->placeholder('name@company.com')
                            ->maxLength(120),
                        TextInput::make('address')
                            ->label('آدرس / محل فعالیت')
                            ->placeholder('شهر و آدرس تقریبی')
                            ->maxLength(200),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('۳. نیاز، بودجه و ارزش')
                    ->description('اختیاری — کیفیت سرنخ را بهتر می‌کند')
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
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('۵۰۰۰۰۰۰۰')
                            ->helperText('رقم به ریال؛ بدون اعشار'),
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
                    ->description('برای فرصت تازه معمولاً «سرنخ اولیه» مناسب است')
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
                            ->required()
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
                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'سرنخ اولیه → واجد شرایط → جلسه → پیشنهاد → مذاکره → بسته شدن')
                            ->validationMessages([
                                'required' => 'مرحله قیف را انتخاب کنید. اگر خالی است MarketingSeeder را اجرا کنید.',
                            ]),
                        Select::make('status')
                            ->label('وضعیت فرصت')
                            ->options(Opportunity::statusOptions())
                            ->default(Opportunity::STATUS_ACTIVE)
                            ->required()
                            ->native(false),
                        Select::make('owner_user_id')
                            ->label('مالک فرصت')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required()
                            ->visible($isAdmin)
                            ->dehydrated()
                            ->helperText('فقط مدیر می‌تواند مالک را عوض کند'),
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
                    ->description('گام بعدی و مهلت را مشخص کنید')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->collapsible()
                    ->schema([
                        Textarea::make('next_step')
                            ->label('گام بعدی')
                            ->placeholder('مثال: تماس سه‌شنبه برای هماهنگی جلسه با مدیر خرید')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull()
                            ->validationMessages([
                                'required' => 'گام بعدی را بنویسید تا پیگیری از دست نرود.',
                            ]),
                        DatePicker::make('next_step_due')
                            ->label('مهلت گام')
                            ->native(false)
                            ->displayFormat('Y/m/d')
                            ->default(now()->addDays(3))
                            ->required(),
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
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable()
                    ->limit(36)
                    ->description(fn ($record) => $record->organization_name ?: $record->customer?->name),
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('تماس')
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('product_interest')
                    ->label('محصول')
                    ->formatStateUsing(fn ($record) => $record->productInterestLabel())
                    ->toggleable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('stage.name')
                    ->label('مرحله')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
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
                Tables\Columns\TextColumn::make('next_step')
                    ->label('گام بعدی')
                    ->limit(28)
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('next_step_due')
                    ->label('مهلت')
                    ->date('Y/m/d')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('مالک')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('بودجه')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('به‌روزرسانی')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stage_id')
                    ->label('مرحله')
                    ->options(fn () => OpportunityStage::query()->orderBy('sort_order')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(Opportunity::statusOptions()),
                Tables\Filters\Filter::make('mine')
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
