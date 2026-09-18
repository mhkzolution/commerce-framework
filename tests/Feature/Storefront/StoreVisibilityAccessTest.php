<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Contracts\Storefront\StoreAccessPolicyInterface;
use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Customers\Models\Customer;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\Concerns\SetsStoreVisibility;
use Tests\TestCase;

final class StoreVisibilityAccessTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;
    use SetsStoreVisibility;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
        $this->withoutVite();
    }

    public function test_members_guest_is_redirected_to_login_with_current_url(): void
    {
        $this->setStoreVisibility(StoreVisibility::Members);

        $shop = route('storefront.shop.index');

        $this->get($shop)
            ->assertRedirect(route('storefront.account.login', ['redirect' => $shop]));
    }

    public function test_members_guest_search_and_product_routes_require_login(): void
    {
        $this->setStoreVisibility(StoreVisibility::Members);
        $variant = $this->createPurchasableProduct(price: 2500, sku: 'MEM-001');

        $search = route('storefront.shop.index', ['q' => 'shoes']);
        $this->get($search)
            ->assertRedirect(route('storefront.account.login', ['redirect' => $search]));

        $pdp = route('storefront.products.show', $variant->product->slug);
        $this->get($pdp)
            ->assertRedirect(route('storefront.account.login', ['redirect' => $pdp]));

        $this->get(route('storefront.suggest', ['q' => 'sh']))
            ->assertRedirect();
    }

    public function test_members_guest_returns_to_shop_after_login(): void
    {
        $this->setStoreVisibility(StoreVisibility::Members);
        $customer = $this->makeCustomer('members.return@example.com');
        $shop = route('storefront.shop.index');

        $this->get($shop);
        $this->post(route('storefront.account.login.store'), [
            'email' => $customer->email,
            'password' => 'password123',
        ])->assertRedirect($shop);
    }

    public function test_private_guest_sees_access_restricted_page_not_login_redirect(): void
    {
        $this->setStoreVisibility(StoreVisibility::Private);

        $html = $this->get(route('storefront.shop.index'))
            ->assertForbidden()
            ->assertSee('data-store-access-restricted', false)
            ->assertSee(__('storefront::storefront.access_restricted_body'), false)
            ->assertDontSee('storefront-pdp', false)
            ->getContent();

        $this->assertStringContainsString(route('storefront.account.login'), $html);
        $this->assertStringContainsString(route('storefront.account.register'), $html);
        $this->assertStringContainsString('noindex,nofollow', $html);
    }

    public function test_private_login_and_register_remain_reachable(): void
    {
        $this->setStoreVisibility(StoreVisibility::Private);

        $this->get(route('storefront.account.login'))->assertOk();
        $this->get(route('storefront.account.register'))->assertOk();
    }

    public function test_private_member_without_policy_sees_forbidden_copy(): void
    {
        $this->setStoreVisibility(StoreVisibility::Private);
        $this->app->instance(StoreAccessPolicyInterface::class, new class implements StoreAccessPolicyInterface
        {
            public function canAccessStore(?object $customer): bool
            {
                return false;
            }
        });

        $this->actingAs($this->makeCustomer('denied@example.com'), 'customer')
            ->get(route('storefront.shop.index'))
            ->assertForbidden()
            ->assertSee(__('storefront::storefront.access_forbidden_body'), false);
    }

    public function test_private_entitled_member_can_open_shop(): void
    {
        $this->setStoreVisibility(StoreVisibility::Private);
        $this->createPurchasableProduct(price: 2500, sku: 'PRIV-OK');

        $this->actingAs($this->makeCustomer('dealer@example.com'), 'customer')
            ->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('storefront-product-card__quick-add', false);
    }

    public function test_members_pages_are_noindex(): void
    {
        $this->setStoreVisibility(StoreVisibility::Members);

        $this->actingAs($this->makeCustomer('seo@example.com'), 'customer')
            ->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    private function makeCustomer(string $email): Customer
    {
        return Customer::query()->create([
            'email' => $email,
            'name' => 'Store Member',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }
}
