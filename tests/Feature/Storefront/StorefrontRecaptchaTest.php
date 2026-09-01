<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class StorefrontRecaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_recaptcha_when_enabled(): void
    {
        config([
            'customers.storefront.recaptcha.enabled' => true,
            'customers.storefront.recaptcha.site_key' => 'test-site-key',
            'customers.storefront.recaptcha.secret_key' => 'test-secret-key',
        ]);

        $this->post(route('storefront.account.register.store'), [
            'name' => 'Human User',
            'email' => 'human@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('g-recaptcha-response');

        $this->assertDatabaseMissing('customers', ['email' => 'human@example.com']);
    }

    public function test_registration_passes_with_valid_recaptcha_token(): void
    {
        config([
            'customers.storefront.recaptcha.enabled' => true,
            'customers.storefront.recaptcha.site_key' => 'test-site-key',
            'customers.storefront.recaptcha.secret_key' => 'test-secret-key',
            'customers.storefront.recaptcha.min_score' => 0.5,
        ]);

        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'score' => 0.9,
            ]),
        ]);

        $this->post(route('storefront.account.register.store'), [
            'name' => 'Human User',
            'email' => 'human@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'g-recaptcha-response' => 'valid-token',
        ])->assertRedirect(route('storefront.account'));

        $this->assertDatabaseHas('customers', ['email' => 'human@example.com']);
    }
}
