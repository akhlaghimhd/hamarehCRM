<?php

namespace App\Filament\Resources\AuditEventResource\Pages;

use App\Filament\Resources\AuditEventResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditEvents extends ListRecords
{
    protected static string $resource = AuditEventResource::class;

    protected static ?string $title = 'گزارش ممیزی';

    public function getSubheading(): ?string
    {
        return 'هر ردیف یک تغییر ثبت‌شده در شرکت جاری است؛ کاربر انجام‌دهنده، بخش و شناسه رکورد مقصد را مشخص می‌کنند و ستون‌های قبل و بعد، مقادیر تغییرکرده را نشان می‌دهند.';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
