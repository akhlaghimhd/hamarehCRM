<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'فاکتور';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recordPayment')
                ->label('ثبت پرداخت')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => $this->record->canReceivePayment()
                    && (auth()->user()?->isCompanyAdmin() || auth()->user()?->hasPermission('payments.manage')))
                ->form([
                    TextInput::make('amount')
                        ->label('مبلغ')
                        ->numeric()
                        ->required()
                        ->default(fn (): float => $this->record->balance()),
                    DatePicker::make('paid_on')
                        ->label('تاریخ پرداخت')
                        ->default(now())
                        ->required()
                        ->native(false),
                    TextInput::make('method')->label('روش')->maxLength(64),
                    TextInput::make('reference')->label('شماره پیگیری')->maxLength(128),
                    Textarea::make('notes')->label('یادداشت')->rows(2),
                ])
                ->action(function (array $data): void {
                    try {
                        Payment::recordForInvoice($this->record, $data, auth()->user());
                        $this->record->refresh();
                        $this->refreshFormData(['status', 'paid_amount', 'notes']);
                        Notification::make()->title('پرداخت ثبت شد.')->success()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancelInvoice')
                ->label('ابطال')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status !== Invoice::STATUS_CANCELLED
                    && (float) $this->record->paid_amount <= 0)
                ->form([
                    Textarea::make('reason')->label('دلیل ابطال')->required()->rows(2),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->cancel($data['reason'] ?? null);
                        $this->refreshFormData(['status', 'notes']);
                        Notification::make()->title('فاکتور باطل شد.')->warning()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing(['customer', 'order']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['status'], $data['total'], $data['paid_amount'], $data['identity_type']);

        return $data;
    }
}
