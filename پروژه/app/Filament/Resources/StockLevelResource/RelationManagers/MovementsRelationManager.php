<?php

namespace App\Filament\Resources\StockLevelResource\RelationManagers;

use App\Models\StockLevel;
use App\Support\Fa;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';

    protected static ?string $title = 'تاریخچه گردش';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')
                    ->label('زمان')
                    ->jalaliDateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('نوع')
                    ->formatStateUsing(fn (string $state) => StockLevel::typeOptions()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('مقدار')
                    ->formatStateUsing(fn ($state) => Fa::digits($state)),
                Tables\Columns\TextColumn::make('qty_on_hand_after')
                    ->label('فیزیکی بعد')
                    ->formatStateUsing(fn ($state) => Fa::digits($state)),
                Tables\Columns\TextColumn::make('qty_reserved_after')
                    ->label('رزرو بعد')
                    ->formatStateUsing(fn ($state) => Fa::digits($state)),
                Tables\Columns\TextColumn::make('qty_in_production_after')
                    ->label('تولید بعد')
                    ->formatStateUsing(fn ($state) => Fa::digits($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('qty_ready_after')
                    ->label('آماده بعد')
                    ->formatStateUsing(fn ($state) => Fa::digits($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reason')
                    ->label('علت')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\TextColumn::make('actor.name')
                    ->label('ثبت‌کننده')
                    ->placeholder('—'),
            ])
            ->defaultSort('occurred_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
