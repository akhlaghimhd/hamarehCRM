<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Models\Quote;
use Filament\Resources\Pages\CreateRecord;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    protected static ?string $title = 'پیش‌فاکتور جدید';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_user_id'] = $data['owner_user_id'] ?? auth()->id();
        $data['status'] = $data['status'] ?? Quote::STATUS_DRAFT;
        $companyId = session('current_company_id') ?? auth()->user()?->currentCompanyId();
        if ($companyId) {
            $data['number'] = Quote::nextNumber((int) $companyId);
            $data['company_id'] = $companyId;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
