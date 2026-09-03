<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\Services\FooterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FooterSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    public function test_admin_can_view_footer_settings(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.settings.footer.show'))
            ->assertOk()
            ->assertSee('Footer Settings', false)
            ->assertSee('All changes saved', false)
            ->assertSee('Add Section', false)
            ->assertSee('Live Preview', false)
            ->assertSee('data-footer-settings', false)
            ->assertSee('data-footer-preview', false)
            ->assertSee('data-footer-editor', false);
    }

    public function test_admin_can_save_footer_config(): void
    {
        $payload = app(FooterConfigService::class)->defaults();
        $payload['layout']['columns'] = 3;
        $payload['layout']['spacing'] = 'sm';
        $payload['sections'][0]['enabled'] = false;
        $payload['sections'][1]['settings']['max_links'] = 8;

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.footer.update'), [
                'config' => json_encode($payload),
            ])
            ->assertRedirect(route('admin.settings.footer.show'));

        $saved = app(SettingQueryServiceInterface::class)->get(FooterConfigService::SETTING_KEY);

        $this->assertIsArray($saved);
        $this->assertSame(3, $saved['layout']['columns']);
        $this->assertSame('sm', $saved['layout']['spacing']);
        $this->assertFalse($saved['sections'][0]['enabled']);
        $this->assertSame(8, $saved['sections'][1]['settings']['max_links']);
    }

    public function test_preview_returns_footer_html_from_shared_blade(): void
    {
        $payload = app(FooterConfigService::class)->defaults();

        $response = $this->actingAs(User::query()->first())
            ->postJson(route('admin.settings.footer.preview'), [
                'config' => json_encode($payload),
            ])
            ->assertOk()
            ->assertJsonStructure([
                'html',
                'meta' => [
                    'total_sections',
                    'visible_sections',
                    'hidden_sections',
                    'hidden_reasons',
                ],
            ]);

        $html = $response->json('html');
        $this->assertIsString($html);
        $this->assertStringContainsString('<footer class="storefront-site-footer', $html);
        $this->assertStringContainsString('storefront-site-footer__section--brand', $html);
        $this->assertStringContainsString('storefront-site-footer__meta-text', $html);
        $this->assertStringNotContainsString('storefront-site-footer__section--social', $html);
        $this->assertStringNotContainsString('storefront-site-footer__section--links', $html);
    }

    public function test_footer_save_normalizes_and_skips_malformed_sections(): void
    {
        $payload = app(FooterConfigService::class)->defaults();
        $payload['sections'] = [
            'broken',
            [
                'id' => 'valid-brand',
                'type' => 'brand',
                'enabled' => '1',
                'settings' => [
                    'show_logo' => 0,
                    'show_store_name' => 'true',
                    'show_description' => 'no',
                ],
            ],
            [
                'id' => 'Invalid Id',
                'type' => 'cms',
                'enabled' => true,
                'settings' => [
                    'page_ids' => [1, 2, 'bad'],
                ],
            ],
            [
                'id' => 'valid-brand',
                'type' => 'social',
                'enabled' => true,
                'settings' => 'invalid',
            ],
        ];

        $this->actingAs(User::query()->first())
            ->put(route('admin.settings.footer.update'), [
                'config' => json_encode($payload),
            ])
            ->assertRedirect(route('admin.settings.footer.show'));

        $saved = app(SettingQueryServiceInterface::class)->get(FooterConfigService::SETTING_KEY);

        $this->assertIsArray($saved);
        $this->assertCount(1, $saved['sections']);
        $this->assertSame('valid-brand', $saved['sections'][0]['id']);
        $this->assertSame('brand', $saved['sections'][0]['type']);
        $this->assertTrue($saved['sections'][0]['enabled']);
        $this->assertFalse($saved['sections'][0]['settings']['show_logo']);
        $this->assertTrue($saved['sections'][0]['settings']['show_store_name']);
        $this->assertFalse($saved['sections'][0]['settings']['show_description']);
    }
}
