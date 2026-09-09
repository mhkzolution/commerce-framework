<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateVariantData;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Models\ProductVariant;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductDetailBuilderTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_unknown_slug_returns_null(): void
    {
        $this->assertNull(app(ProductDetailBuilder::class)->fromSlug('does-not-exist'));
    }

    public function test_maps_default_variant_price_sku_and_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 3200, stock: 3, sku: 'PDP-BUILD-1');

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame($variant->product->name, $data->name);
        $this->assertSame(3200, $data->price);
        $this->assertSame('PDP-BUILD-1', $data->sku);
        $this->assertSame(3, $data->available);
        $this->assertTrue($data->inStock);
        $this->assertSame($variant->uuid, $data->variantUuid);
        $this->assertNull($data->imageUrl);
        $this->assertSame(route('storefront.shop.index'), $data->shopUrl);
    }

    public function test_converts_price_in_the_builder_not_the_view(): void
    {
        $variant = $this->createPurchasableProduct(price: 2000, stock: 2, sku: 'PDP-FX-1');

        $cart = $this->createMock(CartServiceInterface::class);
        $cart->method('get')->willReturn(new CartData(
            currency: 'EUR',
            lines: [],
            subtotal: 0,
            itemCount: 0,
        ));

        $converter = $this->createMock(CurrencyConverterInterface::class);
        $converter->method('baseCurrency')->willReturn('USD');
        $converter->method('convert')->with(2000, 'USD', 'EUR')->willReturn(1840);

        $this->app->instance(CartServiceInterface::class, $cart);
        $this->app->instance(CurrencyConverterInterface::class, $converter);
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame(1840, $data->price);
        $this->assertSame('EUR', $data->displayCurrency);
    }

    public function test_primary_image_url_comes_from_media_query(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 1, sku: 'PDP-IMG-1');
        $mediaUuid = 'media-pdp-primary';

        ProductMedia::query()->create([
            'product_id' => $variant->product->id,
            'media_uuid' => $mediaUuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->app->instance(MediaQueryServiceInterface::class, new class($mediaUuid) implements MediaQueryServiceInterface
        {
            public function __construct(private readonly string $uuid) {}

            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/pdp.jpg' : null;
            }

            public function getSrcset(string $uuid): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/pdp.jpg 800w' : null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }
        });
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame('https://cdn.example.test/pdp.jpg', $data->imageUrl);
        $this->assertNotEmpty($data->gallery);
        $this->assertSame('https://cdn.example.test/pdp.jpg', $data->gallery[0]['url']);
    }

    public function test_inventory_failure_fail_softs_to_in_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 1100, stock: 4, sku: 'PDP-INV-1');

        $inventory = $this->createMock(InventoryQueryServiceInterface::class);
        $inventory->method('availabilityForPurchasable')->willThrowException(new RuntimeException('inventory down'));

        $this->app->instance(InventoryQueryServiceInterface::class, $inventory);
        $this->app->forgetInstance(ProductDetailBuilder::class);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertNull($data->available);
        $this->assertTrue($data->inStock);
        $this->assertNull($data->variants[0]['available']);
        $this->assertTrue($data->variants[0]['in_stock']);
    }

    public function test_zero_stock_is_out_of_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 1100, stock: 1, sku: 'PDP-OOS-1');
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame(0, $data->available);
        $this->assertFalse($data->inStock);
    }

    public function test_untracked_stock_is_purchasable_with_unknown_availability(): void
    {
        $variant = $this->createPurchasableProduct(price: 1100, stock: 1, sku: 'PDP-UNTRACKED-1');
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);
        $variant->update(['track_inventory' => false]);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertNull($data->available);
        $this->assertTrue($data->inStock);
        $this->assertNull($data->variants[0]['available']);
        $this->assertTrue($data->variants[0]['in_stock']);
    }

    public function test_allow_backorder_with_zero_stock_is_in_stock(): void
    {
        $variant = $this->createPurchasableProduct(price: 1100, stock: 1, sku: 'PDP-ALLOW-1');
        app(InventoryServiceInterface::class)->setOnHand($variant->uuid, 0);
        $variant->product->update(['backorder_policy' => 'allow']);

        $data = app(ProductDetailBuilder::class)->fromSlug($variant->product->slug);

        $this->assertNotNull($data);
        $this->assertSame(0, $data->available);
        $this->assertTrue($data->inStock);
        $this->assertSame(0, $data->variants[0]['available']);
        $this->assertTrue($data->variants[0]['in_stock']);
    }

    public function test_default_can_be_out_of_stock_while_sibling_variant_is_in_stock(): void
    {
        $default = $this->createPurchasableProduct(price: 1100, stock: 1, sku: 'PDP-MULTI-OOS');
        app(InventoryServiceInterface::class)->setOnHand($default->uuid, 0);

        $size = Attribute::query()->create([
            'code' => 'size-'.uniqid(),
            'name' => 'Size',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $small = $this->createAttributeValue($size, 'Small', 0);
        $large = $this->createAttributeValue($size, 'Large', 1);

        $product = $default->product;
        $product->update(['type' => 'variable']);
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $size->id,
            'used_for_variations' => true,
            'position' => 0,
        ]);
        $this->attachVariantValue($product, $default, $size, $small);

        $sibling = $product->variants()->create([
            'tenant_id' => $default->tenant_id,
            'sku' => 'PDP-MULTI-IN',
            'track_inventory' => true,
            'name' => 'In-stock sibling',
            'price' => 1100,
            'is_default' => false,
            'position' => 1,
        ]);
        $this->attachVariantValue($product, $sibling, $size, $large);
        app(InventoryServiceInterface::class)->receive($sibling->uuid, 3);

        $data = app(ProductDetailBuilder::class)->fromSlug($product->fresh()->slug);

        $this->assertNotNull($data);
        $this->assertFalse($data->inStock);
        $this->assertFalse($data->variants[0]['in_stock']);
        $this->assertTrue($data->variants[1]['in_stock']);
        $this->assertSame('Large', $data->variants[1]['options']['size'] ?? null);
    }

    public function test_axes_and_variant_options_come_from_relations_not_json(): void
    {
        $setup = $this->variableProductWithColorSizeMaterial();

        $data = app(ProductDetailBuilder::class)->fromSlug($setup['product']->slug);

        $this->assertNotNull($data);
        $this->assertSame($setup['blueM']->uuid, $data->variantUuid);
        $this->assertSame(
            [
                ['key' => 'color', 'name' => 'Color', 'values' => ['Red', 'Blue']],
                ['key' => 'size', 'name' => 'Size', 'values' => ['S', 'M']],
            ],
            $data->variantAxes,
        );

        $bySku = collect($data->variants)->keyBy('sku');
        $this->assertSame(
            ['color' => 'Blue', 'size' => 'M'],
            $bySku['BLUE-M']['options'],
        );
        $this->assertSame(
            ['color' => 'Red', 'size' => 'S'],
            $bySku['RED-S']['options'],
        );
        $this->assertArrayNotHasKey('green', $bySku['BLUE-M']['options']);
        $this->assertNotContains('Green', $data->variantAxes[0]['values']);
        $this->assertNotContains('Purple', $bySku['BLUE-M']['options']);
    }

    public function test_spec_list_uses_visible_non_axis_and_selected_variant_values(): void
    {
        $setup = $this->variableProductWithColorSizeMaterial();

        $data = app(ProductDetailBuilder::class)->fromSlug($setup['product']->slug);

        $this->assertNotNull($data);
        $this->assertContains(
            ['label' => 'Material', 'values' => ['Cotton']],
            $data->attributes,
        );
        $this->assertContains(
            ['label' => 'Color', 'values' => ['Blue']],
            $data->attributes,
        );
        $this->assertContains(
            ['label' => 'Size', 'values' => ['M']],
            $data->attributes,
        );
        $this->assertNotContains(
            ['label' => 'Color', 'values' => ['Red']],
            $data->attributes,
        );
        $this->assertNotContains(
            ['label' => 'From JSON', 'values' => ['Should be ignored']],
            $data->attributes,
        );
    }

    public function test_spec_list_collects_multiple_non_axis_values(): void
    {
        $variant = $this->createPurchasableProduct(sku: 'PDP-MULTI-SPEC');
        $product = $variant->product;
        $color = Attribute::query()->create([
            'code' => 'spec-color-'.uniqid(),
            'name' => 'สี',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $blue = $this->createAttributeValue($color, 'สีฟ้า', 0);
        $gray = $this->createAttributeValue($color, 'สีเทา', 1);
        $this->attachMembership($product, $color, $blue);
        $this->attachMembership($product, $color, $gray);

        $data = app(ProductDetailBuilder::class)->fromSlug($product->slug);

        $this->assertNotNull($data);
        $spec = collect($data->attributes)->firstWhere('label', 'สี');
        $this->assertIsArray($spec);
        $this->assertSame(['สีฟ้า', 'สีเทา'], $spec['values']);
        $this->assertArrayNotHasKey('value', $spec);
    }

    public function test_missing_blue_s_is_disabled_while_oos_blue_m_stays_selectable(): void
    {
        $setup = $this->variableProductWithColorSizeMaterial();
        app(InventoryServiceInterface::class)->setOnHand($setup['blueM']->uuid, 0);

        $data = app(ProductDetailBuilder::class)->fromSlug($setup['product']->fresh()->slug);

        $this->assertNotNull($data);
        $blueM = collect($data->variants)->firstWhere('sku', 'BLUE-M');
        $this->assertNotNull($blueM);
        $this->assertFalse($blueM['in_stock']);
        $this->assertSame(['color' => 'Blue', 'size' => 'M'], $blueM['options']);

        $html = view('components.storefront.forms.variant-axis-selector', [
            'axes' => $data->variantAxes,
            'variants' => $data->variants,
            'selectedUuid' => $data->variantUuid,
        ])->render();

        $this->assertFalse($this->axisButtonDisabled($html, 'size', 'M'));
        $this->assertTrue($this->axisButtonDisabled($html, 'size', 'S'));
        $this->assertFalse($this->axisButtonDisabled($html, 'color', 'Blue'));
        $this->assertTrue($this->axisButtonDisabled($html, 'color', 'Red'));
    }

    /**
     * @return array{
     *     product: Product,
     *     color: Attribute,
     *     size: Attribute,
     *     material: Attribute,
     *     red: AttributeValue,
     *     blue: AttributeValue,
     *     small: AttributeValue,
     *     medium: AttributeValue,
     *     cotton: AttributeValue,
     *     redS: ProductVariant,
     *     blueM: ProductVariant
     * }
     */
    private function variableProductWithColorSizeMaterial(): array
    {
        $color = Attribute::query()->create([
            'code' => 'color-'.uniqid(),
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $size = Attribute::query()->create([
            'code' => 'size-'.uniqid(),
            'name' => 'Size',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $material = Attribute::query()->create([
            'code' => 'material-'.uniqid(),
            'name' => 'Material',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);

        $set = AttributeSet::query()->create([
            'code' => 'apparel-'.uniqid(),
            'name' => 'Apparel',
        ]);
        $set->attributes()->attach([
            $color->id => ['position' => 0, 'is_required' => false],
            $size->id => ['position' => 1, 'is_required' => false],
            $material->id => ['position' => 2, 'is_required' => false],
        ]);

        $red = $this->createAttributeValue($color, 'Red', 0);
        $blue = $this->createAttributeValue($color, 'Blue', 1);
        $small = $this->createAttributeValue($size, 'S', 0);
        $medium = $this->createAttributeValue($size, 'M', 1);
        $cotton = $this->createAttributeValue($material, 'Cotton', 0);

        $redS = $this->createPurchasableProduct(sku: 'RED-S');
        $product = $redS->product;
        $product->update([
            'type' => 'variable',
            'attribute_set_id' => $set->id,
            'meta' => [
                'variant_options' => [
                    ['name' => 'Color', 'values' => ['Green']],
                ],
                'specifications' => [
                    ['label' => 'From JSON', 'value' => 'Should be ignored'],
                    ['label' => 'Color', 'value' => 'Red'],
                ],
            ],
        ]);
        $redS->update([
            'sku' => 'RED-S',
            'is_default' => false,
            'meta' => ['options' => ['Color' => 'Purple', 'Size' => 'XL']],
        ]);

        $blueM = app(ProductServiceInterface::class)->addVariant(new CreateVariantData(
            productUuid: $product->uuid,
            sku: 'BLUE-M',
            name: 'Blue M',
            price: 2500,
            isDefault: true,
            position: 1,
        ));
        $blueM->update([
            'meta' => ['options' => ['Color' => 'Purple', 'Size' => 'XL']],
        ]);
        app(InventoryServiceInterface::class)->receive($blueM->uuid, 4);

        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'used_for_variations' => true,
            'position' => 0,
        ]);
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $size->id,
            'used_for_variations' => true,
            'position' => 1,
        ]);
        ProductAttribute::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $material->id,
            'used_for_variations' => false,
            'position' => 2,
        ]);

        $this->attachMembership($product, $color, $red);
        $this->attachMembership($product, $color, $blue);
        $this->attachMembership($product, $size, $small);
        $this->attachMembership($product, $size, $medium);
        $this->attachMembership($product, $material, $cotton);

        $this->attachVariantValue($product, $redS, $color, $red);
        $this->attachVariantValue($product, $redS, $size, $small);
        $this->attachVariantValue($product, $blueM, $color, $blue);
        $this->attachVariantValue($product, $blueM, $size, $medium);

        return [
            'product' => $product->fresh(),
            'color' => $color,
            'size' => $size,
            'material' => $material,
            'red' => $red,
            'blue' => $blue,
            'small' => $small,
            'medium' => $medium,
            'cotton' => $cotton,
            'redS' => $redS->fresh(),
            'blueM' => $blueM->fresh(),
        ];
    }

    private function createAttributeValue(Attribute $attribute, string $label, int $position): AttributeValue
    {
        return AttributeValue::query()->create([
            'tenant_id' => $attribute->tenant_id,
            'attribute_id' => $attribute->id,
            'code' => app(AttributeValueService::class)->allocateCode($attribute->id, $label),
            'label' => $label,
            'position' => $position,
        ]);
    }

    private function attachMembership(Product $product, Attribute $attribute, AttributeValue $value): void
    {
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => null,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }

    private function attachVariantValue(
        Product $product,
        ProductVariant $variant,
        Attribute $attribute,
        AttributeValue $value,
    ): void {
        ProductAttributeValue::query()->create([
            'product_id' => $product->id,
            'attribute_id' => $attribute->id,
            'product_variant_id' => $variant->id,
            'attribute_value_id' => $value->id,
            'value' => $value->label,
        ]);
    }

    private function axisButtonDisabled(string $html, string $axisKey, string $value): bool
    {
        $document = new DOMDocument;
        $this->assertTrue(
            @$document->loadHTML($html),
            'Selector HTML should parse.',
        );
        $xpath = new DOMXPath($document);
        $buttons = $xpath->query(sprintf(
            '//button[@data-axis-key="%s" and @data-axis-value="%s"]',
            $axisKey,
            $value,
        ));
        $this->assertNotFalse($buttons);
        $this->assertGreaterThan(0, $buttons->length, "Missing {$axisKey}={$value} button");

        return $buttons->item(0)?->hasAttribute('disabled') === true;
    }
}
