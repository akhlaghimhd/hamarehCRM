<?php

namespace App\Support\Filament;

class FormDrawerWidth
{
    /**
     * Choose a responsive drawer width from the approximate number of field
     * rows. Full-width fields count as their own row in the supplied layout.
     */
    public static function markerClass(int $fieldCount, int $columns = 1): string
    {
        $rows = (int) ceil(max(1, $fieldCount) / max(1, $columns));

        return match (true) {
            $rows <= 2 => 'hm-drawer-width--compact',
            $rows <= 4 => 'hm-drawer-width--medium',
            default => 'hm-drawer-width--wide',
        };
    }
}
