<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class OrderLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'خطوط سفارش (قفل‌شده)';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('عنوان')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('product_type')
                    ->label('نوع')
                    ->badge(),
                Tables\Columns\TextColumn::make('config_snapshot.size.name')
                    ->label('سایز')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('config_snapshot.cover_sku.name')
                    ->label('جلد')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('config_snapshot.options')
                    ->label('اقلام')
                    ->formatStateUsing(function ($state): string {
                        if (! is_array($state) || $state === []) {
                            return '—';
                        }

                        return collect($state)->pluck('name')->filter()->implode('، ') ?: '—';
                    })
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('تعداد')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('قیمت واحد')
                    ->numeric(),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('جمع خط')
                    ->numeric(),
            ])
            ->paginated(false)
            ->emptyStateHeading('خطی ثبت نشده');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
