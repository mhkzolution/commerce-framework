<?php

declare(strict_types=1);

namespace Tests\Feature\Product;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Inventory\Database\Seeders\InventoryLocationSeeder;
use Commerce\Product\Database\Seeders\ProductMockupSeeder;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ProductMockupSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media.disk' => 'public']);
        $this->seed(IamSeeder::class);
        $this->seed(InventoryLocationSeeder::class);
    }

    public function test_seeder_creates_simple_and_variable_mockup_products(): void
    {
        $this->seed(ProductMockupSeeder::class);

        $simple = Product::query()->where('slug', 'mockup-organic-tee')->with('variants')->first();
        $variable = Product::query()->where('slug', 'mockup-minimal-hoodie')->with('variants')->first();

        $this->assertNotNull($simple);
        $this->assertNotNull($variable);
        $this->assertSame('simple', $simple->type);
        $this->assertSame('variable', $variable->type);
        $this->assertCount(1, $simple->variants);
        $this->assertCount(6, $variable->variants);
        $this->assertSame('MOCK-TEE-001', $simple->defaultVariant()?->sku);
        $this->assertTrue($simple->media()->exists());
        $this->assertTrue($variable->media()->exists());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ProductMockupSeeder::class);
        $this->seed(ProductMockupSeeder::class);

        $this->assertSame(2, Product::query()->whereIn('slug', ['mockup-organic-tee', 'mockup-minimal-hoodie'])->count());
    }
}
