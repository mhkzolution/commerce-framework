<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Models\Brand;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Core\Models\SearchDocument;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductSearchIndexerTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_indexer_dedupes_attribute_codes_and_lists_every_sku(): void
    {
        $defaultVariant = $this->createPurchasableProduct(sku: 'VAR-BLUE');
        $product = $defaultVariant->product;
        $product->update([
            'type' => 'variable',
            'meta' => [
                'specifications' => ['color' => 'Definitely Not Red'],
                'variant_options' => ['Fabricated'],
                'options' => ['Fabricated'],
            ],
        ]);

        $extraVariant = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'VAR-RED',
            name: 'Red',
            price: 2500,
            position: 1,
        ));

        $color = Attribute::query()->create([
            'code' => 'color-'.uniqid(),
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $red = $this->createAttributeValue($color, 'Red');

        foreach ([$defaultVariant, $extraVariant] as $variant) {
            ProductAttributeValue::query()->create([
                'product_id' => $product->id,
                'attribute_id' => $color->id,
                'product_variant_id' => $variant->id,
                'attribute_value_id' => $red->id,
                'value' => $red->label,
            ]);
        }

        $brand = Brand::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
        ]);
        $product->update(['brand_uuid' => $brand->uuid]);

        app(ProductSearchIndexer::class)->index($product->fresh());

        $document = SearchDocument::query()
            ->where('index_name', ProductSearchIndexer::INDEX)
            ->where('document_id', $product->uuid)
            ->firstOrFail();

        $this->assertEqualsCanonicalizing(['VAR-BLUE', 'VAR-RED'], $document->payload['skus']);
        $this->assertSame([['code' => $red->code, 'label' => 'Red']], $document->payload['attributes']);
        $this->assertSame('Acme', $document->payload['brand_name']);
        $this->assertSame('acme', $document->payload['brand_slug']);
        $this->assertStringContainsString('VAR-BLUE', $document->body);
        $this->assertStringContainsString('VAR-RED', $document->body);
        $this->assertArrayNotHasKey('specifications', $document->payload);
        $this->assertArrayNotHasKey('variant_options', $document->payload);
        $this->assertArrayNotHasKey('options', $document->payload);
    }

    private function createAttributeValue(Attribute $attribute, string $label): AttributeValue
    {
        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => app(AttributeValueService::class)->allocateCode($attribute->id, $label),
            'label' => $label,
            'position' => 0,
        ]);
    }
}
