<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontAuthPagesTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedCheckoutDependencies();
    }

    public function test_login_page_uses_dedicated_auth_card_not_shopper_chrome(): void
    {
        $html = $this->get(route('storefront.account.login'))
            ->assertOk()
            ->assertSee('storefront-auth-page', false)
            ->assertSee('storefront-auth-card', false)
            ->assertSee('data-password-toggle', false)
            ->assertSee('storefront-auth-remember', false)
            ->getContent();

        $this->assertStringContainsString(__('customers::auth.welcome'), $html);
        $this->assertStringContainsString(__('customers::auth.welcome_description'), $html);
        $this->assertStringContainsString(route('storefront.account.register'), $html);
        $this->assertStringNotContainsString('storefront-site-header', $html);
        $this->assertStringNotContainsString('storefront-shopper-main', $html);
        $this->assertStringNotContainsString('storefront-auth__layout', $html);
        $this->assertStringNotContainsString('cf-btn', $html);
        $this->assertStringNotContainsString('cf-input', $html);
        $this->assertStringNotContainsString('x-admin.', $html);
    }

    public function test_register_page_uses_dedicated_auth_card_not_shopper_chrome(): void
    {
        $html = $this->get(route('storefront.account.register'))
            ->assertOk()
            ->assertSee('storefront-auth-page', false)
            ->assertSee('storefront-auth-card', false)
            ->assertSee('name="website"', false)
            ->getContent();

        $this->assertStringContainsString(__('customers::auth.register_title'), $html);
        $this->assertStringContainsString(__('customers::auth.register_description'), $html);
        $this->assertStringContainsString(__('customers::auth.confirm_password'), $html);
        $this->assertStringContainsString(route('storefront.account.login'), $html);
        $this->assertStringNotContainsString('storefront-site-header', $html);
        $this->assertStringNotContainsString('storefront-shopper-main', $html);
        $this->assertStringNotContainsString('cf-input', $html);
    }

    public function test_failed_login_shows_translated_credentials_error(): void
    {
        $this->from(route('storefront.account.login'))
            ->followingRedirects()
            ->post(route('storefront.account.login.store'), [
                'email' => 'missing@example.com',
                'password' => 'wrong-password',
            ])
            ->assertOk()
            ->assertSee(__('customers::auth.invalid_credentials'));
    }

    public function test_customer_can_sign_in_after_registering(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Auth Shopper',
            'email' => 'auth.shopper@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('storefront.account'));

        $this->post(route('storefront.account.logout'))
            ->assertRedirect(route('storefront.shop.index'));

        $this->post(route('storefront.account.login.store'), [
            'email' => 'auth.shopper@example.com',
            'password' => 'password123',
            'remember' => '1',
        ])->assertRedirect(route('storefront.account'));

        $this->get(route('storefront.account'))
            ->assertOk()
            ->assertSee('Auth Shopper');
    }

    public function test_registration_authenticates_customer_and_never_redirects_to_admin(): void
    {
        $this->withSession(['url.intended' => url('/admin/login')])
            ->post(route('storefront.account.register.store'), [
                'name' => 'Store Shopper',
                'email' => 'store.shopper@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('storefront.account'));

        $customer = Customer::query()->where('email', 'store.shopper@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('web');

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get(route('storefront.account'))->assertOk();
    }

    public function test_registration_preserves_storefront_intended_url(): void
    {
        $this->withSession(['url.intended' => route('storefront.checkout')])
            ->post(route('storefront.account.register.store'), [
                'name' => 'Checkout Shopper',
                'email' => 'checkout.shopper@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('storefront.checkout'));

        $this->assertAuthenticatedAs(
            Customer::query()->where('email', 'checkout.shopper@example.com')->first(),
            'customer',
        );
    }

    public function test_login_ignores_admin_intended_url(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Return Shopper',
            'email' => 'return.shopper@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $this->post(route('storefront.account.logout'));

        $this->withSession(['url.intended' => url('/admin')])
            ->post(route('storefront.account.login.store'), [
                'email' => 'return.shopper@example.com',
                'password' => 'password123',
            ])
            ->assertRedirect(route('storefront.account'));
    }

    public function test_authenticated_customer_visiting_login_stays_on_storefront(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Signed In Shopper',
            'email' => 'signed.in@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->withSession(['url.intended' => url('/admin/login')])
            ->get(route('storefront.account.login'))
            ->assertRedirect(route('storefront.account'));
    }

    public function test_guest_account_routes_redirect_to_storefront_login(): void
    {
        $this->get(route('storefront.account'))
            ->assertRedirect(route('storefront.account.login'));
    }
}
