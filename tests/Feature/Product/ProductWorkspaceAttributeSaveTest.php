<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\AttributeValue;
use Commerce\Catalog\Services\AttributeValueService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttribute;
use Commerce\Product\Models\ProductAttributeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductWorkspaceAttributeSaveTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->user = User::query()->firstOrFail();
    }

    public function test_simple_product_with_attribute_set_persists_rows_without_values_or_variant_identity(): void
    {
        [$set, $color, $material] = $this->apparelSet();

        $this->createWorkspace([
            'product' => $this->productPayload('Simple Spec Shirt', [
                'type' => 'simple',
                'attributeSetId' => $set->id,
                'sku' => 'SIMPLE-SPEC',
                'price' => '20',
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => true,
                        'valueIds' => [],
                    ],
                    [
                        'attributeId' => $material->id,
                        'usedForVariations' => false,
                        'valueIds' => [],
                    ],
                ],
            ]),
            'variants' => [],
        ])->assertCreated();

        $product = Product::query()->where('name', 'Simple Spec Shirt')->firstOrFail();
        $rows = $product->productAttributes()->orderBy('position')->get();

        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(
            [$color->id, $material->id],
            $rows->pluck('attribute_id')->all(),
        );
        $this->assertTrue($rows->every(static fn (ProductAttribute $row): bool => $row->used_for_variations === false));
        $this->assertSame(0, ProductAttributeValue::query()->where('product_id', $product->id)->count());

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        $this->assertSame(0, ProductAttributeValue::query()
            ->where('product_variant_id', $variant->id)
            ->count());
        $this->assertArrayNotHasKey('options', $variant->meta ?? []);

        $state = $this->actingAs($this->user)
            ->getJson(route('api.v1.admin.products.workspace.show', $product->uuid))
            ->assertOk()
            ->json('data.workspace');

        $this->assertSame([], $state['variants'][0]['options'] ?? []);
        $hydratedIds = array_map(
            static fn (array $row): int => (int) $row['attributeId'],
            $state['productAttributes'] ?? [],
        );
        $this->assertEqualsCanonicalizing([$color->id, $material->id], $hydratedIds);
        $this->assertTrue(collect($state['productAttributes'] ?? [])->every(
            static fn (array $row): bool => ($row['usedForVariations'] ?? true) === false,
        ));
    }

    public function test_adding_burgundy_via_payload_creates_unique_attribute_value_code(): void
    {
        [$set, $color] = $this->apparelSet();

        $this->createWorkspace([
            'product' => $this->productPayload('Burgundy Tee', [
                'type' => 'simple',
                'attributeSetId' => $set->id,
                'sku' => 'BURGUNDY-TEE',
                'price' => '15',
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => false,
                        'valueIds' => [],
                        'newLabels' => ['Burgundy'],
                    ],
                ],
            ]),
            'variants' => [],
        ])->assertCreated();

        $created = AttributeValue::query()
            ->where('attribute_id', $color->id)
            ->where('label', 'Burgundy')
            ->first();

        $this->assertNotNull($created);
        $this->assertContains($created->code, ['burgundy', 'burgundy-2']);

        $product = Product::query()->where('name', 'Burgundy Tee')->firstOrFail();
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id,
            'attribute_id' => $color->id,
            'attribute_value_id' => $created->id,
            'product_variant_id' => null,
        ]);

        $this->createWorkspace([
            'product' => $this->productPayload('Second Burgundy Tee', [
                'type' => 'simple',
                'attributeSetId' => $set->id,
                'sku' => 'BURGUNDY-TEE-2',
                'price' => '15',
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => false,
                        'valueIds' => [$created->id],
                    ],
                ],
            ]),
            'variants' => [],
        ])->assertCreated();

        $second = Product::query()->where('name', 'Second Burgundy Tee')->firstOrFail();
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $second->id,
            'attribute_id' => $color->id,
            'attribute_value_id' => $created->id,
            'product_variant_id' => null,
        ]);

        $codes = AttributeValue::query()
            ->where('attribute_id', $color->id)
            ->pluck('code')
            ->all();
        $this->assertSame($codes, array_values(array_unique($codes)));
    }

    public function test_draft_variable_without_generate_succeeds_and_published_is_rejected_until_identity_exists(): void
    {
        [$set, $color] = $this->apparelSet();
        $red = $this->createAttributeValue($color, 'Red', 0);

        $draftPayload = [
            'product' => $this->productPayload('Draft Variable Shirt', [
                'type' => 'variable',
                'status' => 'draft',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => true,
                        'valueIds' => [$red->id],
                    ],
                ],
            ]),
            'variants' => [],
        ];

        $this->createWorkspace($draftPayload, 'draft')->assertCreated();

        $product = Product::query()->where('name', 'Draft Variable Shirt')->firstOrFail();
        $this->assertSame('draft', $product->status);
        $this->assertSame(1, $product->variants()->count());
        $this->assertSame(0, ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereNotNull('product_variant_id')
            ->count());

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Draft Variable Shirt', [
                'type' => 'variable',
                'status' => 'published',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => true,
                        'valueIds' => [$red->id],
                    ],
                ],
            ]),
            'variants' => [$this->variantPayload(
                (string) $product->defaultVariant()?->sku,
                [],
                $product->defaultVariant()?->uuid,
            )],
        ], 'published')->assertUnprocessable();

        $this->assertSame('draft', $product->fresh()->status);

        $this->updateWorkspace($product, [
            'product' => $this->productPayload('Draft Variable Shirt', [
                'type' => 'variable',
                'status' => 'published',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => true,
                        'valueIds' => [$red->id],
                    ],
                ],
            ]),
            'generateVariants' => true,
            'variants' => [$this->variantPayload(
                (string) $product->defaultVariant()?->sku,
                [],
                $product->defaultVariant()?->uuid,
            )],
        ], 'published')->assertOk();

        $product = $product->fresh();
        $this->assertSame('published', $product->status);
        $identified = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereNotNull('product_variant_id')
            ->where('attribute_id', $color->id)
            ->where('attribute_value_id', $red->id)
            ->exists();
        $this->assertTrue($identified);
    }

    public function test_used_for_variations_on_non_select_attribute_is_rejected(): void
    {
        $material = Attribute::query()->create([
            'code' => 'care-'.uniqid(),
            'name' => 'Care',
            'type' => 'text',
            'is_filterable' => false,
            'is_visible' => true,
            'options' => [],
        ]);
        $set = AttributeSet::query()->create([
            'code' => 'care-set-'.uniqid(),
            'name' => 'Care Set',
        ]);
        $set->attributes()->attach([$material->id => ['position' => 0, 'is_required' => false]]);

        $this->createWorkspace([
            'product' => $this->productPayload('Invalid Axis Product', [
                'type' => 'variable',
                'status' => 'draft',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'productAttributes' => [
                    [
                        'attributeId' => $material->id,
                        'usedForVariations' => true,
                        'valueIds' => [],
                    ],
                ],
            ]),
            'variants' => [$this->variantPayload('INVALID-AXIS')],
        ], 'draft')->assertUnprocessable();

        $this->assertDatabaseMissing('products', ['name' => 'Invalid Axis Product']);
    }

    public function test_save_does_not_write_variant_json_or_invoke_provisioner(): void
    {
        $this->createWorkspace([
            'product' => $this->productPayload('No Json Options', [
                'type' => 'variable',
                'trackInventory' => false,
            ]),
            'options' => [
                ['id' => 'color', 'name' => 'MysteryAxis', 'values' => ['Neon']],
            ],
            'variants' => [
                $this->variantPayload('NO-JSON-1', ['MysteryAxis' => 'Neon']),
            ],
        ])->assertCreated();

        $product = Product::query()->where('name', 'No Json Options')->firstOrFail();
        $meta = $product->meta ?? [];
        $this->assertArrayNotHasKey('variant_options', $meta);
        $this->assertFalse(array_key_exists('options', $product->defaultVariant()?->meta ?? []));
        $this->assertDatabaseMissing('attributes', ['name' => 'MysteryAxis']);
        $this->assertDatabaseMissing('attribute_sets', ['code' => 'variant_options']);
    }

    public function test_generate_fills_new_blank_skus_from_attribute_value_codes(): void
    {
        [$set, $color, $size] = $this->apparelSet(withSize: true);
        $red = $this->createAttributeValue($color, 'Red', 0);
        $small = $this->createAttributeValue($size, 'S', 0);

        $this->createWorkspace([
            'product' => $this->productPayload('Coded Variable', [
                'type' => 'variable',
                'status' => 'draft',
                'attributeSetId' => $set->id,
                'trackInventory' => false,
                'sku' => 'SHIRT',
                'productAttributes' => [
                    [
                        'attributeId' => $color->id,
                        'usedForVariations' => true,
                        'valueIds' => [$red->id],
                    ],
                    [
                        'attributeId' => $size->id,
                        'usedForVariations' => true,
                        'valueIds' => [$small->id],
                    ],
                ],
            ]),
            'generateVariants' => true,
            'variants' => [],
        ], 'draft')->assertCreated();

        $product = Product::query()->where('name', 'Coded Variable')->firstOrFail();
        $skus = $product->variants()->orderBy('id')->pluck('sku')->all();
        $this->assertContains('SHIRT-RED-S', $skus);

        $created = $product->variants()->where('sku', 'SHIRT-RED-S')->firstOrFail();
        $this->assertTrue((bool) $created->sku_is_auto);
        $this->assertArrayNotHasKey('options', $created->meta ?? []);
    }

    /**
     * @return array{0: AttributeSet, 1: Attribute, 2?: Attribute}
     */
    private function apparelSet(bool $withSize = false): array
    {
        $color = Attribute::query()->create([
            'code' => 'color-'.uniqid(),
            'name' => 'Color',
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
        $size = null;
        if ($withSize) {
            $size = Attribute::query()->create([
                'code' => 'size-'.uniqid(),
                'name' => 'Size',
                'type' => 'select',
                'is_filterable' => true,
                'is_visible' => true,
                'options' => [],
            ]);
        }

        $set = AttributeSet::query()->create([
            'code' => 'apparel-'.uniqid(),
            'name' => 'Apparel',
        ]);
        $attach = [
            $color->id => ['position' => 0, 'is_required' => false],
            $material->id => ['position' => 1, 'is_required' => false],
        ];
        if ($size !== null) {
            $attach[$size->id] = ['position' => 2, 'is_required' => false];
        }
        $set->attributes()->attach($attach);

        return $size === null
            ? [$set, $color, $material]
            : [$set, $color, $size];
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
     * @param  array<string, string>  $options
     * @return array<string, mixed>
     */
    private function variantPayload(
        string $sku,
        array $options = [],
        ?string $uuid = null,
        ?int $onHand = null,
    ): array {
        return array_filter([
            'uuid' => $uuid,
            'name' => 'Variant '.$sku,
            'sku' => $sku,
            'price' => '10',
            'status' => 'active',
            'options' => $options,
            'isDefault' => false,
            'onHand' => $onHand,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
