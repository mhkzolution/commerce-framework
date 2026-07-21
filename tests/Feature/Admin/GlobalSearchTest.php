<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use Commerce\Contracts\Admin\AdminGlobalSearchServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class GlobalSearchTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    public function test_admin_search_returns_products(): void
    {
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);

        $admin = User::query()->first();
        $variant = $this->createPurchasableProduct(sku: 'ADMIN-SEARCH-42');
        app(\Commerce\Product\Services\ProductSearchIndexer::class)
            ->index($variant->product->fresh(['variants', 'categories']));

        $this->actingAs($admin)
            ->getJson(route('admin.search', ['q' => 'ADMIN-SEARCH']))
            ->assertOk()
            ->assertJsonFragment(['group' => 'Products']);
    }

    public function test_search_service_respects_permissions(): void
    {
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);

        $service = app(AdminGlobalSearchServiceInterface::class);
        $results = $service->search('admin', User::query()->first());

        $this->assertNotEmpty($results);
    }

    public function test_admin_search_includes_cms_and_crm(): void
    {
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);

        \Commerce\Cms\Models\Page::query()->create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => 'Policy text',
            'status' => 'published',
        ]);

        \Commerce\Crm\Models\Lead::query()->create([
            'name' => 'Searchable Lead',
            'email' => 'lead@example.com',
            'status' => 'new',
        ]);

        $admin = User::query()->first();

        $this->actingAs($admin)
            ->getJson(route('admin.search', ['q' => 'Privacy']))
            ->assertOk()
            ->assertJsonFragment(['group' => 'CMS Pages']);

        $this->actingAs($admin)
            ->getJson(route('admin.search', ['q' => 'Searchable Lead']))
            ->assertOk()
            ->assertJsonFragment(['group' => 'CRM Leads']);
    }
}
