<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\ProductLineResource\Pages;
use App\Models\ProductLine;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ProductLineResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'products.view';
    protected static ?string $permissionManage = 'products.manage';

    protected static ?string $model = ProductLine::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'فهرست محصولات';

    protected static ?string $modelLabel = 'محصول';

    protected static ?string $pluralModelLabel = 'محصولات';

    protected static string|UnitEnum|null $navigationGroup = 'محصولات';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('هویت محصول')->schema([
                TextInput::make('code')->label('کد داخلی')->required()->maxLength(50),
                TextInput::make('name')->label('نام')->required()->maxLength(100),
                TextInput::make('brand')->label('برند')->maxLength(100),
                Select::make('type')->label('نوع')->options([
                    'configurable' => 'قابل پیکربندی',
                    'simple' => 'ساده',
                    'service' => 'خدمت',
                    'software' => 'نرم‌افزار',
                ])->required()->default('configurable'),
                Select::make('lifecycle_status')->label('وضعیت چرخه')->options([
                    'draft' => 'پیش‌نویس',
                    'active' => 'فعال',
                    'discontinued' => 'متوقف',
                ])->required()->default('active'),
                TextInput::make('unit')->label('واحد')->default('عدد')->maxLength(30),
            ])->columns(2),

            Section::make('توضیحات')->schema([
                Textarea::make('short_description')->label('توضیح کوتاه')->rows(2),
                Textarea::make('description')->label('توضیح کامل')->rows(4),
            ]),

            Section::make('سایر')->schema([
                KeyValue::make('attributes')->label('ویژگی‌های اضافه (کلید / مقدار)')->columnSpanFull(),
                Toggle::make('is_active')->label('فعال در سیستم')->default(true),
                TextInput::make('sort')->label('ترتیب نمایش')->numeric()->default(0),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->label('کد')->badge(),
                Tables\Columns\TextColumn::make('type')->label('نوع')
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'configurable' => 'قابل پیکربندی',
                        'simple' => 'ساده',
                        'service' => 'خدمت',
                        'software' => 'نرم‌افزار',
                        default => $s,
                    }),
                Tables\Columns\TextColumn::make('lifecycle_status')->label('چرخه')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'draft' => 'پیش‌نویس',
                        'active' => 'فعال',
                        'discontinued' => 'متوقف',
                        default => $s,
                    }),
                Tables\Columns\TextColumn::make('brand')->label('برند'),
                Tables\Columns\TextColumn::make('unit')->label('واحد'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make()->label('ویرایش')->visible(fn ($r) => static::canEdit($r)),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductLines::route('/'),
            'create' => Pages\CreateProductLine::route('/create'),
            'edit' => Pages\EditProductLine::route('/{record}/edit'),
        ];
    }
}
