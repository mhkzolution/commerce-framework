<?php

declare(strict_types=1);

namespace Tests\Feature\Customers;

use Commerce\Customers\Models\Customer;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CustomerAdminFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
        app()->setLocale('en');
    }

    public function test_create_form_renders_without_existing_customer(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.customers.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('Create customer', false);
    }

    public function test_create_form_shows_checkout_contact_password_and_address_fields(): void
    {
        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.customers.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="phone"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="password_confirmation"', $html);
        $this->assertStringContainsString('name="address[line1]"', $html);
        $this->assertStringContainsString('name="address[postal_code]"', $html);
        $this->assertStringContainsString('name="address[country_code]"', $html);
        $this->assertMatchesRegularExpression('/<select[^>]*name="address\[country_code\]"[^>]*class="[^"]*cf-input/', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="address\[postal_code\]"[^>]*class="[^"]*cf-input/', $html);
    }

    public function test_store_requires_password(): void
    {
        $this->actingAs(User::query()->first())
            ->from(route('admin.customers.create'))
            ->post(route('admin.customers.store'), $this->customerPayload([
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect(route('admin.customers.create'))
            ->assertSessionHasErrors('password');
    }

    public function test_store_saves_password_and_checkout_address(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.customers.store'), $this->customerPayload())
            ->assertRedirect();

        $customer = Customer::query()->where('email', 'harbor@example.com')->first();
        $this->assertNotNull($customer);
        $this->assertSame('Harbor Guest', $customer->name);
        $this->assertSame('0812345678', $customer->phone);
        $this->assertTrue(Hash::check('password123', $customer->password));
        $this->assertTrue(Auth::guard('customer')->attempt([
            'email' => 'harbor@example.com',
            'password' => 'password123',
        ]));

        $address = $customer->addresses()->first();
        $this->assertNotNull($address);
        $this->assertSame('88 Sukhumvit', $address->line1);
        $this->assertSame('Khlong Toei', $address->city);
        $this->assertSame('10110', $address->postal_code);
        $this->assertSame('TH', $address->country_code);
        $this->assertTrue($address->is_default_shipping);
        $this->assertTrue($address->is_default_billing);
    }

    public function test_update_can_change_customer_password(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.customers.store'), $this->customerPayload());

        $customer = Customer::query()->where('email', 'harbor@example.com')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->put(route('admin.customers.update', $customer), [
                'name' => 'Harbor Guest',
                'email' => 'harbor@example.com',
                'phone' => '0812345678',
                'status' => 'active',
                'password' => 'newpass123',
                'password_confirmation' => 'newpass123',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertTrue(Hash::check('newpass123', $customer->password));
        $this->assertFalse(Hash::check('password123', $customer->password));
    }

    public function test_update_without_password_keeps_existing_password(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.customers.store'), $this->customerPayload());

        $customer = Customer::query()->where('email', 'harbor@example.com')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->put(route('admin.customers.update', $customer), [
                'name' => 'Harbor Updated',
                'email' => 'harbor@example.com',
                'phone' => '0812345678',
                'status' => 'active',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame('Harbor Updated', $customer->name);
        $this->assertTrue(Hash::check('password123', $customer->password));
    }

    public function test_edit_form_still_shows_tax_profile(): void
    {
        $this->actingAs(User::query()->first())
            ->post(route('admin.customers.store'), $this->customerPayload())
            ->assertRedirect();

        $customer = Customer::query()->where('email', 'harbor@example.com')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->get(route('admin.customers.edit', $customer))
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertSee(__('documents::admin.tax_profile'), false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function customerPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'name' => 'Harbor Guest',
            'email' => 'harbor@example.com',
            'phone' => '0812345678',
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address' => [
                'line1' => '88 Sukhumvit',
                'line2' => '',
                'city' => 'Khlong Toei',
                'district' => 'Khlong Toei',
                'subdistrict' => 'Khlong Toei',
                'state' => 'Bangkok',
                'postal_code' => '10110',
                'country_code' => 'TH',
            ],
        ], $overrides);
    }
}
