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
use Filament\Forms\Components\Placeholder;
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

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('۱. سازمان و تماس')
                    ->description('اطلاعات اصلی برای ثبت سریع فرصت')
                    ->schema([
                        TextInput::make('organization_name')
                            ->label('نام سازمان / شرکت')
                            ->placeholder('مثال: شرکت صنایع غذایی آفتاب')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (blank($get('title')) && filled($state)) {
                                    $set('title', 'فرصت ' . $state);
                                }
                            }),
                        Select::make('product_interest')
                            ->label('محصول')
                            ->options(Opportunity::productInterestOptions())
                            ->native(false)
                            ->placeholder('— انتخاب محصول —'),
                        Select::make('industry')
                            ->label('صنعت')
                            ->options(Opportunity::industryOptions())
                            ->native(false)
                            ->live()
                            ->placeholder('— انتخاب —'),
                        TextInput::make('industry_other')
                            ->label('توضیح صنعت (سایر)')
                            ->visible(fn (Get $get) => $get('industry') === 'other')
                            ->maxLength(255),
                        TextInput::make('contact_name')
                            ->label('نام فرد تماس')
                            ->placeholder('کسی که با او در ارتباطید')
                            ->maxLength(255),
                        Select::make('contact_role')
                            ->label('نقش در تصمیم خرید')
                            ->options(Opportunity::decisionRoleOptions())
                            ->native(false)
                            ->placeholder('— انتخاب —'),
                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->placeholder('۰۹۱۲۱۲۳۴۵۶۷')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('phone')
                            ->label('تلفن ثابت')
                            ->placeholder('۰۲۱۱۲۳۴۵۶۷۸')
                            ->tel()
                            ->maxLength(30),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('۲. ایمیل و آدرس')
                    ->description('برای پیگیری بعدی مفید است؛ اگر ندارید خالی بگذارید.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('email')
                            ->label('ایمیل')
                            ->email()
                            ->placeholder('name@company.com')
                            ->maxLength(255),
                        TextInput::make('address')
                            ->label('آدرس / محل فعالیت')
                            ->placeholder('شهر و آدرس تقریبی')
                            ->maxLength(255),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('۳. نیاز، بودجه و ارزش')
                    ->description('این‌ها کیفیت سرنخ را بهتر می‌کند؛ بعداً هم قابل تکمیل است.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('purchase_use')
                            ->label('کاربرد خرید / نیاز')
                            ->placeholder('هدیه سازمانی، برندینگ، نمایشگاه...')
                            ->maxLength(255),
                        TextInput::make('qty_approx')
                            ->label('تیراژ تقریبی')
                            ->placeholder('مثلاً ۲۵۰۰ جلد')
                            ->maxLength(100),
                        Select::make('budget_band')
                            ->label('بودجه تقریبی')
                            ->options(Opportunity::budgetOptions())
                            ->native(false)
                            ->placeholder('— انتخاب —'),
                        TextInput::make('buy_timing')
                            ->label('زمان خرید احتمالی')
                            ->placeholder('فوری / تا پایان ماه / فصل بعد')
                            ->maxLength(100),
                        Select::make('value_focus')
                            ->label('ارزش مورد توجه مشتری')
                            ->options(Opportunity::valueFocusOptions())
                            ->native(false)
                            ->live()
                            ->placeholder('— انتخاب —'),
                        TextInput::make('value_other')
                            ->label('توضیح ارزش (سایر)')
                            ->visible(fn (Get $get) => $get('value_focus') === 'other')
                            ->maxLength(255),
                        Textarea::make('main_objection')
                            ->label('نگرانی یا اعتراض اصلی')
                            ->placeholder('اگر مخالفت یا دغدغه‌ای مطرح شده بنویسید')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('۴. مرحله قیف و وضعیت')
                    ->description('مرحله قیف را حتماً مشخص کنید؛ برای فرصت تازه معمولاً «سرنخ اولیه» مناسب است.')
                    ->schema([
                        Placeholder::make('stage_guide')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-sm text-gray-600 dark:text-gray-300 space-y-1 leading-relaxed">'
                                . '<div><strong>سرنخ اولیه:</strong> اولین تماس یا معرفی؛ هنوز جلسه رسمی یا نیاز مشخص نشده.</div>'
                                . '<div><strong>واجد شرایط:</strong> بودجه، زمان خرید و نقش تصمیم‌گیر تا حدی روشن است.</div>'
                                . '<div><strong>جلسه / نمایش:</strong> جلسه حضوری یا آنلاین برگزار شده یا قطعی شده است.</div>'
                                . '<div><strong>پیشنهاد ارسال‌شده:</strong> پیشنهاد قیمت یا طرح رسمی برای مشتری ارسال شده.</div>'
                                . '<div><strong>مذاکره:</strong> در حال چانه‌زنی روی قیمت، تیراژ یا شرایط قرارداد.</div>'
                                . '<div><strong>بسته شدن موفق:</strong> سفارش قطعی شده یا قرارداد امضا شده.</div>'
                                . '<div><strong>بسته شدن ناموفق:</strong> فرصت از دست رفته؛ دلیل باخت را ثبت کنید.</div>'
                                . '</div>'
                            ))
                            ->columnSpanFull(),
                        Select::make('stage_id')
                            ->label('مرحله قیف')
                            ->options(fn () => OpportunityStage::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id'))
                            ->required()
                            ->native(false)
                            ->default(fn () => OpportunityStage::query()
                                ->where('code', 'lead')
                                ->where('is_active', true)
                                ->value('id')),
                        Select::make('status')
                            ->label('وضعیت فرصت')
                            ->options(Opportunity::statusOptions())
                            ->default(Opportunity::STATUS_ACTIVE)
                            ->required()
                            ->native(false)
                            ->live(),
                        Select::make('lost_reason_code')
                            ->label('دلیل توقف یا باخت')
                            ->options(Opportunity::lostReasonOptions())
                            ->native(false)
                            ->placeholder('— فقط در صورت توقف/باخت —')
                            ->visible(fn (Get $get) => in_array($get('status'), [Opportunity::STATUS_LOST, Opportunity::STATUS_PAUSED], true)),
                        TextInput::make('lost_reason')
                            ->label('توضیح دلیل (سایر)')
                            ->maxLength(255)
                            ->visible(fn (Get $get) => in_array($get('status'), [Opportunity::STATUS_LOST, Opportunity::STATUS_PAUSED], true)),
                        Textarea::make('final_result')
                            ->label('نتیجه نهایی (اگر بسته شد)')
                            ->rows(2)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => in_array($get('status'), [Opportunity::STATUS_WON, Opportunity::STATUS_LOST], true)),
                        TextInput::make('title')
                            ->label('عنوان فرصت')
                            ->required()
                            ->maxLength(255)
                            ->helperText('به‌صورت خودکار از نام سازمان پر می‌شود؛ قابل ویرایش است.')
                            ->columnSpanFull(),
                        Select::make('owner_user_id')
                            ->label('مسئول فرصت')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->required(),
                        TextInput::make('amount')
                            ->label('مبلغ تخمینی (ریال)')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('۵. گام پیگیری')
                    ->description('گام بعدی و مهلت پیگیری را مشخص کنید.')
                    ->schema([
                        Textarea::make('next_step')
                            ->label('گام بعدی')
                            ->placeholder('مثال: تماس سه‌شنبه برای هماهنگی جلسه با مدیر خرید')
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->rows(2)
                            ->columnSpanFull(),
                        DatePicker::make('next_step_due')
                            ->label('مهلت گام')
                            ->native(false)
                            ->displayFormat('Y/m/d')
                            ->default(now()->addDays(3)),
                        Select::make('followup_user_id')
                            ->label('مسئول پیگیری')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->helperText('فقط ادمین می‌تواند از ابتدا فرصت را به کاربر دیگری بسپارد.')
                            ->disabled(fn () => ! (auth()->user()?->isCompanyAdmin() ?? false))
                            ->dehydrated(),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),

                Section::make('۶. یادداشت شروع')
                    ->description('اختیاری')
                    ->collapsed()
                    ->schema([
                        Textarea::make('notes')
                            ->label('یادداشت')
                            ->rows(3)
                            ->columnSpanFull(),
                        TextInput::make('source')
                            ->label('منبع / کمپین')
                            ->maxLength(255),
                    ]),
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
                        'paused' => 'warning',
                        'won' => 'info',
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
                    ->label('مسئول')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('مبلغ')
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
                    ->options(fn () => OpportunityStage::query()
                        ->orderBy('sort_order')
                        ->pluck('name', 'id')),
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
                \Filament\Actions\CreateAction::make()->label('فرصت جدید'),
            ])
            ->recordActions([
                EditAction::make()->label('ویرایش')
                    ->visible(fn ($record) => static::canEdit($record)),
                Action::make('won')
                    ->label('برنده')
                    ->icon('heroicon-o-trophy')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => static::canEdit($record) && $record->status !== Opportunity::STATUS_WON)
                    ->action(function (Opportunity $record) {
                        $record->markWon();
                        Notification::make()->title('فرصت برنده شد')->success()->send();
                    }),
                Action::make('lost')
                    ->label('بازنده')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Select::make('lost_reason_code')
                            ->label('دلیل باخت')
                            ->options(Opportunity::lostReasonOptions())
                            ->required()
                            ->native(false),
                        TextInput::make('lost_reason')
                            ->label('توضیح')
                            ->maxLength(255),
                    ])
                    ->visible(fn ($record) => static::canEdit($record) && $record->status !== Opportunity::STATUS_LOST)
                    ->action(function (Opportunity $record, array $data) {
                        $record->markLost($data['lost_reason'] ?? null, $data['lost_reason_code'] ?? null);
                        Notification::make()->title('فرصت بازنده شد')->warning()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها')
                        ->visible(fn () => static::canDeleteAny()),
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
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }
}
