<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Cart\DTO\ShopFilterCatalog;
use Commerce\Cart\DTO\ShopListingFilters;
use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Cart\Services\ShopProductQuery;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogV2Phase1RegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->user = User::query()->firstOrFail();
    }

    public function test_simple_create_save_reload_and_non_axis_filter(): void
    {
        [$set, $material] = $this->materialSet();
        $cotton = $this->createAttributeValue($material, 'Cotton', 0);

        $this->createWorkspace([
            'product' => $this->productPayload('Phase1 Simple Tee', [
                'type' => 'simple',
                'attributeSetId' => $set->id,
                'sku' => 'P1-SIMPLE',
                'price' => '20',
                'productAttributes' => [[
                    'attributeId' => $material->id,
                    'usedForVariations' => false,
                    'valueIds' => [$cotton->id],
                ]],
            ]),
            'variants' => [],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Phase1 Simple Tee')->firstOrFail();
        $this->assertSame('simple', $product->type);
        $this->assertSame('published', $product->status);
        $this->assertArrayNotHasKey('variant_options', $product->meta ?? []);
        $this->assertArrayNotHasKey('specifications', $product->meta ?? []);
        $this->assertArrayNotHasKey('options', $product->defaultVariant()?->meta ?? []);

        $product->update([
            'meta' => array_merge($product->meta ?? [], [
                'specifications' => [['label' => 'From JSON', 'value' => 'Linen']],
            ]),
        ]);

        $state = $this->editPageState($product);
        $this->assertSame('simple', $state['product']['type'] ?? null);
        $this->assertSame([$cotton->id], $state['productAttributes'][0]['valueIds'] ?? []);
        $this->assertSame(1, $product->variants()->count());

        $results = app(ShopProductQuery::class)->paginate(
            new ShopListingFilters(attributes: [
                $material->code => $cotton->code,
            ]),
            new ShopFilterCatalog,
        );
        $this->assertTrue(
            $results->getCollection()->contains(
                static fn (Product $row): bool => $row->id === $product->id,
            ),
        );
    }

    public function test_variable_generate_reload_pdp_filter_publish_and_scheduled(): void
    {
        [$set, $color] = $this->colorSet();
        $red = $this->createAttributeValue($color, 'Red', 0);
        $blue = $this->createAttributeValue($color, 'Blue', 1);
        $axis = [[
            'attributeId' => $color->id,
            'usedForVariations' => true,
            'valueIds' => [$red->id, $blue->id],
        ]];

        $this->createWorkspace([
            'product' => $this->productPayload('Phase1 Variable Shirt', [
                'type' => 'variable',
                'status' => 'draft',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'P1-VAR',
                'productAttributes' => $axis,
            ]),
            'variants' => [],
        ], 'draft')->assertCreated();

        $product = Product::query()->where('name', 'Phase1 Variable Shirt')->firstOrFail();
        $this->assertSame('draft', $product->status);

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Phase1 Variable Shirt', [
                'type' => 'variable',
                'status' => 'draft',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'P1-VAR',
                'productAttributes' => $axis,
            ]),
            'generateVariants' => true,
            'variants' => [$this->variantPayload(
                (string) $product->defaultVariant()?->sku,
                [],
                $product->defaultVariant()?->uuid,
            )],
        ], 'draft')->assertOk();

        $product = $product->fresh(['variants']);
        $this->assertGreaterThanOrEqual(2, $product->variants->count());
        $this->assertTrue(
            ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->whereNotNull('product_variant_id')
                ->where('attribute_value_id', $red->id)
                ->exists(),
        );
        foreach ($product->variants as $variant) {
            $this->assertArrayNotHasKey('options', $variant->meta ?? []);
        }

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Phase1 Variable Shirt', [
                'type' => 'variable',
                'status' => 'draft',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'P1-VAR',
                'productAttributes' => $axis,
            ]),
            'variants' => $product->variants->map(
                fn ($variant) => $this->variantPayload(
                    (string) $variant->sku,
                    [],
                    $variant->uuid,
                    $this->variantValueIds($variant->id, $color->id),
                ),
            )->all(),
        ], 'draft')->assertOk();

        $product = $product->fresh(['variants']);
        $this->assertArrayNotHasKey('variant_options', $product->meta ?? []);
        $this->assertArrayNotHasKey('specifications', $product->meta ?? []);

        $product->update([
            'meta' => array_merge($product->meta ?? [], [
                'variant_options' => [['name' => 'Color', 'values' => ['Green']]],
                'specifications' => [['label' => 'Color', 'value' => 'Green']],
            ]),
        ]);
        $product->variants()->update([
            'meta' => ['options' => ['Color' => 'Green']],
        ]);

        $state = $this->editPageState($product);
        $optionValues = array_values(array_filter(array_map(
            static fn (array $row): ?string => $row['options']['Color'] ?? null,
            $state['variants'] ?? [],
        )));
        $this->assertContains('Red', $optionValues);
        $this->assertContains('Blue', $optionValues);
        $this->assertNotContains('Green', $optionValues);
        $this->assertSame('draft', $state['product']['status'] ?? null);

        $this->actingAs($this->user)
            ->post(route('admin.products.publish', $product->uuid))
            ->assertRedirect(route('admin.products.edit', $product->uuid));

        $product = $product->fresh();
        $this->assertSame('published', $product->status);

        $reload = $this->editPageState($product);
        $this->assertSame('published', $reload['product']['status'] ?? null);
        $this->assertContains('Red', array_values(array_filter(array_map(
            static fn (array $row): ?string => $row['options']['Color'] ?? null,
            $reload['variants'] ?? [],
        ))));

        $pdp = app(ProductDetailBuilder::class)->fromSlug($product->slug);
        $this->assertNotNull($pdp);
        $pdpColors = $pdp->variantAxes[0]['values'] ?? [];
        $this->assertContains('Red', $pdpColors);
        $this->assertContains('Blue', $pdpColors);
        $this->assertNotContains('Green', $pdpColors);

        $this->get(route('storefront.shop.index', [$color->code => 'red']))
            ->assertOk()
            ->assertSee($product->name);
        $this->get(route('storefront.shop.index', [$color->code => 'green']))
            ->assertOk()
            ->assertDontSee($product->name);

        $this->createWorkspace([
            'product' => $this->productPayload('Phase1 Scheduled Shirt', [
                'type' => 'variable',
                'status' => 'scheduled',
                'publishAt' => now()->addDay()->format('Y-m-d\TH:i'),
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'P1-SCHED',
                'productAttributes' => [[
                    'attributeId' => $color->id,
                    'usedForVariations' => true,
                    'valueIds' => [$red->id],
                ]],
            ]),
            'generateVariants' => true,
            'variants' => [],
        ], 'scheduled')->assertCreated();

        $scheduled = Product::query()->where('name', 'Phase1 Scheduled Shirt')->firstOrFail();
        $this->assertSame('scheduled', $scheduled->status);
        $this->assertNotNull($scheduled->publish_at);

        $scheduledState = $this->editPageState($scheduled);
        $this->assertSame('scheduled', $scheduledState['product']['status'] ?? null);
        $this->assertNotSame('', $scheduledState['product']['publishAt'] ?? '');
        $this->assertSame('Red', $scheduledState['variants'][0]['options']['Color'] ?? null);

        $this->assertNull(app(ProductDetailBuilder::class)->fromSlug($scheduled->slug));
        $this->get(route('storefront.shop.index', [$color->code => 'red']))
            ->assertOk()
            ->assertDontSee($scheduled->name);

        $scheduled->update(['publish_at' => now()->subMinute()]);
        $this->assertSame(1, app(ProductServiceInterface::class)->publishScheduled());
        $scheduled = $scheduled->fresh();
        $this->assertSame('published', $scheduled->status);

        $this->assertNotNull(app(ProductDetailBuilder::class)->fromSlug($scheduled->slug));
        $this->get(route('storefront.shop.index', [$color->code => 'red']))
            ->assertOk()
            ->assertSee($scheduled->name);
    }

    /**
     * @return array<string, mixed>
     */
    private function editPageState(Product $product): array
    {
        $html = $this->actingAs($this->user)
            ->get(route('admin.products.edit', $product->uuid))
            ->assertOk()
            ->assertSee('data-product-workspace-state', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<script type="application\/json" data-product-workspace-state>/',
            $html,
        );
        preg_match(
            '/<script type="application\/json" data-product-workspace-state>\s*(.*?)\s*<\/script>/s',
            $html,
            $matches,
        );
        $this->assertNotSame('', $matches[1] ?? '');
        $state = json_decode($matches[1], true);
        $this->assertIsArray($state);

        return $state;
    }

    /**
     * @return list<int>
     */
    private function variantValueIds(int $variantId, int $attributeId): array
    {
        return ProductAttributeValue::query()
            ->where('product_variant_id', $variantId)
            ->where('attribute_id', $attributeId)
            ->whereNotNull('attribute_value_id')
            ->pluck('attribute_value_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array{0: AttributeSet, 1: Attribute}
     */
    private function colorSet(): array
    {
        $color = Attribute::query()->create([
            'code' => 'color-p1-'.uniqid(),
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $set = AttributeSet::query()->create([
            'code' => 'p1-color-'.uniqid(),
            'name' => 'Phase1 Color',
        ]);
        $set->attributes()->attach([$color->id => ['position' => 0, 'is_required' => false]]);

        return [$set, $color];
    }

    /**
     * @return array{0: AttributeSet, 1: Attribute}
     */
    private function materialSet(): array
    {
        $material = Attribute::query()->create([
            'code' => 'material-p1-'.uniqid(),
            'name' => 'Material',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $set = AttributeSet::query()->create([
            'code' => 'p1-material-'.uniqid(),
            'name' => 'Phase1 Material',
        ]);
        $set->attributes()->attach([$material->id => ['position' => 0, 'is_required' => false]]);

        return [$set, $material];
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

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function createWorkspace(array $workspace, string $status = 'published')
    {
        return $this->actingAs($this->user)->postJson(
            route('api.v1.admin.products.workspace.store'),
            $this->requestPayload($workspace, $status),
        );
    }

    /**
     * @param  array<string, mixed>  $workspace
     */
    private function updateWorkspace(Product $product, array $workspace, string $status = 'published')
    {
        return $this->actingAs($this->user)->putJson(
            route('api.v1.admin.products.workspace.update', $product->uuid),
            $this->requestPayload($workspace, $status),
        );
    }

    /**
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    private function requestPayload(array $workspace, string $status = 'published'): array
    {
        $payload = [
            'name' => $workspace['product']['name'],
            'status' => $workspace['product']['status'] ?? $status,
            'visibility' => 'public',
            'attribute_set_id' => $workspace['product']['attributeSetId'] ?? null,
            'workspace_payload' => array_merge([
                'options' => [],
                'variants' => [],
                'media' => ['productUuids' => []],
            ], $workspace),
        ];

        if (($workspace['product']['status'] ?? $status) === 'scheduled') {
            $payload['publish_at'] = $workspace['product']['publishAt'] ?? now()->addDay()->format('Y-m-d\TH:i');
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function productPayload(string $name, array $overrides = []): array
    {
        return array_merge([
            'name' => $name,
            'status' => 'published',
            'visibility' => 'public',
            'type' => 'simple',
            'backorderPolicy' => 'deny',
            'trackInventory' => false,
        ], $overrides);
    }

    /**
     * @param  list<int>  $valueIds
     * @return array<string, mixed>
     */
    private function variantPayload(
        string $sku,
        array $options = [],
        ?string $uuid = null,
        array $valueIds = [],
    ): array {
        return array_filter([
            'uuid' => $uuid,
            'name' => 'Variant '.$sku,
            'sku' => $sku,
            'price' => '10',
            'status' => 'active',
            'options' => $options,
            'valueIds' => $valueIds === [] ? null : $valueIds,
            'isDefault' => true,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
