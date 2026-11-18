<?php

namespace App\Filament\Resources\StockLevelResource\Pages;

use App\Filament\Resources\StockLevelResource;
use App\Models\StockLevel;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditStockLevel extends EditRecord
{
    protected static string $resource = StockLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->qtyAction('receive', 'ورود', 'heroicon-o-plus-circle', 'success', fn (StockLevel $r, int $qty, ?string $reason) => $r->receive($qty, $reason)),
            $this->qtyAction('reserve', 'رزرو', 'heroicon-o-lock-closed', 'warning', fn (StockLevel $r, int $qty, ?string $reason) => $r->reserve($qty, $reason)),
            $this->qtyAction('release', 'آزادسازی', 'heroicon-o-lock-open', 'gray', fn (StockLevel $r, int $qty, ?string $reason) => $r->release($qty, $reason)),
            $this->qtyAction('transfer', 'انتقال به تولید', 'heroicon-o-arrow-path', 'info', fn (StockLevel $r, int $qty, ?string $reason) => $r->transferToProduction($qty, $reason)),
            $this->qtyAction('consume', 'مصرف', 'heroicon-o-minus-circle', 'danger', fn (StockLevel $r, int $qty, ?string $reason) => $r->consume($qty, $reason)),
            $this->qtyAction('ready', 'آماده تحویل', 'heroicon-o-check-badge', 'success', fn (StockLevel $r, int $qty, ?string $reason) => $r->markReady($qty, $reason)),
            $this->qtyAction('deliver', 'تحویل', 'heroicon-o-truck', 'primary', fn (StockLevel $r, int $qty, ?string $reason) => $r->deliver($qty, $reason)),
            Action::make('adjust')
                ->label('اصلاح')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->form([
                    TextInput::make('delta')
                        ->label('تغییر (مثبت/منفی)')
                        ->numeric()
                        ->required(),
                    Textarea::make('reason')
                        ->label('علت')
                        ->required()
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->adjust((int) $data['delta'], (string) $data['reason']);
                        $this->refreshFormData(['qty_on_hand', 'qty_reserved', 'qty_in_production', 'qty_ready']);
                        Notification::make()->title('اصلاح موجودی ثبت شد.')->success()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->hidden();
    }

    /**
     * @param  callable(StockLevel, int, ?string): mixed  $callback
     */
    private function qtyAction(string $name, string $label, string $icon, string $color, callable $callback): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->form([
                TextInput::make('qty')
                    ->label('مقدار')
                    ->numeric()
                    ->minValue(1)
                    ->required(),
                Textarea::make('reason')
                    ->label('یادداشت')
                    ->rows(2),
            ])
            ->action(function (array $data) use ($callback): void {
                try {
                    $callback($this->record, (int) $data['qty'], $data['reason'] ?? null);
                    $this->refreshFormData(['qty_on_hand', 'qty_reserved', 'qty_in_production', 'qty_ready']);
                    Notification::make()->title('گردش موجودی ثبت شد.')->success()->send();
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
