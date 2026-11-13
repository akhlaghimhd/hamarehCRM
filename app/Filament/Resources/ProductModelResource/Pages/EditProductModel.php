<?php
namespace App\Filament\Resources\ProductModelResource\Pages;
use App\Filament\Resources\ProductModelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductModel extends EditRecord
{
    protected static string $resource = ProductModelResource::class;
    protected static ?string $title = 'ویرایش مدل محصول';
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('حذف')->visible(fn ($r) => ProductModelResource::canDelete($r))];
    }



    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['has_special_paper'] = false;
        $data['includes_erasable_pen'] = false;
        $data['includes_cloth'] = false;
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record->fresh();
        if (($record->status ?? '') !== 'published') {
            return;
        }
        if ($record->isPublishable()) {
            return;
        }
        $record->update(['status' => 'draft']);
        \Filament\Notifications\Notification::make()
            ->title('انتشار لغو شد')
            ->body('حداقل یک سایز و یک Cover SKU لازم است. وضعیت: پیش‌نویس.')
            ->danger()->send();
    }
}
