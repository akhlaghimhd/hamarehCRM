<?php

namespace App\Filament\Resources\ProductModelResource\Pages;

use App\Filament\Resources\ProductModelResource;
use App\Models\CatalogSeason;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProductModel extends EditRecord
{
    protected static string $resource = ProductModelResource::class;

    protected static ?string $title = 'ویرایش مدل محصول';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publish')
                ->label('انتشار')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => ProductModelResource::canEdit($this->record)
                    && ($this->record->status ?? '') !== 'published')
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->record->fresh(['sizes', 'coverSkus']);
                    if (! $record->isPublishable()) {
                        Notification::make()
                            ->title('قابل انتشار نیست')
                            ->body('حداقل یک سایز و یک جلد/رنگ در ماتریس مجاز لازم است. خانواده و کالکشن هم باید ست باشند.')
                            ->danger()
                            ->send();

                        return;
                    }
                    $record->update(['status' => 'published', 'is_active' => true]);
                    $this->refreshFormData(['status', 'is_active']);
                    Notification::make()->title('مدل منتشر شد')->success()->send();
                }),

            Action::make('archive')
                ->label('بایگانی')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn () => ProductModelResource::canEdit($this->record)
                    && ($this->record->status ?? '') !== 'archived')
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update(['status' => 'archived']);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('مدل بایگانی شد')->success()->send();
                }),

            Action::make('copyToSeason')
                ->label('کپی به نسخه دیگر')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn () => ProductModelResource::canCreate())
                ->form([
                    Select::make('catalog_season_id')
                        ->label('نسخه مقصد')
                        ->options(fn () => CatalogSeason::query()
                            ->where('is_active', true)
                            ->orderBy('sort')
                            ->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->default(fn () => $this->record->catalog_season_id),
                    TextInput::make('name')
                        ->label('نام مدل در نسخه جدید')
                        ->default(fn () => $this->record->name)
                        ->required(),
                    TextInput::make('code')
                        ->label('کد (اختیاری)')
                        ->helperText('خالی = پیشنهاد خودکار'),
                ])
                ->action(function (array $data) {
                    $copy = $this->record->duplicateForCatalog(
                        seasonId: (int) $data['catalog_season_id'],
                        code: filled($data['code'] ?? null) ? $data['code'] : null,
                        name: $data['name'] ?? null
                    );

                    Notification::make()
                        ->title('کپی ساخته شد')
                        ->body($copy->code)
                        ->success()
                        ->send();

                    return redirect(ProductModelResource::getUrl('edit', ['record' => $copy]));
                }),

            DeleteAction::make()
                ->label('حذف')
                ->visible(fn ($r) => ProductModelResource::canDelete($r)),
        ];
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
        Notification::make()
            ->title('انتشار لغو شد')
            ->body('حداقل یک سایز و یک Cover SKU لازم است. وضعیت: پیش‌نویس.')
            ->danger()
            ->send();
    }
}
