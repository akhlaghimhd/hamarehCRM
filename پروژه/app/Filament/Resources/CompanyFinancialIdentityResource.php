<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyFinancialIdentityResource\Pages;
use App\Filament\Resources\CompanyFinancialIdentityResource\RelationManagers\BankAccountsRelationManager;
use App\Models\Company;
use App\Models\CompanyFinancialIdentity;
use App\Models\User;
use App\Support\CompanyMembershipAccess;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CompanyFinancialIdentityResource extends Resource
{
    protected static ?string $model = CompanyFinancialIdentity::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationLabel = 'هویت‌های مالی و حساب‌ها';
    protected static ?string $modelLabel = 'هویت مالی';
    protected static ?string $pluralModelLabel = 'هویت‌های مالی';
    protected static string|UnitEnum|null $navigationGroup = 'مدیریت سازمان';
    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->is_active && ($user->isPlatformOwner() || self::manageableCompanies() !== []);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false; // Each company receives exactly one official and one unofficial identity at creation.
    }

    public static function canView($record): bool
    {
        $user = auth()->user();

        return $record instanceof CompanyFinancialIdentity
            && $user instanceof User
            && ($user->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($user, (int) $record->company_id));
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $record instanceof CompanyFinancialIdentity
            && $user instanceof User
            && ($user->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($user, (int) $record->company_id));
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user instanceof User || ! $user->is_active) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isPlatformOwner()) {
            return $query;
        }

        $companyIds = $user->companies()->get()
            ->filter(fn (Company $company) => app(CompanyMembershipAccess::class)->canManage($user, (int) $company->id))
            ->pluck('id');

        return $query->whereIn('company_id', $companyIds);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('شرکت و نوع هویت')
                ->columns(2)
                ->schema([
                    Select::make('company_id')->label('شرکت')->options(fn () => self::manageableCompanies())->searchable()->required()->disabled(),
                    Select::make('type')->label('نوع هویت مالی')->options(['official' => 'رسمی', 'unofficial' => 'غیررسمی'])->required()->native(false)->disabled(),
                    Toggle::make('is_active')->label('فعال')->default(true)->inline(false),
                ]),
            Section::make('اطلاعات هویتی و ثبتی')
                ->columns(2)
                ->schema([
                    TextInput::make('legal_name')->label('نام حقوقی / نام صادرکننده')->required()->maxLength(255)->columnSpanFull(),
                    TextInput::make('national_id')->label('شناسه ملی')->maxLength(32)->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('economic_code')->label('کد اقتصادی')->maxLength(64)->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('registration_number')->label('شماره ثبت')->maxLength(64)->extraInputAttributes(['dir' => 'ltr']),
                ]),
            Section::make('اطلاعات تماس و نشانی')
                ->columns(2)
                ->schema([
                    TextInput::make('phone')->label('تلفن')->tel()->maxLength(32)->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('email')->label('ایمیل')->email()->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('postal_code')->label('کد پستی')->maxLength(20)->extraInputAttributes(['dir' => 'ltr', 'inputmode' => 'numeric']),
                    TextInput::make('website')->label('وب‌سایت')->maxLength(255)->extraInputAttributes(['dir' => 'ltr']),
                    Textarea::make('address')->label('نشانی')->rows(3)->maxLength(2000)->columnSpanFull(),
                ]),
        ]);
    }

    private static function manageableCompanies(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $companies = $user->isPlatformOwner()
            ? Company::query()->where('is_active', true)->orderBy('name')->get()
            : $user->companies()->where('companies.is_active', true)->orderBy('companies.name')->get();

        return $companies
            ->filter(fn (Company $company) => $user->isPlatformOwner() || app(CompanyMembershipAccess::class)->canManage($user, (int) $company->id))
            ->mapWithKeys(fn (Company $company) => [$company->id => $company->name])
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\\Columns\\TextColumn::make('company.name')->label('شرکت')->searchable()->sortable(),
                Tables\\Columns\\TextColumn::make('type')->label('نوع هویت')->formatStateUsing(fn (string $state): string => $state === 'official' ? 'رسمی' : 'غیررسمی')->badge(),
                Tables\\Columns\\TextColumn::make('legal_name')->label('نام حقوقی')->searchable()->wrap(),
                Tables\\Columns\\TextColumn::make('national_id')->label('شناسه ملی')->toggleable(),
                Tables\\Columns\\TextColumn::make('version')->label('نسخه')->alignCenter(),
                Tables\\Columns\\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make()->label('مدیریت هویت و حساب‌ها')->visible(fn (CompanyFinancialIdentity $record): bool => static::canEdit($record)),
            ])
            ->defaultSort('company_id')
            ->emptyStateHeading('هویت مالی ثبت‌شده‌ای وجود ندارد')
            ->emptyStateDescription('برای هر شرکت، هویت رسمی و غیررسمی به‌صورت خودکار ایجاد می‌شود.');
    }

    public static function getRelations(): array
    {
        return [BankAccountsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanyFinancialIdentities::route('/'),
            'create' => Pages\CreateCompanyFinancialIdentity::route('/create'),
            'edit' => Pages\EditCompanyFinancialIdentity::route('/{record}/edit'),
        ];
    }
}
