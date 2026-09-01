<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CustomerOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['customers.storefront.otp.enabled' => true]);
    }

    public function test_send_otp_endpoint_returns_json_success(): void
    {
        Customer::query()->create([
            'email' => 'otp@example.com',
            'name' => 'OTP User',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->postJson(route('storefront.account.otp.send'), [
            'identifier' => 'otp@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('message', __('customers::auth.otp_sent'));

        $this->assertDatabaseHas('customer_login_otps', [
            'identifier' => 'otp@example.com',
        ]);
    }

    public function test_customer_can_login_with_valid_otp(): void
    {
        $customer = Customer::query()->create([
            'email' => 'otp@example.com',
            'name' => 'OTP User',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        DB::table('customer_login_otps')->insert([
            'identifier' => 'otp@example.com',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        $this->post(route('storefront.account.login.store'), [
            'login_mode' => 'otp',
            'identifier' => 'otp@example.com',
            'otp' => '123456',
        ])->assertRedirect(route('storefront.account'));

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_registration_honeypot_rejects_bot_submissions(): void
    {
        $this->post(route('storefront.account.register.store'), [
            'name' => 'Bot User',
            'email' => 'bot@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'website' => 'https://spam.test',
        ])->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('customers', ['email' => 'bot@example.com']);
    }
}
