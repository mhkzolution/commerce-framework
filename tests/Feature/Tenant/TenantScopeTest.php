<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_are_scoped_to_current_tenant(): void
    {
        config(['commerce.tenant.enabled' => true]);

        $tenantA = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);
        $context->set($tenantA);

        app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Tenant A Product',
            status: 'published',
            visibility: 'public',
            sku: 'A-001',
            price: 1000,
        ));

        $context->set($tenantB);

        app(ProductServiceInterface::class)->create(new CreateProductData(
            name: 'Tenant B Product',
            status: 'published',
            visibility: 'public',
            sku: 'B-001',
            price: 2000,
        ));

        $context->set($tenantA);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('Tenant A Product', Product::query()->first()?->name);

        $context->set($tenantB);
        $this->assertSame(1, Product::query()->count());
        $this->assertSame('Tenant B Product', Product::query()->first()?->name);
    }
}
