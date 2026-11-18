<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Actions\CreateLinkedTaskAction;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'سفارش';

    protected function getHeaderActions(): array
    {
        return [
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

    protected function afterSave(): void
    {
        if ($this->record->status === Order::STATUS_CONFIRMED && blank($this->record->confirmed_at)) {
            $this->record->confirmed_at = now();
            $this->record->save();
        }
    }
}
