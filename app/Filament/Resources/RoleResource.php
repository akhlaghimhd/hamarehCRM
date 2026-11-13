<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Permission;
use App\Models\Role;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('نام سیستمی')
                    ->required()
                    ->maxLength(100)
                    ->helperText('مثلاً: sales, warehouse'),
                TextInput::make('label')
                    ->label('عنوان نمایشی')
                    ->required()
                    ->maxLength(100),
                Toggle::make('is_system')
                    ->label('نقش سیستمی')
                    ->helperText('نقش‌های سیستمی معمولاً حذف نمی‌شوند')
                    ->disabled(fn (?Role $record) => $record?->is_system),
                CheckboxList::make('permissions')
                    ->label('مجوزها')
                    ->relationship('permissions', 'label')
                    ->columns(2)
                    ->bulkToggleable()
                    ->helperText('مجوزها فقط از لیست Seed قابل انتخاب هستند'),
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
                    ->badge(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('تعداد مجوز'),
                Tables\Columns\IconColumn::make('is_system')
                    ->label('سیستمی')
                    ->boolean(),
            ])
            ->defaultSort('id')
            ->recordActions([
                EditAction::make()->label('ویرایش'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->visible(fn () => true),
                ]),
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
