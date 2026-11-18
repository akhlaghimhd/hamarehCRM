<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Models\Order;
use App\Models\OrderLine;
use App\Services\OrderInventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use InvalidArgumentException;

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
                Tables\Columns\TextColumn::make('qty_delivered')
                    ->label('تحویل‌شده')
                    ->numeric()
                    ->placeholder('0'),
                Tables\Columns\TextColumn::make('remaining')
                    ->label('مانده')
                    ->state(fn (OrderLine $record): float => $record->remainingQty())
                    ->numeric()
                    ->color(fn (OrderLine $record): string => $record->isFullyDelivered() ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('قیمت واحد')
                    ->numeric(),
                Tables\Columns\TextColumn::make('line_total')
                    ->label('جمع خط')
                    ->numeric(),
            ])
            ->recordActions([
                Action::make('deliverPartial')
                    ->label('تحویل جزئی')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->visible(function (OrderLine $record): bool {
                        $order = $this->getOwnerRecord();

                        return $record->remainingQty() > 0
                            && $order instanceof Order
                            && ! in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DRAFT], true)
                            && (auth()->user()?->isCompanyAdmin()
                                || auth()->user()?->hasPermission('orders.manage')
                                || auth()->user()?->hasPermission('inventory.manage'));
                    })
                    ->form([
                        TextInput::make('qty')
                            ->label('تعداد تحویل')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(fn (OrderLine $record): int => (int) floor($record->remainingQty()))
                            ->helperText(fn (OrderLine $record): string => 'مانده: '.(int) floor($record->remainingQty())),
                    ])
                    ->action(function (OrderLine $record, array $data): void {
                        try {
                            $qty = (int) ($data['qty'] ?? 0);
                            app(OrderInventoryService::class)->deliverLine($record, $qty);
                            Notification::make()
                                ->title('تحویل جزئی ثبت شد.')
                                ->body($qty.' عدد برای خط «'.$record->title.'»')
                                ->success()
                                ->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('خطی ثبت نشده');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
