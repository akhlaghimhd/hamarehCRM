<?php

namespace App\Filament\Resources\CoverColorResource\Pages;

use App\Filament\Concerns\HasNotopiaBreadcrumbs;
use App\Filament\Resources\CoverColorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoverColors extends ListRecords
{
    use HasNotopiaBreadcrumbs;

    protected static string $resource = CoverColorResource::class;

    protected static ?string $title = 'رنگ‌های جلد';

    public function getBreadcrumbs(): array
    {
        return $this->coverSettingsBreadcrumbs('رنگ‌های جلد');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('ایجاد')
                ->visible(fn () => CoverColorResource::canCreate()),
        ];
    }
}
