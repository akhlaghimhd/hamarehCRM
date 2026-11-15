<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CoverMaterialResource\Pages;
use App\Models\CoverMaterial;
use App\Models\ProductLine;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CoverMaterialResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = CoverMaterial::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'جنس جلد';
    protected static ?string $modelLabel = 'جنس جلد';
    protected static ?string $pluralModelLabel = 'جنس‌های جلد';
    protected static string|UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
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
        return $schema->components([
            TextInput::make('code')->label('کد')->maxLength(50)->required(),
            TextInput::make('name')->label('نام')->required()->maxLength(100),
            
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('code')->label('کد')->badge(),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            Tables\Columns\TextColumn::make('sort')->label('ترتیب')->sortable(),
        ])->defaultSort('sort')
        ->recordActions([EditAction::make()->label('ویرایش')->visible(fn ($r) => static::canEdit($r))]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoverMaterials::route('/'),
            'create' => Pages\CreateCoverMaterial::route('/create'),
            'edit' => Pages\EditCoverMaterial::route('/{record}/edit'),
        ];
    }
}
