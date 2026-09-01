<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CustomerExperienceTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    public function test_product_listing_does_not_embed_full_product_details(): void
    {
        $variant = $this->createPurchasableProduct(price: 500, stock: 8, sku: 'QV-LIST-1');
        $product = $variant->product;
        $product->update([
            'description' => 'UNIQUE_QUICK_VIEW_DESCRIPTION_BODY',
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('data-quick-view-open="'.$product->uuid.'"', false)
            ->assertDontSee('UNIQUE_QUICK_VIEW_DESCRIPTION_BODY', false);
    }

    public function test_quick_view_api_lazy_loads_product_details(): void
    {
        $variant = $this->createPurchasableProduct(price: 500, stock: 8, sku: 'QV-API-1');
        $product = $variant->product;
        $product->update([
            'name' => 'Air Max Quick View',
            'description' => 'UNIQUE_QUICK_VIEW_DESCRIPTION_BODY',
        ]);

        $this->getJson(route('api.v1.storefront.products.quick-view', $product->uuid))
            ->assertOk()
            ->assertJsonPath('data.uuid', $product->uuid)
            ->assertJsonPath('data.name', 'Air Max Quick View')
            ->assertJsonPath('data.description', 'UNIQUE_QUICK_VIEW_DESCRIPTION_BODY')
            ->assertJsonPath('data.sku', 'QV-API-1');

        $this->getJson(route('api.products.quick-view', $product->uuid))
            ->assertOk()
            ->assertJsonPath('data.uuid', $product->uuid);
    }

    public function test_quick_view_api_is_hidden_when_disabled(): void
    {
        $variant = $this->createPurchasableProduct(price: 120, stock: 2, sku: 'QV-OFF-1');
        $this->saveConfig([
            'quickView' => ['enabled' => false],
        ]);

        $this->getJson(route('api.v1.storefront.products.quick-view', $variant->product->uuid))
            ->assertNotFound();

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertDontSee('data-quick-view-open="'.$variant->product->uuid.'"', false);
    }

    public function test_storefront_renders_notification_host_and_back_to_top(): void
    {
        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('data-customer-experience-config', false)
            ->assertSee('data-notification-host', false)
            ->assertSee('data-back-to-top', false);
    }

    public function test_back_to_top_can_be_disabled(): void
    {
        $this->saveConfig([
            'navigation' => ['backToTop' => false],
            'notifications' => ['enabled' => false],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertDontSee('data-back-to-top', false)
            ->assertDontSee('data-notification-host', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function saveConfig(array $overrides): void
    {
        $config = app(CustomerExperienceConfig::class);
        $config->ensureRegistered();

        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'customer_experience',
            values: [
                'config' => $config->merge($overrides),
            ],
        ));
    }
}
