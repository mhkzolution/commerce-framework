<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\DTO\CreateCustomerData;
use Commerce\Documents\Models\CustomerTaxProfile;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CustomerTaxProfileAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_save_a_customer_tax_profile_from_the_edit_page(): void
    {
        $customer = app(CustomerServiceInterface::class)->create(new CreateCustomerData(
            email: 'profile@example.com',
            name: 'Profile Co',
        ));
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.customers.edit', $customer))
            ->assertOk()
            ->assertSee(__('documents::admin.tax_profile'), false)
            ->assertSee('data-thailand-address', false)
            ->assertSee('name="billing_address[province]"', false)
            ->assertSee('name="billing_address[district]"', false);

        $this->actingAs($admin)
            ->put(route('admin.customers.tax-profile.update', $customer), [
                'company_name' => 'Profile Co., Ltd.',
                'tax_id' => '0105511111111',
                'branch_no' => '00001',
                'billing_address' => [
                    'line1' => '99 Sukhumvit',
                    'district' => 'Khlong Toei',
                    'subdistrict' => 'Khlong Toei',
                    'province' => 'Bangkok',
                    'postal_code' => '10110',
                    'country_code' => 'TH',
                ],
            ])
            ->assertRedirect(route('admin.customers.edit', $customer));

        $profile = CustomerTaxProfile::query()->where('customer_id', $customer->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame('Profile Co., Ltd.', $profile->company_name);
        $this->assertSame('0105511111111', $profile->tax_id);
        $this->assertSame('00001', $profile->branch_no);
        $this->assertSame('99 Sukhumvit', $profile->billing_address['line1']);
        $this->assertSame('Khlong Toei', $profile->billing_address['city']);
        $this->assertSame('Khlong Toei', $profile->billing_address['district']);
        $this->assertSame('Khlong Toei', $profile->billing_address['subdistrict']);
        $this->assertSame('Bangkok', $profile->billing_address['province']);
        $this->assertSame('Bangkok', $profile->billing_address['state']);
        $this->assertFalse($profile->isHeadOffice());
    }
}
