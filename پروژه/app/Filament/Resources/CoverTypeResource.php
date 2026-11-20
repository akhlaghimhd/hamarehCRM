<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CoverTypeResource\Pages;
use App\Models\CoverType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CoverTypeResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = CoverType::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'نوع جلد';
    protected static ?string $pluralModelLabel = 'انواع جلد';
    protected static ?string $navigationLabel = 'نوع جلد';
    protected static string|\UnitEnum|null $navigationGroup = 'کاتالوگ نوتوپیا';
    protected static ?int $navigationSort = 21;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label('کد')->required()->maxLength(50),
            TextInput::make('name')->label('نام')->required()->maxLength(100),
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code')->label('کد')->searchable(),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->defaultSort('sort');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoverTypes::route('/'),
            'create' => Pages\CreateCoverType::route('/create'),
            'edit' => Pages\EditCoverType::route('/{record}/edit'),
        ];
    }
}
