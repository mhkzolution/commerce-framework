<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Services\FooterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class FooterPreviewStatelessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
        $this->seed(SettingsSeeder::class);
        Carbon::setTestNow('2026-08-18 15:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_preview_returns_expected_shape_without_persisting_transient_config(): void
    {
        $storedConfig = app(FooterConfigService::class)->defaults();
        $storedConfig['sections'] = [
            [
                'id' => 'copyright',
                'type' => 'copyright',
                'enabled' => true,
                'settings' => [
                    'template' => 'Stored footer {year} {store_name}',
                ],
                'visibility' => [
                    'guest' => true,
                    'authenticated' => true,
                ],
            ],
        ];

        $this->saveStoreName('Preview Shop');
        $this->saveStoredFooterConfig($storedConfig);

        $previewConfig = app(FooterConfigService::class)->defaults();
        $previewConfig['sections'] = [
            [
                'id' => 'social-links',
                'type' => 'social',
                'enabled' => false,
                'settings' => [],
                'visibility' => [],
            ],
            [
                'id' => 'help-pages',
                'type' => 'cms',
                'enabled' => true,
                'settings' => [
                    'page_ids' => [],
                ],
                'visibility' => [],
            ],
            [
                'id' => 'copyright',
                'type' => 'copyright',
                'enabled' => true,
                'settings' => [
                    'template' => 'Preview footer {year} {store_name}',
                ],
                'visibility' => [],
            ],
        ];

        $response = $this->actingAs(User::query()->first())
            ->postJson(route('admin.settings.footer.preview'), [
                'config' => $previewConfig,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'html',
                'meta' => [
                    'total_sections',
                    'visible_sections',
                    'hidden_sections',
                    'hidden_reasons',
                ],
            ])
            ->assertJsonPath('meta.total_sections', 3)
            ->assertJsonPath('meta.visible_sections', 1)
            ->assertJsonPath('meta.hidden_sections', 2)
            ->assertJsonPath('meta.hidden_reasons.0.section_id', 'social-links')
            ->assertJsonPath('meta.hidden_reasons.0.reason', 'disabled')
            ->assertJsonPath('meta.hidden_reasons.1.section_id', 'help-pages')
            ->assertJsonPath('meta.hidden_reasons.1.reason', 'empty_cms_selection');

        $html = $response->json('html');
        $this->assertIsString($html);
        $this->assertStringContainsString('Preview footer 2026 Preview Shop', $html);
        $this->assertStringNotContainsString('Stored footer 2026 Preview Shop', $html);

        $this->assertSame(
            $storedConfig,
            app(SettingQueryServiceInterface::class)->get(FooterConfigService::SETTING_KEY),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function saveStoredFooterConfig(array $config): void
    {
        $footerConfig = app(FooterConfigService::class);
        $footerConfig->ensureRegistered();

        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'footer',
            values: [
                'config' => $config,
            ],
        ));

        $footerConfig->forgetResolved();
    }

    private function saveStoreName(string $name): void
    {
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'store',
            values: [
                'name' => $name,
            ],
        ));
    }
}
