<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Contracts\Inventory\InventoryQueryServiceInterface;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class WooCommerceImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_woocommerce_csv_rows(): void
    {
        $csv = $this->sampleCsvPath();

        Artisan::call('product:import-woocommerce', [
            'file' => $csv,
        ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Sample Woo Product',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'sku' => 'TEST-IMPORT-001',
        ]);

        $product = Product::query()->where('name', 'Sample Woo Product')->first();
        $this->assertNotNull($product);
        $this->assertSame(2110, $product->meta['wordpress_id']);

        $variant = ProductVariant::query()->where('sku', 'TEST-IMPORT-001')->firstOrFail();
        $this->assertSame(235.0, (float) $variant->price);
        $this->assertSame(780.0, (float) $variant->compare_at_price);

        $stock = app(InventoryQueryServiceInterface::class)->getStockLevel($variant->uuid);
        $this->assertSame(3, $stock->getOnHand());
    }

    public function test_it_skips_corrupt_image_files_when_linking(): void
    {
        Storage::fake('wordpress_uploads');

        $validPath = '2021/03/sample.jpg';
        $corruptPath = '2023/01/corrupt.jpeg';

        Storage::disk('wordpress_uploads')->put($validPath, $this->fakeJpeg());
        Storage::disk('wordpress_uploads')->put($corruptPath, ' ');

        $path = storage_path('framework/testing/woocommerce-corrupt-images.csv');
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, implode("\n", [
            'ID,Type,SKU,Name,Published,Visibility in catalog,Short description,Description,Sale price,Regular price,Categories,Tags,Images,Stock,Attribute 1 name,Attribute 1 value(s),Meta: condition',
            '8888,simple,CORRUPT-IMG-001,Corrupt Image Product,1,visible,,,100,200,Test,,https://example.com/wp-content/uploads/2021/03/sample.jpg, https://example.com/wp-content/uploads/2023/01/corrupt.jpeg,1,,,',
        ]));

        Artisan::call('product:import-woocommerce', ['file' => $path]);
        Artisan::call('product:import-woocommerce', [
            'file' => $path,
            '--link-images' => true,
        ]);

        $product = Product::query()->where('name', 'Corrupt Image Product')->firstOrFail();
        $this->assertCount(1, $product->fresh()->media);
    }

    public function test_it_deduplicates_duplicate_image_urls_when_linking(): void
    {
        Storage::fake('wordpress_uploads');

        $relativePath = '2021/03/sample.jpg';
        Storage::disk('wordpress_uploads')->put($relativePath, $this->fakeJpeg());

        $path = storage_path('framework/testing/woocommerce-duplicate-images.csv');
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, implode("\n", [
            'ID,Type,SKU,Name,Published,Visibility in catalog,Short description,Description,Sale price,Regular price,Categories,Tags,Images,Stock,Attribute 1 name,Attribute 1 value(s),Meta: condition',
            '2110,simple,DUP-IMG-001,Duplicate Image Product,1,visible,,,100,200,Test,,https://example.com/wp-content/uploads/2021/03/sample.jpg, https://example.com/wp-content/uploads/2021/03/sample.jpg,1,,,',
        ]));

        Artisan::call('product:import-woocommerce', ['file' => $path]);
        Artisan::call('product:import-woocommerce', [
            'file' => $path,
            '--link-images' => true,
        ]);

        $product = Product::query()->where('name', 'Duplicate Image Product')->firstOrFail();
        $this->assertCount(1, $product->fresh()->media);
    }

    public function test_it_links_images_from_wordpress_uploads_folder(): void
    {
        Storage::fake('wordpress_uploads');

        $relativePath = '2021/03/sample.jpg';
        Storage::disk('wordpress_uploads')->put($relativePath, $this->fakeJpeg());

        $csv = $this->sampleCsvPath();
        Artisan::call('product:import-woocommerce', ['file' => $csv]);

        Artisan::call('product:import-woocommerce', [
            'file' => $csv,
            '--link-images' => true,
        ]);

        $product = Product::query()->where('name', 'Sample Woo Product')->firstOrFail();
        $this->assertCount(1, $product->fresh()->media);

        $this->assertDatabaseHas('media', [
            'disk' => 'wordpress_uploads',
            'path' => $relativePath,
        ]);
    }

    public function test_it_strips_utf8_bom_from_csv_headers(): void
    {
        $path = storage_path('framework/testing/woocommerce-bom.csv');
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, "\xEF\xBB\xBF".implode("\n", [
            'ID,Type,SKU,Name,Published,Visibility in catalog,Short description,Description,Sale price,Regular price,Categories,Tags,Images,Stock,Attribute 1 name,Attribute 1 value(s),Meta: condition',
            '9999,simple,BOM-SKU,BOM Product,1,visible,,,99,199,Test,,,1,,,',
        ]));

        Artisan::call('product:import-woocommerce', ['file' => $path]);

        $variant = ProductVariant::query()->where('sku', 'BOM-SKU')->firstOrFail();
        $product = $variant->product;

        $this->assertNotNull($product);
        $this->assertSame(9999, $product->meta['wordpress_id']);
    }

    public function test_it_imports_variable_product_rows_from_cli(): void
    {
        $path = storage_path('framework/testing/woocommerce-variable.csv');
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, implode("\n", [
            'ID,Type,SKU,Name,Published,Visibility in catalog,Short description,Description,Sale price,Regular price,Categories,Tags,Images,Stock,Parent,Attribute 1 name,Attribute 1 value(s),Attribute 2 name,Attribute 2 value(s),Meta: condition',
            '6000,variable,VAR-PARENT,Variable Tee,1,visible,,,,,Test,,,0,,Color,"Red, Blue",Size,"S, M",',
            ',variation,VAR-RED-S,Red / S,1,visible,,,100,120,Test,,,2,VAR-PARENT,Color,Red,Size,S,',
            ',variation,VAR-RED-M,Red / M,1,visible,,,110,130,Test,,,3,VAR-PARENT,Color,Red,Size,M,',
        ]));

        Artisan::call('product:import-woocommerce', ['file' => $path]);

        $product = Product::query()->where('name', 'Variable Tee')->with('variants')->firstOrFail();

        $this->assertSame('variable', $product->type);
        $this->assertCount(2, $product->variants);
        $this->assertNotNull($product->variants->firstWhere('sku', 'VAR-RED-S'));
    }

    private function sampleCsvPath(): string
    {
        $path = storage_path('framework/testing/woocommerce-sample.csv');
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($path, implode("\n", [
            'ID,Type,SKU,Name,Published,Visibility in catalog,Short description,Description,Sale price,Regular price,Categories,Tags,Images,Stock,Attribute 1 name,Attribute 1 value(s),Meta: condition',
            '2110,simple,TEST-IMPORT-001,Sample Woo Product,1,visible,Short text,,235,780,สินค้าทดสอบ,,https://example.com/wp-content/uploads/2021/03/sample.jpg,3,สี,สีฟ้า,สภาพดี',
        ]));

        return $path;
    }

    private function fakeJpeg(): string
    {
        return base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAALCAABAAEBAREA/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEABj8Cf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8hf//Z');
    }
}
