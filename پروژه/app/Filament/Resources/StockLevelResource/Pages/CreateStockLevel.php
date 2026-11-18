<?php

namespace App\Filament\Resources\StockLevelResource\Pages;

use App\Filament\Resources\StockLevelResource;
use App\Models\CoverSku;
use App\Models\StockLevel;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStockLevel extends CreateRecord
{
    protected static string $resource = StockLevelResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $skuId = (int) ($this->form->getState()['cover_sku_id'] ?? 0);
        $sku = CoverSku::query()->findOrFail($skuId);

        return StockLevel::ensureForItem($sku, $sku->sku ?: $sku->name);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
