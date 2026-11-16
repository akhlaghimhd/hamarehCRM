<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RoleResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'roles.manage';
    protected static ?string $permissionManage = 'roles.manage';

    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $modelLabel = 'نقش';

    protected static ?string $pluralModelLabel = 'نقش‌ها';

    protected static ?string $navigationLabel = 'نقش‌ها و مجوزها';

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت سیستم';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return 'نقش';
    }

    public static function getPluralModelLabel(): string
    {
        return 'نقش‌ها';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $companyId = auth()->user()?->currentCompanyId();

        if (! $companyId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('company_id', $companyId);
    }

    public static function form(Schema $schema): Schema
    {
        $companyId = auth()->user()?->currentCompanyId();

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام سیستمی')
                    ->required()
                    ->maxLength(100)
                    ->rules(fn (?Role $record): array => [
                        'regex:/^[a-z][a-z0-9_-]{0,99}$/',
                        ...($record?->is_system ? [] : ['not_in:admin']),
                    ])
                    ->disabled(fn (?Role $record): bool => (bool) $record?->is_system)
                    ->helperText('نام admin رزرو شده است؛ برای نقش‌های اختصاصی نامی مانند sales یا warehouse انتخاب کنید.'),
                TextInput::make('label')
                    ->label('عنوان نمایشی')
                    ->required()
                    ->maxLength(100),
                Select::make('parent_role_id')
                    ->label('نقش والد')
                    ->options(
                        Role::query()
                            ->where('company_id', $companyId ?? 0)
                            ->orderBy('label')
                            ->pluck('label', 'id')
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('نقش والد باید متعلق به همین شرکت باشد.'),
                Toggle::make('inherit_permissions')
                    ->label('ارث‌بری مجوزهای نقش والد')
                    ->default(true),
                Select::make('copy_permissions_from_role_id')
                    ->label('کپی اولیه مجوزها از نقش')
                    ->options(
                        Role::query()
                            ->where('company_id', $companyId ?? 0)
                            ->orderBy('label')
                            ->pluck('label', 'id')
                    )
                    ->searchable()
                    ->nullable()
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->helperText('فقط یک کپی مستقل در زمان ایجاد انجام می‌شود؛ تغییرات بعدی منبع منتقل نمی‌شوند.'),
                Toggle::make('is_system')
                    ->label('نقش سیستمی')
                    ->default(false)
                    ->disabled()
                    ->helperText('وضعیت سیستمی فقط توسط کد و Seed رسمی مدیریت می‌شود.'),
                CheckboxList::make('permissions')
                    ->label('مجوزهای مستقیم')
                    ->relationship('permissions', 'label')
                    ->columns(2)
                    ->bulkToggleable()
                    ->helperText('مجوزهای مستقیم مستقل از مجوزهای ارث‌رسیده هستند.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->label('عنوان')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('نام سیستمی')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('parentRole.label')
                    ->label('نقش والد')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('تعداد مجوز مستقیم')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('inherit_permissions')
                    ->label('ارث‌بری')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('سیستمی')
                    ->boolean(),
            ])
            ->defaultSort('id')
            ->recordActions([
                EditAction::make()->label('ویرایش'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
