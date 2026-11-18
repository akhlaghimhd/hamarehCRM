<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use InvalidArgumentException;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'پرداخت‌ها';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('شماره'),
                Tables\Columns\TextColumn::make('amount')->label('مبلغ')->numeric(),
                Tables\Columns\TextColumn::make('paid_on')->label('تاریخ')->date('Y/m/d'),
                Tables\Columns\TextColumn::make('method')->label('روش')->placeholder('—'),
                Tables\Columns\TextColumn::make('reference')->label('پیگیری')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('voided_at')
                    ->label('وضعیت')
                    ->formatStateUsing(fn ($state): string => $state ? 'باطل' : 'فعال')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'danger' : 'success'),
            ])
            ->defaultSort('id', 'desc')
            ->emptyStateHeading('پرداختی ثبت نشده')
            ->recordActions([
                Action::make('voidPayment')
                    ->label('ابطال')
                    ->color('danger')
                    ->visible(fn (Payment $record): bool => blank($record->voided_at)
                        && (auth()->user()?->isCompanyAdmin() || auth()->user()?->hasPermission('payments.manage')))
                    ->form([
                        Textarea::make('reason')->label('دلیل')->required()->rows(2),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        try {
                            $record->void($data['reason'] ?? null);
                            Notification::make()->title('پرداخت باطل شد.')->warning()->send();
                            $this->getOwnerRecord()->refresh();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
