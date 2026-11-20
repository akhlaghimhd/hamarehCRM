<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\OpportunityResource\Pages;
use App\Filament\Resources\OpportunityResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\SalesRepresentative;
use App\Models\User;
use App\Support\Fa;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

    protected static string|UnitEnum|null $navigationGroup = 'بازاریابی';

    protected static ?int $navigationSort = 20;

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
                            ->maxLength(120)
                            ->rules(['required', 'max:120'])
                            ->markAsRequired()
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Select::make('product_interest')
                            ->label('محصولات مورد علاقه')
                            ->options(Opportunity::productInterestOptions())
                            ->multiple()
                            ->searchable()
                            ->native(false)
                            ->columnSpan(['default' => 1, 'md' => 2]),
                        Select::make('industry')
                            ->label('صنعت')
                            ->options(Opportunity::industryOptions())
                            ->native(false)
                            ->live(),
                        TextInput::make('industry_other')
                            ->label('توضیح صنعت (سایر)')
                            ->visible(fn (Get $get) => $get('industry') === 'other')
                            ->maxLength(80),
                        TextInput::make('contact_name')
                            ->label('نام فرد تماس')
                            ->maxLength(80),
                        Select::make('contact_role')
                            ->label('نقش فرد در خرید')
                            ->options(Opportunity::decisionRoleOptions())
                            ->native(false),
                        TextInput::make('mobile')
                            ->label('موبایل')
                            ->maxLength(15)
                            ->dehydrateStateUsing(function ($state) {
                                if ($state === null || $state === '') {
                                    return $state;
                                }

                                return preg_replace('/[\s\-()]/', '', Fa::toEnglishDigits((string) $state)) ?? $state;
                            }),
                        TextInput::make('phone')
                            ->label('تلفن ثابت')
                            ->maxLength(11),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                    ->columnSpanFull(),
                Section::make('۲. ایمیل و آدرس')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextInput::make('email')->label('ایمیل')->maxLength(120),
                        TextInput::make('address')->label('آدرس / محل فعالیت')->maxLength(200),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),
                Section::make('۳. نیاز، بودجه و ارزش')
                    ->collapsed()
                    ->collapsible()
                    ->schema([
                        TextInput::make('purchase_use')->label('کاربرد خرید / نیاز')->maxLength(120),
                        TextInput::make('qty_approx')->label('تیراژ تقریبی')->maxLength(40),
                        TextInput::make('amount')->label('بودجه تقریبی (ریال)'),
                        TextInput::make('buy_timing')->label('زمان خرید احتمالی')->maxLength(60),
                        Select::make('value_focus')
                            ->label('ارزش مورد توجه مشتری')
                            ->options(Opportunity::valueFocusOptions())
                            ->native(false)
                            ->live(),
                        TextInput::make('value_other')
                            ->label('توضیح ارزش (سایر)')
                            ->visible(fn (Get $get) => $get('value_focus') === 'other')
                            ->maxLength(80),
                        Textarea::make('main_objection')->label('نگرانی یا اعتراض اصلی')->rows(2)->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->columnSpanFull(),
                Section::make('۴. مرحله قیف و وضعیت')
                    ->collapsible()
                    ->schema([
                        Select::make('stage_id')
                            ->label('مرحله قیف')
                            ->options(function () {
                                OpportunityStage::ensureDefaults();

                                return OpportunityStage::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id');
                            })
                            ->native(false)
                            ->rules(['required'])
                            ->markAsRequired()
                            ->validationMessages([
                                'required' => 'انتخاب مرحله قیف الزامی است.',
                            ]),
                        Select::make('status')
                            ->label('وضعیت فرصت')
                            ->options(Opportunity::statusOptions())
                            ->default(Opportunity::STATUS_ACTIVE)
                            ->native(false)
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('owner_user_id')
                            ->label('مالک فرصت')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->visible($isAdmin)
                            ->dehydrated()
                            ->rules(['required'])
                            ->markAsRequired(),
                        Select::make('sales_representative_id')
                            ->label('نماینده فروش')
                            ->options(fn () => SalesRepresentative::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        Select::make('followup_user_id')
                            ->label('مسئول پیگیری')
                            ->options(fn () => User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->id())
                            ->searchable()
                            ->visible($isAdmin)
                            ->dehydrated(),
                        TextInput::make('title')->label('عنوان فرصت')->maxLength(160)->rules(['required'])->markAsRequired()->columnSpanFull(),
                        Textarea::make('notes')->label('یادداشت')->rows(3)->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->limit(40),
                TextColumn::make('organization_name')->label('سازمان')->searchable()->toggleable(),
                TextColumn::make('stage.name')->label('مرحله')->badge(),
                TextColumn::make('status')->label('وضعیت')->badge()
                    ->formatStateUsing(fn (?string $state) => Opportunity::statusOptions()[$state] ?? $state),
                TextColumn::make('owner.name')->label('مالک')->toggleable(),
                TextColumn::make('salesRepresentative.name')->label('نماینده')->toggleable(),
                TextColumn::make('updated_at')->label('به‌روزرسانی')->jalaliDateTime()->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(Opportunity::statusOptions()),
                SelectFilter::make('stage_id')->label('مرحله')->options(fn () => OpportunityStage::query()->pluck('name', 'id')),
            ])
            ->recordActions([
                EditAction::make()->label('ویرایش'),
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
            'view' => Pages\ManageOpportunity::route('/{record}'),
        ];
    }
}
