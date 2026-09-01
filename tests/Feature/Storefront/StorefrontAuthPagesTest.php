<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontAuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_auth_layout(): void
    {
        $this->get(route('storefront.account.login'))
            ->assertOk()
            ->assertSee(__('customers::auth.welcome'))
            ->assertSee('storefront-auth-page', false)
            ->assertDontSee('storefront-site-header', false);
    }

    public function test_register_page_uses_auth_layout(): void
    {
        $this->get(route('storefront.account.register'))
            ->assertOk()
            ->assertSee(__('customers::auth.register_title'))
            ->assertSee(__('customers::auth.confirm_password'))
            ->assertSee('storefront-auth-page', false)
            ->assertDontSee('storefront-site-header', false);
    }

    public function test_forgot_password_page_uses_auth_layout(): void
    {
        $this->get(route('storefront.account.password.request'))
            ->assertOk()
            ->assertSee(__('customers::auth.forgot_password_title'))
            ->assertSee(__('customers::auth.back_to_sign_in'))
            ->assertSee('storefront-auth-page', false)
            ->assertDontSee('storefront-site-header', false);
    }
}
