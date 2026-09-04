<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class CustomerExperienceStorefrontTest extends TestCase
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

    public function test_shop_renders_quick_view_button_and_overlays(): void
    {
        $this->createPurchasableProduct(price: 2500, sku: 'CX-SHOP-001');

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('data-quick-view-open', false)
            ->assertSee('data-customer-experience-config', false)
            ->assertSee(__('storefront::storefront.quick_view'), false);
    }

    public function test_quick_view_api_returns_product_payload(): void
    {
        $variant = $this->createPurchasableProduct(price: 2500, sku: 'CX-QV-001');
        $product = $variant->product;

        $this->getJson(route('api.v1.storefront.products.quick-view', $product->uuid))
            ->assertOk()
            ->assertJsonPath('data.uuid', $product->uuid)
            ->assertJsonPath('data.sku', 'CX-QV-001')
            ->assertJsonPath('data.formatted_price', '25.00');
    }

    public function test_quick_view_api_is_hidden_when_module_disabled(): void
    {
        $variant = $this->createPurchasableProduct(price: 2500, sku: 'CX-QV-OFF');
        $module = SystemModule::query()->where('code', 'customer-experience')->firstOrFail();
        app(ModuleService::class)->updateStatus($module, ModuleStatus::Disabled);

        $this->getJson(route('api.v1.storefront.products.quick-view', $variant->product->uuid))
            ->assertNotFound();
    }
}
