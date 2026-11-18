<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'فاکتورها';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره')->searchable(),
                Tables\Columns\TextColumn::make('order.number')->label('سفارش')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Invoice::statusOptions()[$state] ?? ($state ?? '—')),
                Tables\Columns\TextColumn::make('total')->label('جمع')->numeric(),
                Tables\Columns\TextColumn::make('paid_amount')->label('پرداخت‌شده')->numeric(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('مانده')
                    ->state(fn (Invoice $record): float => $record->balance())
                    ->numeric(),
                Tables\Columns\TextColumn::make('issued_on')->label('تاریخ')->date('Y/m/d'),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('فاکتوری برای این مشتری نیست')
            ->recordActions([
                Action::make('open')
                    ->label('باز کردن')
                    ->url(fn (Invoice $record): string => InvoiceResource::getUrl('edit', ['record' => $record])),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
