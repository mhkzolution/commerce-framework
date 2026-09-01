<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Customers\Models\Customer;
use Commerce\Customers\Notifications\CustomerResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class CustomerPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();

        Customer::query()->create([
            'email' => 'reset@example.com',
            'name' => 'Reset User',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->post(route('storefront.account.password.email'), [
            'email' => 'reset@example.com',
        ])->assertRedirect();

        Notification::assertSentTo(
            Customer::query()->where('email', 'reset@example.com')->first(),
            CustomerResetPasswordNotification::class,
        );
    }

    public function test_customer_can_reset_password_with_valid_token(): void
    {
        $customer = Customer::query()->create([
            'email' => 'reset@example.com',
            'name' => 'Reset User',
            'password' => Hash::make('old-password'),
            'status' => 'active',
        ]);

        $token = Password::broker('customers')->createToken($customer);

        $this->post(route('storefront.account.password.update'), [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('storefront.account.login'));

        $customer->refresh();
        $this->assertTrue(Hash::check('new-password-123', (string) $customer->password));
    }

    public function test_reset_password_page_is_accessible(): void
    {
        $this->get(route('storefront.account.password.reset', [
            'token' => 'sample-token',
            'email' => 'reset@example.com',
        ]))
            ->assertOk()
            ->assertSee(__('customers::auth.reset_password_title'))
            ->assertSee('sample-token', false);
    }
}
