<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CustomerExperienceNotificationFeedTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->withoutVite();
    }

    public function test_feed_includes_recent_new_products_with_stable_ids(): void
    {
        $variant = $this->createPurchasableProduct(price: 2100, sku: 'CX-NEW-1');
        $product = $variant->product;

        $this->getJson(route('api.v1.storefront.customer-experience.notifications'))
            ->assertOk()
            ->assertJsonPath('data.0.type', 'newProduct')
            ->assertJsonPath('data.0.id', 'newProduct:'.$product->uuid)
            ->assertJsonPath('data.0.title', $product->name);
    }

    public function test_feed_excludes_products_that_are_not_recently_published(): void
    {
        $variant = $this->createPurchasableProduct(price: 2100, sku: 'CX-OLD-1');
        $product = $variant->product;
        $product->forceFill([
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
            'published_at' => now()->subDays(30),
        ])->save();

        $this->getJson(route('api.v1.storefront.customer-experience.notifications'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
