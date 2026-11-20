<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\ProductOptionResource\Pages;
use App\Filament\Resources\ProductOptionResource\RelationManagers\VariantsRelationManager;
use App\Models\ProductLine;
use App\Models\ProductOption;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductOptionResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = ProductOption::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationLabel = 'اقلام و آپشن‌ها';
    protected static ?string $modelLabel = 'قلم';
    protected static ?string $pluralModelLabel = 'اقلام و آپشن‌ها';
    protected static string|UnitEnum|null $navigationGroup = 'کاتالوگ نوتوپیا';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $id = ProductLine::query()->where('code', 'notopia')->value('id');
        if ($id) {
            $q->where('product_line_id', $id);
        }

        return $q;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->label('کد')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make()->label('ویرایش'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductOptions::route('/'),
            'create' => Pages\CreateProductOption::route('/create'),
            'edit' => Pages\EditProductOption::route('/{record}/edit'),
        ];
    }
}
