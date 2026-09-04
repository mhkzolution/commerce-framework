<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontNavigationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_and_save_storefront_navigation(): void
    {
        $this->actingAs(User::query()->first())
            ->get('/admin/storefront/navigation')
            ->assertOk()
            ->assertSee('Storefront navigation', false)
            ->assertSee('Promo bar', false)
            ->assertSee('Show promo bar above the header', false)
            ->assertSee('Promo message', false)
            ->assertSee('Allow shoppers to dismiss the promo bar', false)
            ->assertSee('Navigation items (JSON)', false)
            ->assertSee('Save navigation', false);

        $this->actingAs(User::query()->first())
            ->put('/admin/storefront/navigation', [
                'promo_enabled' => '1',
                'promo_message' => 'FREE SHIPPING ON ORDERS ฿1,500+',
                'promo_dismissible' => '1',
                'items_json' => '[{"id":"shop","type":"link","label":"Shop"}]',
            ])
            ->assertRedirect('/admin/storefront/navigation');

        $settings = app(SettingQueryServiceInterface::class);
        $this->assertTrue((bool) $settings->get('storefront.navigation.promo_enabled'));
        $this->assertSame('FREE SHIPPING ON ORDERS ฿1,500+', $settings->get('storefront.navigation.promo_message'));
        $this->assertTrue((bool) $settings->get('storefront.navigation.promo_dismissible'));
        $this->assertSame('[{"id":"shop","type":"link","label":"Shop"}]', $settings->get('storefront.navigation.items_json'));
    }

    public function test_invalid_navigation_json_is_rejected(): void
    {
        $this->actingAs(User::query()->first())
            ->from('/admin/storefront/navigation')
            ->put('/admin/storefront/navigation', [
                'promo_enabled' => '0',
                'promo_message' => 'Hello',
                'promo_dismissible' => '1',
                'items_json' => '{not-json',
            ])
            ->assertRedirect('/admin/storefront/navigation')
            ->assertSessionHasErrors('items_json');
    }
}
