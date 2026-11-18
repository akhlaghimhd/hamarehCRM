<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Actions\CreateLinkedTaskAction;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'سفارش';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startFulfillment')
                ->label('شروع انجام')
                ->icon('heroicon-o-play')
                ->color('info')
                ->visible(fn (): bool => $this->record->canStartFulfillment())
                ->requiresConfirmation()
                ->modalHeading('شروع انجام سفارش')
                ->modalDescription('وضعیت سفارش به «در حال انجام» تغییر می‌کند.')
                ->action(function (): void {
                    try {
                        $this->record->startFulfillment();
                        $this->refreshFormData(['status']);
                        Notification::make()->title('سفارش وارد مرحله انجام شد.')->success()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('markCompleted')
                ->label('تکمیل')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => $this->record->canComplete())
                ->requiresConfirmation()
                ->modalHeading('تکمیل سفارش')
                ->action(function (): void {
                    try {
                        $this->record->markCompleted();
                        $this->refreshFormData(['status']);
                        Notification::make()->title('سفارش تکمیل شد.')->success()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancelOrder')
                ->label('لغو')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->canCancel())
                ->form([
                    Textarea::make('reason')
                        ->label('دلیل لغو')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->cancel($data['reason'] ?? null);
                        $this->refreshFormData(['status', 'notes']);
                        Notification::make()->title('سفارش لغو شد.')->warning()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            CreateLinkedTaskAction::make(
                $this,
                Order::class,
                modalHeading: 'ایجاد کار وابسته به این سفارش',
                subjectDefault: fn (Order $order) => 'پیگیری سفارش: '.($order->number ?: $order->title),
                idempotencyPrefix: 'order',
            ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('customer');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // وضعیت فقط از اکشن‌های کنترل‌شده تغییر می‌کند
        unset($data['status']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->record->status === Order::STATUS_CONFIRMED && blank($this->record->confirmed_at)) {
            $this->record->confirmed_at = now();
            $this->record->save();
        }
    }
}
