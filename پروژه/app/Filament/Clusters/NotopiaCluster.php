<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use UnitEnum;

class NotopiaCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'نوتوپیا';

    protected static string|UnitEnum|null $navigationGroup = 'محصولات';

    protected static ?int $navigationSort = 99;

    protected static ?string $slug = 'notopia';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // مخفی؛ از navigationGroup استفاده می‌کنیم
    }
}
