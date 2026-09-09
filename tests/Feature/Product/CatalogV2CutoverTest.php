<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Cart\Services\ProductDetailBuilder;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Tests\TestCase;

final class CatalogV2CutoverTest extends TestCase
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

    public function test_workspace_save_strips_leftover_variant_and_specification_json(): void
    {
        [$set, $color] = $this->colorSet();
        $red = $this->createAttributeValue($color, 'Red', 0);

        $this->createWorkspace([
            'product' => $this->productPayload('Cutover Shirt', [
                'type' => 'variable',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'CUTOVER',
                'productAttributes' => [[
                    'attributeId' => $color->id,
                    'usedForVariations' => true,
                    'valueIds' => [$red->id],
                ]],
            ]),
            'generateVariants' => true,
            'variants' => [],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Cutover Shirt')->firstOrFail();
        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);

        $product->update([
            'meta' => array_merge($product->meta ?? [], [
                'variant_options' => [
                    ['name' => 'Color', 'values' => ['Green']],
                ],
                'specifications' => [
                    ['label' => 'From JSON', 'value' => 'Should be stripped'],
                ],
            ]),
        ]);
        $variant->update([
            'meta' => array_merge($variant->meta ?? [], [
                'options' => ['Color' => 'Green'],
            ]),
        ]);

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Cutover Shirt', [
                'type' => 'variable',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'CUTOVER',
                'productAttributes' => [[
                    'attributeId' => $color->id,
                    'usedForVariations' => true,
                    'valueIds' => [$red->id],
                ]],
            ]),
            'variants' => [$this->variantPayload(
                (string) $variant->sku,
                [],
                $variant->uuid,
                [$red->id],
            )],
        ])->assertOk();

        $product = $product->fresh(['variants']);
        $this->assertArrayNotHasKey('variant_options', $product->meta ?? []);
        $this->assertArrayNotHasKey('specifications', $product->meta ?? []);
        foreach ($product->variants as $savedVariant) {
            $this->assertArrayNotHasKey('options', $savedVariant->meta ?? []);
        }
    }

    public function test_hydrate_pdp_and_shop_filter_follow_relations_not_lying_json(): void
    {
        $setup = $this->publishedRedShirtWithLyingGreenJson();
        $product = $setup['product'];

        $workspace = $this->actingAs($this->user)
            ->getJson(route('api.v1.admin.products.workspace.show', $product->uuid))
            ->assertOk()
            ->json('data.workspace');

        $this->assertSame('Red', $workspace['variants'][0]['options']['Color'] ?? null);
        $this->assertNotSame('Green', $workspace['variants'][0]['options']['Color'] ?? null);
        $this->assertSame([$setup['red']->id], $workspace['variants'][0]['valueIds'] ?? []);
        $this->assertSame([$setup['red']->id], $workspace['productAttributes'][0]['valueIds'] ?? []);

        $pdp = app(ProductDetailBuilder::class)->fromSlug($product->slug);
        $this->assertNotNull($pdp);
        $this->assertSame(['color' => 'Red'], $pdp->variants[0]['options']);
        $this->assertSame(['Red'], $pdp->variantAxes[0]['values'] ?? []);
        $this->assertNotContains('Green', $pdp->variantAxes[0]['values'] ?? []);
        $this->assertNotContains(
            ['label' => 'From JSON', 'values' => ['Should be ignored']],
            $pdp->attributes,
        );

        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($product->name);
        $this->get(route('storefront.shop.index', ['color' => 'green']))
            ->assertOk()
            ->assertDontSee($product->name);
    }

    public function test_shop_filter_matches_attribute_value_code_not_leftover_pav_value(): void
    {
        $setup = $this->publishedRedShirtWithLyingGreenJson();
        $product = $setup['product'];

        foreach (ProductAttributeValue::query()->where('product_id', $product->id)->get() as $row) {
            $row->update(['value' => 'เหลือง']);
        }

        $html = $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('value="red"', $html);
        $this->assertStringNotContainsString('value="เหลือง"', $html);

        $this->get(route('storefront.shop.index', ['color' => 'red']))
            ->assertOk()
            ->assertSee($product->name);
        $this->get(route('storefront.shop.index', ['color' => 'เหลือง']))
            ->assertOk()
            ->assertDontSee($product->name);
    }

    public function test_variant_option_attribute_provisioner_has_no_production_callers(): void
    {
        $classFile = base_path('modules/Product/src/Services/VariantOptionAttributeProvisioner.php');
        $this->assertFileDoesNotExist(
            $classFile,
            'Unused VariantOptionAttributeProvisioner should be deleted after cutover.',
        );

        $hits = $this->productionReferences('VariantOptionAttributeProvisioner');
        $this->assertSame([], $hits, "Unexpected production references:\n".implode("\n", $hits));
    }

    /**
     * @return array{product: Product, color: Attribute, red: AttributeValue}
     */
    private function publishedRedShirtWithLyingGreenJson(): array
    {
        [$set, $color] = $this->colorSet();
        $red = $this->createAttributeValue($color, 'Red', 0);

        $this->createWorkspace([
            'product' => $this->productPayload('Relation Red Shirt', [
                'type' => 'variable',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'REL-RED',
                'productAttributes' => [[
                    'attributeId' => $color->id,
                    'usedForVariations' => true,
                    'valueIds' => [$red->id],
                ]],
            ]),
            'generateVariants' => true,
            'variants' => [],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Relation Red Shirt')->firstOrFail();
        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);

        $product->update([
            'meta' => array_merge($product->meta ?? [], [
                'variant_options' => [
                    ['name' => 'Color', 'values' => ['Green']],
                ],
                'specifications' => [
                    ['label' => 'From JSON', 'value' => 'Should be ignored'],
                    ['label' => 'Color', 'value' => 'Green'],
                ],
            ]),
        ]);
        $variant->update([
            'meta' => array_merge($variant->meta ?? [], [
                'options' => ['Color' => 'Green'],
            ]),
        ]);

        return [
            'product' => $product->fresh(['variants', 'attributeValues.attribute', 'attributeValues.attributeValue', 'productAttributes.attribute']),
            'color' => $color,
            'red' => $red,
        ];
    }

    /**
     * @return array{0: AttributeSet, 1: Attribute}
     */
    private function colorSet(): array
    {
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'Color',
            'type' => 'select',
            'is_filterable' => true,
            'is_visible' => true,
            'options' => [],
        ]);
        $set = AttributeSet::query()->create([
            'code' => 'cutover-color-'.uniqid(),
            'name' => 'Cutover Color',
        ]);
        $set->attributes()->attach([$color->id => ['position' => 0, 'is_required' => false]]);

        return [$set, $color];
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
        return [
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
            'valueIds' => $valueIds,
            'isDefault' => true,
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return list<string>
     */
    private function productionReferences(string $needle): array
    {
        $roots = [base_path('modules'), base_path('app')];
        $hits = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)),
                '/\.php$/',
            );

            foreach ($iterator as $file) {
                $path = $file->getPathname();
                if (str_contains($path, '/VariantOptionAttributeProvisioner.php')) {
                    continue;
                }
                if (str_contains($path, '/CatalogVariantRelationMigrator.php')) {
                    continue;
                }

                $contents = (string) file_get_contents($path);
                if (str_contains($contents, $needle)) {
                    $hits[] = $path;
                }
            }
        }

        return $hits;
    }
}
