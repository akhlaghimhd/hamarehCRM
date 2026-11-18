<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CoverSku;
use App\Models\ProductLine;
use App\Models\StockLevel;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StockLevelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_is_scoped_to_company(): void
    {
        [$companyA, $level] = $this->makeLevel('A');
        $companyB = Company::query()->create([
            'name' => 'Co B',
            'slug' => 'co-b-'.uniqid(),
            'is_active' => true,
        ]);

        $visibleInA = app(CompanyContext::class)->runForCompany(
            $companyA->id,
            fn () => StockLevel::query()->whereKey($level->id)->exists(),
        );

        $visibleInB = app(CompanyContext::class)->runForCompany(
            $companyB->id,
            fn () => StockLevel::query()->whereKey($level->id)->exists(),
        );

        $this->assertTrue($visibleInA);
        $this->assertFalse($visibleInB);
    }

    public function test_receipt_reserve_release_and_transfer_flow(): void
    {
        [, $level] = $this->makeLevel('Flow');

        app(CompanyContext::class)->runForCompany($level->company_id, function () use ($level) {
            $level->receive(10, 'ورود اولیه');
            $level->reserve(3, 'رزرو سفارش');
            $this->assertSame(7, $level->fresh()->availableQty());
            $this->assertSame(3, (int) $level->fresh()->qty_reserved);

            $level->release(1, 'آزادسازی');
            $this->assertSame(2, (int) $level->fresh()->qty_reserved);

            $level->transferToProduction(4, 'شروع تولید');
            $fresh = $level->fresh();
            $this->assertSame(6, (int) $fresh->qty_on_hand);
            $this->assertSame(4, (int) $fresh->qty_in_production);

            $level->markReady(2, 'اتمام بخشی');
            $fresh = $level->fresh();
            $this->assertSame(2, (int) $fresh->qty_in_production);
            $this->assertSame(2, (int) $fresh->qty_ready);

            $level->deliver(1, 'تحویل جزئی');
            $this->assertSame(1, (int) $level->fresh()->qty_ready);

            $this->assertGreaterThanOrEqual(5, $level->movements()->count());
        });
    }

    public function test_cannot_go_negative_on_hand(): void
    {
        [, $level] = $this->makeLevel('Neg');

        $this->expectException(InvalidArgumentException::class);

        app(CompanyContext::class)->runForCompany(
            $level->company_id,
            fn () => $level->adjust(-1, 'کاهش بدون موجودی'),
        );
    }

    /**
     * @return array{Company, StockLevel}
     */
    private function makeLevel(string $suffix): array
    {
        $company = Company::query()->create([
            'name' => 'Inv '.$suffix,
            'slug' => 'inv-'.$suffix.'-'.uniqid(),
            'is_active' => true,
        ]);

        $user = User::factory()->create(['is_active' => true]);
        $user->companies()->attach($company->id, ['is_default' => true]);

        $level = app(CompanyContext::class)->runForCompany($company->id, function () {
            $line = ProductLine::query()->create([
                'name' => 'Line',
                'code' => 'L'.uniqid(),
                'is_active' => true,
                'sort' => 1,
            ]);

            $sku = CoverSku::query()->create([
                'product_line_id' => $line->id,
                'sku' => 'SKU-'.uniqid(),
                'name' => 'Cover',
                'is_active' => true,
                'stock_qty' => 0,
            ]);

            return StockLevel::ensureForItem($sku, $sku->sku);
        });

        return [$company, $level];
    }
}
