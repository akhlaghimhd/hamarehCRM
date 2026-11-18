<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CoverSku;
use App\Models\ProductModel;
use App\Models\ProductOption;
use App\Models\ProductSize;
use App\Services\CatalogConfigurator;
use App\Support\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CatalogConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_returns_only_allowed_matrix_items(): void
    {
        [$company, $model, $size, $cover, $option] = $this->seedCatalog();

        $schema = app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => app(CatalogConfigurator::class)->schema($company->id, $model->id),
        );

        $this->assertSame($model->id, $schema['model_id']);
        $this->assertSame([$size->id], collect($schema['sizes'])->pluck('id')->all());
        $this->assertSame([$cover->id], collect($schema['cover_skus'])->pluck('id')->all());
        $this->assertSame([$option->id], collect($schema['options'])->pluck('id')->all());
    }

    public function test_invalid_size_is_rejected(): void
    {
        [$company, $model] = $this->seedCatalog();

        $foreignSize = app(CompanyContext::class)->runForCompany($company->id, fn () => ProductSize::query()->create([
            'company_id' => $company->id,
            'code' => 'XX',
            'name' => 'خارج ماتریس',
            'is_active' => true,
        ]));

        $this->expectException(InvalidArgumentException::class);

        app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => app(CatalogConfigurator::class)->validate(
                $company->id,
                'notopia',
                $model->id,
                ['size_id' => $foreignSize->id],
            ),
        );
    }

    public function test_snapshot_captures_selection_and_price(): void
    {
        [$company, $model, $size, $cover, $option] = $this->seedCatalog();

        $snapshot = app(CompanyContext::class)->runForCompany(
            $company->id,
            fn () => app(CatalogConfigurator::class)->snapshot(
                $company->id,
                'notopia',
                $model->id,
                [
                    'size_id' => $size->id,
                    'cover_sku_id' => $cover->id,
                    'option_ids' => [$option->id],
                    'unit_price' => 2500000,
                ],
            ),
        );

        $this->assertSame('notopia', $snapshot['product_type']);
        $this->assertSame($model->id, $snapshot['model']['id']);
        $this->assertSame($size->id, $snapshot['size']['id']);
        $this->assertSame($cover->id, $snapshot['cover_sku']['id']);
        $this->assertSame([$option->id], collect($snapshot['options'])->pluck('id')->all());
        $this->assertSame(2500000.0, $snapshot['price']['unit_price']);
        $this->assertNotEmpty($snapshot['captured_at']);
    }

    /**
     * @return array{Company, ProductModel, ProductSize, CoverSku, ProductOption}
     */
    private function seedCatalog(): array
    {
        $company = Company::query()->create([
            'name' => 'Catalog Co',
            'slug' => 'catalog-co-'.uniqid(),
            'is_active' => true,
        ]);

        $context = app(CompanyContext::class);

        return $context->runForCompany($company->id, function () use ($company) {
            $size = ProductSize::query()->create([
                'company_id' => $company->id,
                'code' => 'A5',
                'name' => 'A5',
                'is_active' => true,
            ]);

            $cover = CoverSku::query()->create([
                'company_id' => $company->id,
                'code' => 'CV-RED',
                'name' => 'جلد قرمز',
                'is_active' => true,
            ]);

            $option = ProductOption::query()->create([
                'company_id' => $company->id,
                'code' => 'PEN',
                'name' => 'قلم پاک‌شو',
                'is_active' => true,
            ]);

            $model = ProductModel::query()->create([
                'company_id' => $company->id,
                'code' => 'KEYVAN-ZIBA-1405',
                'name' => 'زیبا',
                'base_price' => 1_800_000,
                'is_active' => true,
                'status' => 'published',
            ]);

            $model->sizes()->sync([$size->id]);
            $model->coverSkus()->sync([$cover->id]);
            $model->options()->sync([
                $option->id => [
                    'is_recommended' => true,
                    'min_qty' => 1,
                    'max_qty' => 3,
                ],
            ]);

            return [$company, $model->fresh(), $size, $cover, $option];
        });
    }
}
