<?php

namespace App\Filament\Resources\WorkPackageResource\Pages;

use App\Filament\Resources\WorkPackageResource;
use App\Models\WorkPackage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use InvalidArgumentException;

class EditWorkPackage extends EditRecord
{
    protected static string $resource = WorkPackageResource::class;

    protected static ?string $title = 'بسته کاری';

    protected function getHeaderActions(): array
    {
        return [
            $this->statusAction('assign', 'ارجاع', 'heroicon-o-paper-airplane', 'info', fn (WorkPackage $p) => $p->assign()),
            $this->statusAction('start', 'شروع', 'heroicon-o-play', 'warning', fn (WorkPackage $p) => $p->start()),
            $this->statusAction('deliver', 'ثبت تحویل', 'heroicon-o-truck', 'primary', fn (WorkPackage $p) => $p->markDelivered()),
            $this->statusAction('accept', 'پذیرش', 'heroicon-o-check-badge', 'success', fn (WorkPackage $p) => $p->accept()),
            Action::make('reject')
                ->label('رد')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->form([
                    Textarea::make('reason')->label('دلیل رد')->required()->rows(3),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->reject($data['reason'] ?? null);
                        $this->refreshFormData(['status', 'notes']);
                        Notification::make()->title('بسته رد شد.')->warning()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            Action::make('cancelPackage')
                ->label('لغو')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->form([
                    Textarea::make('reason')->label('دلیل لغو')->required()->rows(2),
                ])
                ->action(function (array $data): void {
                    try {
                        $this->record->cancel($data['reason'] ?? null);
                        $this->refreshFormData(['status', 'notes']);
                        Notification::make()->title('بسته لغو شد.')->warning()->send();
                    } catch (InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->loadMissing('order');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['status']);

        return $data;
    }

    private function statusAction(string $name, string $label, string $icon, string $color, callable $callback): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->action(function () use ($callback): void {
                try {
                    $callback($this->record);
                    $this->refreshFormData(['status', 'notes']);
                    Notification::make()->title('وضعیت بسته به‌روز شد.')->success()->send();
                } catch (InvalidArgumentException $e) {
                    Notification::make()->title($e->getMessage())->danger()->send();
                }
            });
    }
}
