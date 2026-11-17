<?php

namespace Tests\Unit;

use App\Support\Filament\FormDrawerWidth;
use PHPUnit\Framework\TestCase;

class FormDrawerWidthTest extends TestCase
{
    public function test_small_forms_use_compact_drawers(): void
    {
        $this->assertSame('hm-drawer-width--compact', FormDrawerWidth::markerClass(4, 2));
    }

    public function test_medium_forms_use_medium_drawers(): void
    {
        $this->assertSame('hm-drawer-width--medium', FormDrawerWidth::markerClass(7, 2));
    }

    public function test_long_forms_use_wide_drawers(): void
    {
        $this->assertSame('hm-drawer-width--wide', FormDrawerWidth::markerClass(13, 2));
    }

    public function test_single_column_forms_calculate_rows_from_field_count(): void
    {
        $this->assertSame('hm-drawer-width--wide', FormDrawerWidth::markerClass(5, 1));
    }
}
