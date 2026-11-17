<?php

namespace App\Filament\Resources\ProductLineResource\Pages;

use App\Filament\Resources\ProductLineResource;
use App\Filament\Resources\ProductModelResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductLines extends ListRecords
{
    protected static string $resource = ProductLineResource::class;

    protected static ?string $title = 'فهرست محصولات';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('notopia')
                ->label('ورود به نوتوپیا')
                ->icon('heroicon-o-book-open')
                ->url(fn () => ProductModelResource::getUrl('index'))
                ->color('primary'),
            CreateAction::make()
                ->label('محصول جدید')
                ->visible(fn () => ProductLineResource::canCreate()),
        ];
    }
}
