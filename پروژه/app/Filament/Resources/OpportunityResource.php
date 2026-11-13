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

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی (جدید)';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
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
                            })
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Select::make('product_interest')
                            ->label('خطوط محصول')
                            ->options(Opportunity::productInterestOptions())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->placeholder('یک یا چند محصول')
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
                            ->maxLength(255),
                        TextInput::make('contact_name')
                            ->label('نام فرد تماس')
                            ->placeholder('نام شخص تماس')
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
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->columnSpanFull(),

                Section::make('۲. ایمیل و آدرس')
                    ->description('اختیاری — برای پیگیری بعدی')
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
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('۳. نیاز، بودجه و ارزش')
                    ->description('اختیاری — کیفیت سرنخ را بهتر می‌کند')
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
                            ->placeholder('اگر مخالفت یا دغدغه‌ای مطرح شده')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->columnSpanFull(),

                Section::make('۴. مرحله قیف و وضعیت')
                    ->description('برای فرصت تازه معمولاً «سرنخ اولیه» مناسب است')
                    ->schema([
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
                                ->value('id'))
                            ->helperText('سرنخ اولیه → واجد شرایط → جلسه → پیشنهاد → مذاکره → بسته شدن'),
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
                            ->helperText('خودکار از نام سازمان پر می‌شود؛ قابل ویرایش')
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
                            ->minValue(0)
                            ->placeholder('۰'),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('۵. گام پیگیری')
                    ->description('گام بعدی و مهلت را مشخص کنید')
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
                            ->helperText('فقط ادمین می‌تواند به دیگری بسپارد')
                            ->disabled(fn () => ! (auth()->user()?->isCompanyAdmin() ?? false))
                            ->dehydrated(),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

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
                            ->placeholder('تماس ورودی، نمایشگاه، معرفی...')
                            ->maxLength(255),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),
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
                \Filament\Actions\Action::make('quick')->label('ثبت سریع')->url(static::getUrl('quick-create'))->icon('heroicon-o-bolt'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function ($records) {
                            // company scope already applied via global scope / resource
                        }),
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
