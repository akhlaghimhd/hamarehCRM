<?php

namespace App\Filament\Resources;

use App\Actions\Identity\RemoveUserFromCompany;
use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'users.view';
    protected static ?string $permissionManage = 'users.manage';

    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'کاربر';

    protected static ?string $pluralModelLabel = 'کاربران';

    protected static ?string $navigationLabel = 'کاربران';

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت سیستم';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'کاربر';
    }

    public static function getPluralModelLabel(): string
    {
        return 'کاربران';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $companyId = auth()->user()?->currentCompanyId();

        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'companies',
            fn (Builder $query) => $query->where('companies.id', $companyId),
        );
    }

    public static function form(Schema $schema): Schema
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->required()
                    ->maxLength(255),
                TextInput::make('username')
                    ->label('نام کاربری')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('ایمیل')
                    ->email()
                    ->maxLength(255),
                TextInput::make('password')
                    ->label('رمز عبور')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->maxLength(255),
                Select::make('role_ids')
                    ->label('نقش‌های کاربر در شرکت جاری')
                    ->options(function (?User $record) use ($companyId): array {
                        $query = Role::query()->where('company_id', $companyId ?? 0);
                        $actor = auth()->user();
                        $canManageAdministratorRole = $actor?->isCompanyAdmin() ?? false;
                        $recordAlreadyHasAdministratorRole = $record !== null
                            && $companyId !== null
                            && static::membershipHasAdminRole($record, (int) $companyId);

                        if (! $canManageAdministratorRole && ! $recordAlreadyHasAdministratorRole) {
                            $query->where('name', '<>', 'admin');
                        }

                        return $query->orderBy('label')->pluck('label', 'id')->all();
                    })
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->disabled(fn (?User $record): bool => $record !== null
                        && $companyId !== null
                        && ! (auth()->user()?->isCompanyAdmin() ?? false)
                        && static::membershipHasAdminRole($record, (int) $companyId))
                    ->dehydrated()
                    ->required(),
                Select::make('manager_user_id')
                    ->label('مدیر مستقیم')
                    ->options(
                        User::query()
                            ->where('is_active', true)
                            ->whereHas(
                                'companies',
                                fn (Builder $query) => $query->where('companies.id', $companyId ?? 0),
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('مدیر باید عضو فعال همین شرکت باشد. چرخه سرپرستی در زمان ذخیره کنترل می‌شود.'),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record !== null && (
                        DB::table('company_user')
                            ->where('user_id', $record->id)
                            ->where('company_id', '<>', $companyId ?? 0)
                            ->exists()
                        || ($record->is_active && DB::table('company_user')
                            ->where('company_id', $companyId ?? 0)
                            ->where('manager_user_id', $record->id)
                            ->exists())
                    ))
                    ->helperText('وضعیت حساب سراسری است؛ کاربر چندشرکتی یا مدیر دارای زیرمجموعه را از اینجا غیرفعال نکنید. حساب غیرفعال مدیر پس از بررسی می‌تواند دوباره فعال شود.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('نام')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('username')
                    ->label('نام کاربری')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('ایمیل')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([
                ViewAction::make()->label('مشاهده')
                    ->visible(fn ($record) => static::canView($record)),
                EditAction::make()->label('ویرایش')
                    ->visible(fn ($record) => static::canEdit($record)),
                Action::make('removeFromCompany')
                    ->label('حذف از شرکت جاری')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('حذف عضویت کاربر')
                    ->modalDescription('حساب کاربری حذف نمی‌شود؛ فقط عضویت او در شرکت جاری برداشته خواهد شد.')
                    ->action(fn ($record) => app(RemoveUserFromCompany::class)->handle(
                        (int) auth()->user()->currentCompanyId(),
                        (int) $record->id,
                        auth()->user(),
                    ))
                    ->successNotificationTitle('عضویت کاربر از شرکت جاری حذف شد')
                    ->visible(fn ($record) => static::canDelete($record)),
            ]);
    }


    private static function membershipHasAdminRole(User $user, int $companyId): bool
    {
        $membership = DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->first(['id', 'role_id']);

        if (! $membership) {
            return false;
        }

        $roleIds = DB::table('company_user_role')
            ->where('company_user_id', $membership->id)
            ->pluck('role_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($roleIds === [] && $membership->role_id !== null) {
            $roleIds = [(int) $membership->role_id];
        }

        return Role::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $roleIds)
            ->where('name', 'admin')
            ->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
