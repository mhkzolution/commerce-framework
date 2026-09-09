<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Collection;
use Commerce\Catalog\Models\Tag;
use Commerce\Product\Export\WooCommerceProductExporter;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Marketplace\Models\Seller;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class ProductCsvImportTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_admin_can_view_product_import_page(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.products.import.show'))
            ->assertOk()
            ->assertSee('Import products', false)
            ->assertSee('Upload CSV', false)
            ->assertSee('Download CSV template', false);
    }

    public function test_import_result_summary_shows_errors_count_tile(): void
    {
        $html = $this->actingAs(User::query()->first())
            ->withSession([
                'import_result' => [
                    'created' => 1,
                    'updated' => 0,
                    'skipped' => 0,
                    'duplicates' => 0,
                    'linked_images' => 0,
                    'warnings' => 0,
                    'messages' => ['Created: Clean Tee (SKU: CSV-RSV-CLEAN)'],
                    'duplicate_skus' => [],
                    'errors' => ['Row 16: database exception'],
                ],
            ])
            ->get(route('admin.products.import.show'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<div class="text-xs uppercase tracking-wide text-muted">Errors<\/div>\s*<div class="text-2xl font-semibold text-text">1<\/div>/',
            $html,
        );
    }

    public function test_admin_can_create_product_from_csv(): void
    {
        Http::fake([
            'https://example.com/images/product.jpg' => Http::response(
                $this->fakeJpegBytes(),
                200,
                ['Content-Type' => 'image/jpeg'],
            ),
        ]);

        $csv = $this->makeCsv([
            $this->csvRow([
                'SKU' => 'CSV-NEW-001',
                'Name' => 'Imported Tee',
                'Published' => '1',
                'Sale price' => '199',
                'Regular price' => '299',
                'Categories' => 'Kids Wear',
                'Tags' => 'Summer, Cotton',
                'Brands' => 'Carter\'s',
                'Images' => 'https://example.com/images/product.jpg',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'Blue',
            ]),
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'))
            ->assertSessionHas('import_result');

        $variant = ProductVariant::query()->where('sku', 'CSV-NEW-001')->first();

        $this->assertNotNull($variant);

        $product = $variant->product->fresh(['categories', 'tags', 'media', 'attributeValues']);

        $this->assertSame('Imported Tee', $product->name);
        $this->assertSame('simple', $product->type);
        $this->assertSame(19900, (int) $variant->price);
        $this->assertSame(29900, (int) $variant->compare_at_price);
        $this->assertNotNull($product->brand_uuid);
        $this->assertTrue($product->categories->contains(fn (Category $category): bool => $category->name === 'Kids Wear'));
        $this->assertTrue($product->tags->contains(fn (Tag $tag): bool => $tag->name === 'Summer'));
        $this->assertTrue($product->tags->contains(fn (Tag $tag): bool => $tag->name === 'Cotton'));
        $this->assertCount(1, $product->media);
        $this->assertNotEmpty($product->attributeValues);
    }

    public function test_admin_can_import_product_with_seller_column(): void
    {
        if (! Schema::hasTable('marketplace_sellers')) {
            $this->markTestSkipped('Marketplace sellers table is not available.');
        }

        $csv = $this->makeCsv([
            $this->csvRow([
                'SKU' => 'CSV-SELLER-001',
                'Name' => 'Seller Import Tee',
                'Published' => '1',
                'Sale price' => '199',
                'Regular price' => '299',
                'Seller' => 'Bangkok Corner Shop',
            ]),
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'));

        $variant = ProductVariant::query()->where('sku', 'CSV-SELLER-001')->first();
        $this->assertNotNull($variant);

        $seller = Seller::query()->where('name', 'Bangkok Corner Shop')->first();
        $this->assertNotNull($seller);
        $this->assertSame($seller->uuid, $variant->product->seller_uuid);
    }

    public function test_admin_can_import_product_with_existing_seller(): void
    {
        if (! Schema::hasTable('marketplace_sellers')) {
            $this->markTestSkipped('Marketplace sellers table is not available.');
        }

        $seller = Seller::query()->create([
            'name' => 'Acme Vendor',
            'slug' => 'acme-vendor',
            'email' => 'vendor@example.com',
            'commission_rate' => 1000,
            'status' => 'active',
        ]);

        $csv = $this->makeCsv([
            $this->csvRow([
                'SKU' => 'CSV-SELLER-002',
                'Name' => 'Linked Seller Tee',
                'Published' => '1',
                'Seller' => 'Acme Vendor',
            ]),
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'));

        $variant = ProductVariant::query()->where('sku', 'CSV-SELLER-002')->first();
        $this->assertNotNull($variant);
        $this->assertSame($seller->uuid, $variant->product->seller_uuid);
    }

    public function test_admin_can_export_product_with_seller_column(): void
    {
        if (! Schema::hasTable('marketplace_sellers')) {
            $this->markTestSkipped('Marketplace sellers table is not available.');
        }

        $seller = Seller::query()->create([
            'name' => 'Export Seller',
            'slug' => 'export-seller',
            'email' => 'export-seller@example.com',
            'commission_rate' => 500,
            'status' => 'active',
        ]);

        $variant = $this->createPurchasableProduct(price: 12000, stock: 4, sku: 'CSV-SELLER-EXP');
        $variant->product->update(['seller_uuid' => $seller->uuid]);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Seller', $content);
        $this->assertStringContainsString('Export Seller', $content);
        $this->assertStringContainsString('CSV-SELLER-EXP', $content);
        $this->assertStringContainsString('120', $content);
    }

    public function test_admin_can_update_existing_product_by_sku(): void
    {
        $variant = $this->createPurchasableProduct(price: 10000, stock: 5, sku: 'CSV-UPD-001');
        $product = $variant->product;

        $csv = $this->makeCsv([
            $this->csvRow([
                'SKU' => 'CSV-UPD-001',
                'Name' => 'Updated Product Name',
                'Published' => '1',
                'Sale price' => '150',
                'Regular price' => '250',
                'Categories' => 'Updated Category',
            ]),
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'));

        $product->refresh();
        $variant->refresh();

        $this->assertSame('Updated Product Name', $product->name);
        $this->assertSame(15000, (int) $variant->price);
        $this->assertTrue($product->categories->contains(fn (Category $category): bool => $category->name === 'Updated Category'));
    }

    public function test_import_keeps_first_duplicate_sku_and_skips_later_rows(): void
    {
        $csv = $this->makeCsv([
            $this->csvRow(['SKU' => 'CSV-DUP-001', 'Name' => 'First product']),
            $this->csvRow(['SKU' => 'CSV-DUP-001', 'Name' => 'Second product']),
        ]);

        $result = $this->importCsv($csv);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, $result['skipped']);
        $this->assertGreaterThanOrEqual(1, $result['warnings']);
        $this->assertContains('CSV-DUP-001', $result['duplicate_skus']);
        $this->assertTrue(
            collect($result['messages'])->contains(
                fn (string $message): bool => str_contains($message, 'Duplicate SKU CSV-DUP-001'),
            ),
        );

        $product = ProductVariant::query()->where('sku', 'CSV-DUP-001')->first()?->product;
        $this->assertNotNull($product);
        $this->assertSame('First product', $product->name);
        $this->assertSame(1, Product::query()->count());
    }

    public function test_admin_can_export_variable_product_with_variation_rows(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $this->variableWorkspacePayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(
            'variable',
            Product::query()->where('name', 'Export Hoodie')->value('type'),
        );

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('variable', $content);
        $this->assertStringContainsString('variation', $content);
        $this->assertStringContainsString('HOODIE-RED-S', $content);
        $this->assertStringContainsString('HOODIE-RED-M', $content);
    }

    public function test_import_creates_simple_product_when_type_is_empty(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'SKU' => 'TEE-001',
                'Type' => '',
                'Name' => 'Plain Tee',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame('simple', ProductVariant::query()->where('sku', 'TEE-001')->first()?->product->type);
    }

    public function test_import_attaches_and_creates_collections(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'SKU' => 'CSV-COL-001',
                'Name' => 'Collection Tee',
                'Collections' => 'Summer Drop',
            ]),
        ]));

        $this->assertSame(1, $result['created']);

        $product = ProductVariant::query()->where('sku', 'CSV-COL-001')->first()?->product;
        $this->assertNotNull($product);
        $product->load('collections');
        $this->assertTrue($product->collections->contains(fn (Collection $collection): bool => $collection->name === 'Summer Drop'));
    }

    public function test_admin_can_export_product_with_collections_column(): void
    {
        $variant = $this->createPurchasableProduct(price: 12000, stock: 4, sku: 'CSV-COL-EXP');
        $collection = Collection::query()->create([
            'name' => 'Export Collection',
            'slug' => 'export-collection',
        ]);
        $variant->product->collections()->attach($collection->id);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Collections', $content);
        $this->assertStringContainsString('Export Collection', $content);
        $this->assertStringContainsString('CSV-COL-EXP', $content);
    }

    public function test_admin_can_download_woocommerce_csv_template(): void
    {
        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.import.template'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));

        $firstLine = str_getcsv(strtok($response->streamedContent(), "\n") ?: '');

        $this->assertSame(app(WooCommerceProductExporter::class)->headers(), $firstLine);
    }

    public function test_import_links_variations_when_parent_is_id_reference(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '123',
                'Type' => 'variable',
                'SKU' => 'TSHIRT',
                'Name' => 'Parent Tee',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'Red,Blue',
            ]),
            $this->csvRow([
                'ID' => '',
                'Type' => 'variation',
                'SKU' => 'TSHIRT-RED',
                'Name' => 'Red',
                'Parent' => 'id:123',
                'Sale price' => '100',
                'Regular price' => '120',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'Red',
            ]),
            $this->csvRow([
                'ID' => '',
                'Type' => 'variation',
                'SKU' => 'TSHIRT-BLUE',
                'Name' => 'Blue',
                'Parent' => 'id:123',
                'Sale price' => '110',
                'Regular price' => '130',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'Blue',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);

        $product = Product::query()->where('name', 'Parent Tee')->with('variants')->firstOrFail();
        $this->assertSame('variable', $product->type);
        $this->assertCount(2, $product->variants);
        $this->assertNotNull($product->variants->firstWhere('sku', 'TSHIRT-RED'));
        $this->assertNotNull($product->variants->firstWhere('sku', 'TSHIRT-BLUE'));
    }

    public function test_admin_can_import_variable_product_from_parent_and_variation_rows(): void
    {
        $csv = $this->makeCsv([
            $this->csvRow([
                'ID' => '5000',
                'Type' => 'variable',
                'SKU' => 'HOODIE-PARENT',
                'Name' => 'Imported Hoodie',
                'Published' => '1',
                'Attribute 1 name' => 'Color',
                'Attribute 1 value(s)' => 'Red, Blue',
                'Attribute 2 name' => 'Size',
                'Attribute 2 value(s)' => 'S, M',
            ]),
            $this->csvRow([
                'Type' => 'variation',
                'Parent' => 'HOODIE-PARENT',
                'SKU' => 'HOODIE-RED-S',
                'Name' => 'Red / S',
                'Sale price' => '100',
                'Regular price' => '120',
                'Stock' => '2',
                'Attribute 1 name' => 'Color',
                'Attribute 1 value(s)' => 'Red',
                'Attribute 2 name' => 'Size',
                'Attribute 2 value(s)' => 'S',
            ]),
            $this->csvRow([
                'Type' => 'variation',
                'Parent' => 'HOODIE-PARENT',
                'SKU' => 'HOODIE-RED-M',
                'Name' => 'Red / M',
                'Sale price' => '110',
                'Regular price' => '130',
                'Stock' => '3',
                'Attribute 1 name' => 'Color',
                'Attribute 1 value(s)' => 'Red',
                'Attribute 2 name' => 'Size',
                'Attribute 2 value(s)' => 'M',
            ]),
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'))
            ->assertSessionHas('import_result');

        $product = Product::query()->where('name', 'Imported Hoodie')->with('variants')->firstOrFail();

        $this->assertSame('variable', $product->type);
        $this->assertCount(2, $product->variants);
        $redS = $product->variants->firstWhere('sku', 'HOODIE-RED-S');
        $this->assertNotNull($redS);
        $this->assertArrayNotHasKey('options', $redS->meta ?? []);
    }

    public function test_admin_can_export_products_csv(): void
    {
        $variant = $this->createPurchasableProduct(price: 15000, stock: 3, sku: 'CSV-EXP-001');
        $product = $variant->product;
        $product->update([
            'name' => 'Exportable Product',
            'status' => 'published',
            'visibility' => 'public',
            'description' => 'Short description here',
        ]);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.export'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));

        $content = $response->streamedContent();

        $this->assertStringContainsString('SKU', $content);
        $this->assertStringContainsString('CSV-EXP-001', $content);
        $this->assertStringContainsString('Exportable Product', $content);
        $this->assertStringContainsString('Short description here', $content);
        $this->assertStringContainsString('150', $content);
    }

    public function test_import_skips_reserved_brand_column_and_keeps_other_attributes(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '11',
                'SKU' => 'CSV-RSV-001',
                'Name' => 'Reserved Brand Tee',
                'Attribute 1 name' => 'Brand',
                'Attribute 1 value(s)' => 'Nike',
                'Attribute 2 name' => 'สี',
                'Attribute 2 value(s)' => 'Blue',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);
        $this->assertGreaterThanOrEqual(1, $result['warnings']);
        $this->assertContains(
            'Row 11: skipped reserved attribute column "Brand" (code "brand").',
            $result['messages'],
        );

        $product = ProductVariant::query()->where('sku', 'CSV-RSV-001')->first()?->product;
        $this->assertNotNull($product);
        $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
        $this->assertHasAttributeValue($product, 'color', 'Blue');
        $this->assertMissingAttributeCode($product, 'brand');
    }

    public function test_import_skips_price_min_slugged_attribute_column(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '12',
                'SKU' => 'CSV-RSV-002',
                'Name' => 'Price Min Tee',
                'Attribute 1 name' => 'Price Min',
                'Attribute 1 value(s)' => '100',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertContains(
            'Row 12: skipped reserved attribute column "Price Min" (code "price_min").',
            $result['messages'],
        );
        $this->assertDatabaseMissing('attributes', ['code' => 'price_min']);

        $product = ProductVariant::query()->where('sku', 'CSV-RSV-002')->first()?->product;
        $this->assertNotNull($product);
        $this->assertMissingAttributeCode($product, 'price_min');
    }

    public function test_import_still_attaches_non_reserved_color_attribute(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '13',
                'SKU' => 'CSV-RSV-003',
                'Name' => 'Color Only Tee',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'Red',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertFalse(
            collect($result['messages'])->contains(
                fn (string $message): bool => str_contains($message, 'skipped reserved attribute column'),
            ),
        );

        $product = ProductVariant::query()->where('sku', 'CSV-RSV-003')->first()?->product;
        $this->assertNotNull($product);
        $this->assertHasAttributeValue($product, 'color', 'Red');
    }

    public function test_import_writes_attribute_value_id_for_color(): void
    {
        $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '80',
                'SKU' => 'CSV-ATTR-FK-1',
                'Name' => 'Blue Tee',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'สีฟ้า, สีเทา',
            ]),
        ]));

        $product = ProductVariant::query()->where('sku', 'CSV-ATTR-FK-1')->first()?->product;
        $this->assertNotNull($product);
        $rows = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereHas('attribute', fn ($query) => $query->where('code', 'color'))
            ->get();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->every(fn (ProductAttributeValue $row): bool => $row->attribute_value_id !== null));
    }

    public function test_import_canonicalizes_size_top(): void
    {
        $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '81',
                'SKU' => 'CSV-ATTR-SIZE-1',
                'Name' => 'Sized Tee',
                'Attribute 1 name' => 'Size (เสื้อ)',
                'Attribute 1 value(s)' => '4-5 Y',
            ]),
        ]));

        $product = ProductVariant::query()->where('sku', 'CSV-ATTR-SIZE-1')->first()?->product;
        $this->assertNotNull($product);
        $row = $product->attributeValues()
            ->whereHas('attribute', fn ($query) => $query->where('code', 'size_top'))
            ->first();

        $this->assertSame('4-5Y', $row?->attributeValue?->label);
    }

    public function test_import_creates_product_when_only_reserved_attribute_column_is_present(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '14',
                'SKU' => 'CSV-RSV-004',
                'Name' => 'Brand Only Tee',
                'Attribute 1 name' => 'Brand',
                'Attribute 1 value(s)' => 'Nike',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertContains(
            'Row 14: skipped reserved attribute column "Brand" (code "brand").',
            $result['messages'],
        );
        $this->assertNotNull(ProductVariant::query()->where('sku', 'CSV-RSV-004')->first());
    }

    public function test_import_skips_reserved_column_even_when_attribute_is_preloaded_in_cache(): void
    {
        $brand = Attribute::query()->create([
            'code' => 'brand',
            'name' => 'Brand',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);
        $color = Attribute::query()->create([
            'code' => 'color',
            'name' => 'สี',
            'type' => 'text',
            'is_filterable' => true,
            'is_visible' => true,
        ]);

        $set = AttributeSet::query()->create([
            'code' => (string) config('product.import.woocommerce.attribute_set_code', 'woocommerce_default'),
            'name' => (string) config('product.import.woocommerce.attribute_set_name', 'WooCommerce Default'),
        ]);
        $set->attributes()->attach($brand->id, ['position' => 0, 'is_required' => false]);
        $set->attributes()->attach($color->id, ['position' => 1, 'is_required' => false]);

        $this->assertTrue(
            $set->fresh()->load('attributes')->attributes->contains(
                fn (Attribute $attribute): bool => $attribute->id === $brand->id,
            ),
            'Precondition: Brand must be on the WooCommerce set so importer cache-preloads it by name.',
        );

        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '15',
                'SKU' => 'CSV-RSV-005',
                'Name' => 'Legacy Brand Tee',
                'Attribute 1 name' => 'Brand',
                'Attribute 1 value(s)' => 'Nike',
                'Attribute 2 name' => 'สี',
                'Attribute 2 value(s)' => 'Green',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertContains(
            'Row 15: skipped reserved attribute column "Brand" (code "brand").',
            $result['messages'],
        );

        $product = ProductVariant::query()->where('sku', 'CSV-RSV-005')->first()?->product;
        $this->assertNotNull($product);
        $this->assertDatabaseHas('attributes', ['id' => $brand->id, 'code' => 'brand']);
        $this->assertSame(
            0,
            ProductAttributeValue::query()
                ->where('product_id', $product->id)
                ->where('attribute_id', $brand->id)
                ->count(),
        );
        $this->assertHasAttributeValue($product, 'color', 'Green');
    }

    public function test_import_still_fails_the_row_on_non_reserved_create_failure(): void
    {
        Attribute::creating(function (Attribute $attribute): void {
            if ($attribute->code === 'fabric') {
                throw new \RuntimeException('database exception');
            }
        });

        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '16',
                'SKU' => 'CSV-RSV-006',
                'Name' => 'Fabric Clash Tee',
                'Attribute 1 name' => 'Brand',
                'Attribute 1 value(s)' => 'Nike',
                'Attribute 2 name' => 'Fabric',
                'Attribute 2 value(s)' => 'Cotton',
            ]),
            $this->csvRow([
                'ID' => '17',
                'SKU' => 'CSV-RSV-CLEAN',
                'Name' => 'Clean Tee',
                'Attribute 1 name' => 'สี',
                'Attribute 1 value(s)' => 'Blue',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertNotSame([], $result['errors']);
        $this->assertNull(ProductVariant::query()->where('sku', 'CSV-RSV-006')->first());
        $this->assertNotNull(ProductVariant::query()->where('sku', 'CSV-RSV-CLEAN')->first());

        foreach ($result['messages'] as $message) {
            $this->assertStringNotContainsString('skipped reserved attribute column', $message);
        }
    }

    public function test_import_skips_reserved_column_on_variable_parent(): void
    {
        $result = $this->importCsv($this->makeCsv([
            $this->csvRow([
                'ID' => '17',
                'Type' => 'variable',
                'SKU' => 'CSV-RSV-VAR',
                'Name' => 'Variable Reserved Tee',
                'Attribute 1 name' => 'Brand',
                'Attribute 1 value(s)' => 'Nike',
                'Attribute 2 name' => 'สี',
                'Attribute 2 value(s)' => 'Blue',
            ]),
        ]));

        $this->assertSame(1, $result['created']);
        $this->assertSame([], $result['errors']);
        $this->assertContains(
            'Row 17: skipped reserved attribute column "Brand" (code "brand").',
            $result['messages'],
        );
    }

    public function test_cli_import_writes_reserved_column_comment_and_still_imports(): void
    {
        $csv = $this->makeCsv([
            $this->csvRow([
                'ID' => '18',
                'SKU' => 'CSV-RSV-CLI',
                'Name' => 'CLI Brand Tee',
                'Attribute 1 name' => 'Brand',
                'Attribute 1 value(s)' => 'Nike',
            ]),
        ]);

        $path = sys_get_temp_dir().'/wc-import-reserved-'.uniqid('', true).'.csv';
        file_put_contents($path, $csv);

        try {
            $this->artisan('product:import-woocommerce', ['file' => $path, '--force' => true])
                ->expectsOutputToContain('Row 18: skipped reserved attribute column "Brand" (code "brand").')
                ->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $this->assertNotNull(ProductVariant::query()->where('sku', 'CSV-RSV-CLI')->first());
        $this->assertDatabaseMissing('attributes', ['code' => 'brand']);
    }

    public function test_cli_import_keeps_first_duplicate_sku_even_with_force(): void
    {
        $csv = $this->makeCsv([
            $this->csvRow(['SKU' => 'CLI-DUP-001', 'Name' => 'First CLI product']),
            $this->csvRow(['SKU' => 'CLI-DUP-001', 'Name' => 'Second CLI product']),
        ]);

        $path = sys_get_temp_dir().'/wc-import-dup-'.uniqid('', true).'.csv';
        file_put_contents($path, $csv);

        try {
            $this->artisan('product:import-woocommerce', ['file' => $path, '--force' => true])
                ->expectsOutputToContain('Duplicate SKU CLI-DUP-001')
                ->assertSuccessful();
        } finally {
            @unlink($path);
        }

        $product = ProductVariant::query()->where('sku', 'CLI-DUP-001')->first()?->product;
        $this->assertNotNull($product);
        $this->assertSame('First CLI product', $product->name);
        $this->assertSame(1, Product::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function importCsv(string $csv): array
    {
        $response = $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'));

        $result = $response->getSession()->get('import_result');
        $this->assertIsArray($result);

        return $result;
    }

    private function assertHasAttributeValue(Product $product, string $attributeCode, string $value): void
    {
        $product->load('attributeValues.attribute');

        $this->assertTrue(
            $product->attributeValues->contains(
                fn ($row): bool => $row->attribute?->code === $attributeCode && $row->value === $value,
            ),
            "Expected attribute [{$attributeCode}] = [{$value}].",
        );
    }

    private function assertMissingAttributeCode(Product $product, string $attributeCode): void
    {
        $product->load('attributeValues.attribute');

        $this->assertFalse(
            $product->attributeValues->contains(
                fn ($row): bool => $row->attribute?->code === $attributeCode,
            ),
            "Did not expect attribute [{$attributeCode}] on the product.",
        );
    }

    /**
     * @param  list<string>  $rows
     */
    private function makeCsv(array $rows): string
    {
        $header = implode(',', [
            'ID', 'Type', 'SKU', 'Name', 'Published', 'Visibility in catalog',
            'Short description', 'Description', 'Sale price', 'Regular price',
            'Categories', 'Tags', 'Collections', 'Images', 'Brands', 'Seller', 'Parent',
            'Attribute 1 name', 'Attribute 1 value(s)',
            'Attribute 2 name', 'Attribute 2 value(s)',
            'Attribute 3 name', 'Attribute 3 value(s)',
            'Attribute 4 name', 'Attribute 4 value(s)',
            'Meta: condition', 'Stock',
        ]);

        return $header."\n".implode("\n", $rows);
    }

    /**
     * @param  array<string, string>  $overrides
     */
    private function csvRow(array $overrides = []): string
    {
        $defaults = [
            'ID' => '1',
            'Type' => 'simple',
            'SKU' => 'SKU-001',
            'Name' => 'Sample Product',
            'Published' => '1',
            'Visibility in catalog' => 'visible',
            'Short description' => '',
            'Description' => '',
            'Sale price' => '100',
            'Regular price' => '200',
            'Categories' => '',
            'Tags' => '',
            'Collections' => '',
            'Images' => '',
            'Brands' => '',
            'Seller' => '',
            'Parent' => '',
            'Attribute 1 name' => '',
            'Attribute 1 value(s)' => '',
            'Attribute 2 name' => '',
            'Attribute 2 value(s)' => '',
            'Attribute 3 name' => '',
            'Attribute 3 value(s)' => '',
            'Attribute 4 name' => '',
            'Attribute 4 value(s)' => '',
            'Meta: condition' => '',
            'Stock' => '1',
        ];

        $row = array_merge($defaults, $overrides);

        return implode(',', [
            $row['ID'],
            $row['Type'],
            $row['SKU'],
            '"'.$row['Name'].'"',
            $row['Published'],
            $row['Visibility in catalog'],
            '"'.$row['Short description'].'"',
            '"'.$row['Description'].'"',
            $row['Sale price'],
            $row['Regular price'],
            '"'.$row['Categories'].'"',
            '"'.$row['Tags'].'"',
            '"'.$row['Collections'].'"',
            '"'.$row['Images'].'"',
            '"'.$row['Brands'].'"',
            '"'.$row['Seller'].'"',
            '"'.$row['Parent'].'"',
            '"'.$row['Attribute 1 name'].'"',
            '"'.$row['Attribute 1 value(s)'].'"',
            '"'.$row['Attribute 2 name'].'"',
            '"'.$row['Attribute 2 value(s)'].'"',
            '"'.$row['Attribute 3 name'].'"',
            '"'.$row['Attribute 3 value(s)'].'"',
            '"'.$row['Attribute 4 name'].'"',
            '"'.$row['Attribute 4 value(s)'].'"',
            '"'.$row['Meta: condition'].'"',
            $row['Stock'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function variableWorkspacePayload(): array
    {
        $workspace = [
            'product' => [
                'name' => 'Export Hoodie',
                'slug' => 'export-hoodie',
                'status' => 'published',
                'visibility' => 'public',
                'type' => 'variable',
                'trackInventory' => false,
            ],
            'options' => [
                ['id' => 'opt_color', 'name' => 'Color', 'values' => ['Red', 'Blue']],
                ['id' => 'opt_size', 'name' => 'Size', 'values' => ['S', 'M']],
            ],
            'variants' => [
                [
                    'name' => 'Red / S',
                    'sku' => 'HOODIE-RED-S',
                    'price' => '100',
                    'options' => ['color' => 'Red', 'size' => 'S'],
                    'isDefault' => true,
                ],
                [
                    'name' => 'Red / M',
                    'sku' => 'HOODIE-RED-M',
                    'price' => '110',
                    'options' => ['color' => 'Red', 'size' => 'M'],
                ],
            ],
            'media' => ['productUuids' => []],
        ];

        return [
            'name' => 'Export Hoodie',
            'status' => 'published',
            'visibility' => 'public',
            'workspace_payload' => json_encode($workspace),
        ];
    }

    private function fakeJpegBytes(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAf/CABEIAAEAAQMBIgACEQEDEQH/xAAUAAEAAAAAAAAAAAAAAAAAAAAK/9oACAEBAAAAAH8f/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAhAAAAB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAxAAAAB//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwB//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwB//9k=') ?: '';
    }
}
