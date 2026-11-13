<?php

namespace App\Filament\Resources\ProductModelResource\Pages;

use App\Filament\Resources\ProductModelResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProductModel extends CreateRecord
{
    protected static string $resource = ProductModelResource::class;

    protected static ?string $title = 'ایجاد مدل محصول';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_active'] = $data['is_active'] ?? true;
        $data['has_special_paper'] = false;
        $data['includes_erasable_pen'] = false;
        $data['includes_cloth'] = false;
        $data['status'] = $data['status'] ?? 'draft';

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record->fresh();

        if (($record->status ?? '') !== 'published') {
            return;
        }

        if ($record->isPublishable()) {
            return;
        }

        $record->update(['status' => 'draft']);

        Notification::make()
            ->title('انتشار انجام نشد')
            ->body('حداقل یک سایز و یک رنگ/جلد لازم است. به‌صورت پیش‌نویس ذخیره شد.')
            ->danger()
            ->send();
    }
}
