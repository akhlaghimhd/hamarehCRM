<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Order;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    protected static ?string $title = 'ویرایش پیش‌فاکتور';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toOrder')
                ->label('تبدیل به سفارش')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => QuoteResource::canEdit($this->record)
                    && $this->record->status !== Quote::STATUS_CONVERTED
                    && $this->record->lines()->exists())
                ->action(function () {
                    $order = Order::fromQuote($this->record);
                    Notification::make()
                        ->title('سفارش ساخته شد')
                        ->body($order->number)
                        ->success()
                        ->send();

                    return redirect(OrderResource::getUrl('edit', ['record' => $order]));
                }),
            DeleteAction::make()
                ->label('حذف')
                ->visible(fn () => QuoteResource::canDelete($this->record)
                    && $this->record->status !== Quote::STATUS_CONVERTED),
        ];
    }
}
