<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CompanySettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_and_save_company_tax_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.company.show'))
            ->assertOk()
            ->assertSee(__('documents::admin.company_title'), false)
            ->assertSee(__('documents::admin.tax_id'), false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.company.update'), [
                'name' => 'Punpun Co., Ltd.',
                'tax_id' => '010-5555-55555-1',
                'branch_no' => '00000',
                'address' => '1 Sathorn',
                'phone' => '021110000',
                'email' => 'finance@punpun.test',
            ])
            ->assertRedirect(route('admin.settings.company.show'));

        $settings = app(SettingQueryServiceInterface::class);
        $this->assertSame('Punpun Co., Ltd.', $settings->get('company.name'));
        $this->assertSame('0105555555551', $settings->get('company.tax_id'));
        $this->assertSame('00000', $settings->get('company.branch_no'));
        $this->assertSame('1 Sathorn', $settings->get('company.address'));
        $this->assertSame('finance@punpun.test', $settings->get('company.email'));
    }

    public function test_company_tax_id_must_be_thirteen_digits(): void
    {
        $this->actingAs(User::query()->first())
            ->from(route('admin.settings.company.show'))
            ->put(route('admin.settings.company.update'), [
                'name' => 'Punpun Co., Ltd.',
                'tax_id' => '12345',
                'branch_no' => '00000',
            ])
            ->assertRedirect(route('admin.settings.company.show'))
            ->assertSessionHasErrors('tax_id');
    }
}
