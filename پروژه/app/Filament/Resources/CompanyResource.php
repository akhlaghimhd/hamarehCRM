<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class CompanyResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'companies.manage';
    protected static ?string $permissionManage = 'companies.manage';

    protected static ?string $model = Company::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'شرکت';

    protected static ?string $pluralModelLabel = 'شرکت‌ها';

    protected static ?string $navigationLabel = 'شرکت‌ها';

    protected static string|UnitEnum|null $navigationGroup = 'سیستم';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        if ($user && $user->is_active && ! DB::table('company_user')->where('user_id', $user->id)->exists()) {
            return true;
        }

        return static::allowed(static::$permissionView);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        if ($user && $user->is_active && ! DB::table('company_user')->where('user_id', $user->id)->exists()) {
            return true;
        }

        return $user && $user->is_active && $user->isCompanyAdmin();
    }

    public static function canView($record): bool
    {
        return static::belongsToCurrentCompany($record)
            && static::allowed(static::$permissionView);
    }

    public static function canEdit($record): bool
    {
        return static::belongsToCurrentCompany($record)
            && static::allowed(static::$permissionManage);
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return static::belongsToCurrentCompany($record)
            && $user !== null
            && $user->is_active
            && $user->isCompanyAdmin();
    }

    protected static function belongsToCurrentCompany(mixed $record): bool
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $record instanceof Company
            && $companyId !== null
            && (int) $record->id === (int) $companyId;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $companyId = auth()->user()?->currentCompanyId();

        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereKey($companyId);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام شرکت')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get): void {
                        if (filled($get('slug'))) {
                            return;
                        }

                        $suggestedSlug = Str::slug((string) $state);

                        if ($suggestedSlug !== '') {
                            $set('slug', $suggestedSlug);
                        }
                    }),
                TextInput::make('slug')
                    ->label('اسلاگ')
                    ->required()
                    ->maxLength(255)
                    ->helperText('شناسه یکتای لاتین شرکت؛ فقط حروف کوچک انگلیسی، عدد و خط تیره.')
                    ->rules(['regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'])
                    ->unique(ignoreRecord: true),
                TextInput::make('national_id')
                    ->label('شناسه ملی / اقتصادی')
                    ->maxLength(50),
                TextInput::make('registration_number')
                    ->label('شماره ثبت')
                    ->maxLength(50),
                TextInput::make('phone')
                    ->label('تلفن')
                    ->tel()
                    ->maxLength(50),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255),
                Textarea::make('address')
                    ->label('آدرس')
                    ->rows(3)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true)
                    ->disabled(fn (): bool => ! (auth()->user()?->isCompanyAdmin() ?? false))
                    ->dehydrated()
                    ->helperText('تغییر وضعیت شرکت فقط برای مدیر شرکت مجاز است.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->description(fn (Company $record): string => (string) $record->slug)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('national_id')
                    ->label('شناسه ملی')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone')
                    ->label('تلفن')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('ایجاد')
                    ->dateTime('Y/m/d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->recordUrl(null)
            ->emptyStateHeading('هنوز شرکتی ثبت نشده است')
            ->emptyStateDescription('برای شروع، اولین شرکت خود را ایجاد کنید.')
            ->emptyStateActions([
                CreateAction::make()->label('ایجاد شرکت'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
