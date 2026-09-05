<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Services\CartService;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\Models\ProductMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class Ws002ShopperChromeContractTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedCheckoutDependencies();
    }

    public function test_empty_cart_uses_page_container_and_empty_state(): void
    {
        $html = $this->get(route('storefront.cart.index'))
            ->assertOk()
            ->assertSee(__('storefront::storefront.cart_empty'))
            ->getContent();

        $this->assertStringContainsString('storefront-shopper-main', $html);
        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringContainsString('storefront-empty', $html);
        $this->assertStringContainsString('storefront-site-header', $html);
        $this->assertStringNotContainsString('cf-btn', $html);
        $this->assertStringNotContainsString('cf-input', $html);
        $this->assertStringNotContainsString('cf-flash', $html);
    }

    public function test_cart_with_line_has_qty_form_and_checkout_link(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 4, sku: 'CART-CHROME-1');

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 2,
        ])->assertRedirect(route('storefront.cart.index'));

        $html = $this->get(route('storefront.cart.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="quantity"', $html);
        $this->assertStringContainsString(__('storefront::storefront.checkout'), $html);
        $this->assertStringContainsString('storefront-btn', $html);
        $this->assertStringContainsString('storefront-cart__layout', $html);
        $this->assertStringNotContainsString('cf-btn', $html);
    }

    public function test_cart_line_shows_image_when_media_url_exists(): void
    {
        $variant = $this->createPurchasableProduct(price: 1500, stock: 4, sku: 'CART-IMG-1');
        $mediaUuid = 'media-cart-line';

        ProductMedia::query()->create([
            'product_id' => $variant->product->id,
            'media_uuid' => $mediaUuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->app->instance(MediaQueryServiceInterface::class, new class($mediaUuid) implements MediaQueryServiceInterface
        {
            public function __construct(private readonly string $uuid) {}

            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/cart-line.jpg' : null;
            }

            public function getSrcset(string $uuid): ?string
            {
                return $uuid === $this->uuid ? 'https://cdn.example.test/cart-line.jpg 800w' : null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }
        });
        $this->app->forgetInstance(CartService::class);
        $this->app->forgetInstance(CartServiceInterface::class);

        $this->post(route('storefront.cart.items.store'), [
            'purchasable_uuid' => $variant->uuid,
            'quantity' => 1,
        ])->assertRedirect(route('storefront.cart.index'));

        $this->get(route('storefront.cart.index'))
            ->assertOk()
            ->assertSee('https://cdn.example.test/cart-line.jpg', false)
            ->assertSee('storefront-cart-item__image', false);
    }

    public function test_login_and_register_use_auth_card_without_admin_chrome(): void
    {
        $login = $this->get(route('storefront.account.login'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(__('customers::auth.welcome'), $login);
        $this->assertStringContainsString('storefront-auth-page', $login);
        $this->assertStringNotContainsString('cf-btn', $login);
        $this->assertStringNotContainsString('storefront-site-header', $login);

        $register = $this->get(route('storefront.account.register'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-auth-page', $register);
        $this->assertStringNotContainsString('cf-input', $register);
    }

    public function test_account_uses_storefront_address_form_not_admin_include(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Chrome User',
            'email' => 'chrome.user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('storefront.account'));

        $dashboard = $this->get(route('storefront.account'))
            ->assertOk()
            ->assertSee('Chrome User')
            ->getContent();

        $this->assertStringContainsString('storefront-shopper-main', $dashboard);
        $this->assertStringContainsString('storefront-account__layout', $dashboard);
        $this->assertStringNotContainsString('cf-btn', $dashboard);
        $this->assertStringNotContainsString('cf-badge', $dashboard);

        $html = $this->get(route('storefront.account.addresses'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="line1"', $html);
        $this->assertStringNotContainsString('cf-btn', $html);
        $this->assertStringNotContainsString('admin._address_form', $html);
    }

    public function test_checkout_uses_page_container_without_admin_chrome(): void
    {
        $html = $this->get(route('storefront.checkout'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-shopper-main', $html);
        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringNotContainsString('cf-btn', $html);
        $this->assertStringNotContainsString('lg:grid-cols-2', $html);
    }
}
