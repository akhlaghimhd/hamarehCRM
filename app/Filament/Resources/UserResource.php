<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
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

    public static function form(Schema $schema): Schema
    {
        $companyId = session('current_company_id') ?? Company::query()->value('id');

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
                Select::make('role_id')
                    ->label('نقش در شرکت جاری')
                    ->options(
                        Role::query()
                            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                            ->pluck('label', 'id')
                    )
                    ->searchable()
                    ->required()
                    ->dehydrated(true),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
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
                    ->searchable(),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها')
                        ->visible(fn () => static::canDeleteAny()),
                ]),
            ]);
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
