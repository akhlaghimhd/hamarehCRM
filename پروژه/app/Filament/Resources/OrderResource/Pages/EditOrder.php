<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Actions\CreateLinkedTaskAction;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\WorkPackageResource;
use App\Models\Order;
use App\Models\WorkPackage;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
            Action::make('createWorkPackage')
                ->label('بسته کاری')
                ->icon('heroicon-o-cube-transparent')
                ->color('gray')
                ->visible(fn (): bool => ! in_array($this->record->status, [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED], true)
                    && (auth()->user()?->isCompanyAdmin() || auth()->user()?->hasPermission('work_packages.manage')))
                ->form([
                    TextInput::make('title')
                        ->label('عنوان بسته')
                        ->default(fn (): string => 'بسته کاری سفارش '.($this->record->number ?: ''))
                        ->required()
                        ->maxLength(255),
                    Select::make('execution_mode')
                        ->label('نوع اجرا')
                        ->options(WorkPackage::executionModeOptions())
                        ->default(WorkPackage::MODE_INTERNAL)
                        ->required()
                        ->native(false),
                    TextInput::make('contractor_name')
                        ->label('پیمانکار / واحد')
                        ->maxLength(255),
                    Textarea::make('description')
                        ->label('شرح')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        $package = WorkPackage::createForOrder($this->record, $data);

                        Notification::make()
                            ->title('بسته کاری ایجاد شد.')
                            ->body($package->number)
                            ->success()
                            ->send();

                        return redirect(WorkPackageResource::getUrl('edit', ['record' => $package]));
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        return null;
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
