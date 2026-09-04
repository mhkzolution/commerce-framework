<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Catalog\Models\Category;
use Commerce\Catalog\Models\Tag;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Marketplace\Models\Seller;
use Commerce\Product\Models\Product;
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
            ->assertSee('Upload CSV', false);
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

    public function test_import_reports_duplicate_skus_in_csv(): void
    {
        $csv = $this->makeCsv([
            $this->csvRow(['SKU' => 'CSV-DUP-001', 'Name' => 'First product']),
            $this->csvRow(['SKU' => 'CSV-DUP-001', 'Name' => 'Second product']),
        ]);

        $response = $this->actingAs(User::query()->first())
            ->post(route('admin.products.import.store'), [
                'csv' => UploadedFile::fake()->createWithContent('products.csv', $csv),
            ])
            ->assertRedirect(route('admin.products.import.show'));

        $result = $response->getSession()->get('import_result');

        $this->assertIsArray($result);
        $this->assertSame(2, $result['duplicates']);
        $this->assertContains('CSV-DUP-001', $result['duplicate_skus']);
        $this->assertSame(0, Product::query()->count());
    }

    public function test_admin_can_export_variable_product_with_variation_rows(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.products.store'), $this->variableWorkspacePayload())
            ->assertRedirect();

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.products.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('variable', $content);
        $this->assertStringContainsString('variation', $content);
        $this->assertStringContainsString('HOODIE-RED-S', $content);
        $this->assertStringContainsString('HOODIE-RED-M', $content);
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
        $this->assertSame(['color' => 'Red', 'size' => 'S'], $product->variants->firstWhere('sku', 'HOODIE-RED-S')?->meta['options']);
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

    /**
     * @param  list<string>  $rows
     */
    private function makeCsv(array $rows): string
    {
        $header = implode(',', [
            'ID', 'Type', 'SKU', 'Name', 'Published', 'Visibility in catalog',
            'Short description', 'Description', 'Sale price', 'Regular price',
            'Categories', 'Tags', 'Images', 'Brands', 'Seller', 'Parent',
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
