<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CoverTypeResource\Pages;
use App\Models\CoverType;
use App\Models\ProductLine;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CoverTypeResource extends Resource
{
public static function shouldRegisterNavigation(): bool
    {
        return false;
    }


    use ChecksResourcePermission;
protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = CoverType::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'انواع جلد';

    protected static string|\UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?string $modelLabel = 'نوع جلد';
    protected static ?string $pluralModelLabel = 'انواع جلد';
    protected static ?int $navigationSort = 5;

    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $notopiaId = \App\Models\ProductLine::query()->where('code', 'notopia')->value('id');
        if ($notopiaId && in_array('product_line_id', (new (static::getModel()))->getFillable(), true)) {
            $query->where('product_line_id', $notopiaId);
        }
        return $query;
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_line_id')->label('خط محصول')
                ->options(fn () => ProductLine::query()->pluck('name', 'id'))->searchable(),
            TextInput::make('code')->label('کد')->required()->maxLength(50),
            TextInput::make('name')->label('نام')->required()->maxLength(100),
            KeyValue::make('attributes')->label('ویژگی‌ها (کلید/مقدار)')->columnSpanFull(),
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),
            Tables\Columns\TextColumn::make('code')->label('کد')->badge(),
            Tables\Columns\TextColumn::make('productLine.name')->label('خط'),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->defaultSort('sort')
        ->recordActions([EditAction::make()->label('ویرایش')->visible(fn ($r) => static::canEdit($r))]);
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
