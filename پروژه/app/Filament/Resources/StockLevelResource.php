<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ChecksResourcePermission;
use App\Filament\Resources\StockLevelResource\Pages;
use App\Filament\Resources\StockLevelResource\RelationManagers\MovementsRelationManager;
use App\Models\CoverSku;
use App\Models\StockLevel;
use App\Support\Fa;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class StockLevelResource extends Resource
{
    use ChecksResourcePermission;

    protected static ?string $permissionView = 'inventory.view';

    protected static ?string $permissionManage = 'inventory.manage';

    protected static ?string $model = StockLevel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $modelLabel = 'موجودی';

    protected static ?string $pluralModelLabel = 'موجودی‌ها';

    protected static ?string $navigationLabel = 'موجودی';

    protected static string|UnitEnum|null $navigationGroup = 'عملیات';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('قلم موجودی')
                ->schema([
                    Select::make('cover_sku_id')
                        ->label('کاور SKU')
                        ->options(fn () => CoverSku::query()->orderBy('sku')->pluck('sku', 'id'))
                        ->searchable()
                        ->required()
                        ->visibleOn('create')
                        ->dehydrated(false),
                    TextInput::make('item_label')
                        ->label('برچسب')
                        ->disabled()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                    TextInput::make('qty_on_hand')
                        ->label('فیزیکی')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0))
                        ->visibleOn('edit'),
                    TextInput::make('qty_reserved')
                        ->label('رزرو')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0))
                        ->visibleOn('edit'),
                    TextInput::make('qty_in_production')
                        ->label('در تولید')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0))
                        ->visibleOn('edit'),
                    TextInput::make('qty_ready')
                        ->label('آماده تحویل')
                        ->disabled()
                        ->dehydrated(false)
                        ->formatStateUsing(fn ($state) => Fa::digits($state ?? 0))
                        ->visibleOn('edit'),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item_label')
                    ->label('قلم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_on_hand')
                    ->label('فیزیکی')
                    ->formatStateUsing(fn ($state) => Fa::digits($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('qty_reserved')
                    ->label('رزرو')
                    ->formatStateUsing(fn ($state) => Fa::digits($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('available')
                    ->label('آزاد')
                    ->state(fn (StockLevel $record) => $record->availableQty())
                    ->formatStateUsing(fn ($state) => Fa::digits($state)),
                Tables\Columns\TextColumn::make('qty_in_production')
                    ->label('در تولید')
                    ->formatStateUsing(fn ($state) => Fa::digits($state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('qty_ready')
                    ->label('آماده تحویل')
                    ->formatStateUsing(fn ($state) => Fa::digits($state))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('به‌روزرسانی')
                    ->jalaliDateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('item_label')
            ->actions([
                EditAction::make()->label('مدیریت'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockLevels::route('/'),
            'create' => Pages\CreateStockLevel::route('/create'),
            'edit' => Pages\EditStockLevel::route('/{record}/edit'),
        ];
    }
}
