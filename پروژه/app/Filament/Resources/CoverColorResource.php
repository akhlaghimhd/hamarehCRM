<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\CoverColorResource\Pages;
use App\Models\CoverColor;
use App\Models\ProductLine;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CoverColorResource extends Resource
{
    use ChecksResourcePermission;
    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';
    protected static ?string $model = CoverColor::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'رنگ‌های جلد';
    protected static ?string $modelLabel = 'رنگ';
    protected static ?string $pluralModelLabel = 'رنگ‌های جلد';
    protected static string|UnitEnum|null $navigationGroup = 'نوتوپیا';
    protected static ?int $navigationSort = 22;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $id = ProductLine::query()->where('code', 'notopia')->value('id');
        if ($id) $q->where('product_line_id', $id);
        return $q;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام رنگ')->required()->maxLength(100),
            ColorPicker::make('hex')->label('انتخاب رنگ')->hex(),
            Toggle::make('is_active')->label('فعال')->default(true),
            TextInput::make('sort')->label('ترتیب')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\ColorColumn::make('hex')->label('رنگ'),
            Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),
            Tables\Columns\TextColumn::make('hex')->label('کد'),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->defaultSort('sort')
        ->recordActions([EditAction::make()->label('ویرایش')->visible(fn ($r) => static::canEdit($r))]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoverColors::route('/'),
            'create' => Pages\CreateCoverColor::route('/create'),
            'edit' => Pages\EditCoverColor::route('/{record}/edit'),
        ];
    }
}
