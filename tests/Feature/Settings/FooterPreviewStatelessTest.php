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

        $this->saveSiteIdentity([
            'name' => 'Preview Shop',
        ]);
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

        $response->assertSee('Preview footer 2026 Preview Shop');
        $response->assertDontSee('Stored footer 2026 Preview Shop');

        $this->assertSame(
            $storedConfig,
            app(SettingQueryServiceInterface::class)->get(FooterConfigService::SETTING_KEY),
        );
    }

    public function test_multiple_preview_payloads_are_isolated_from_each_other_and_stored_config(): void
    {
        $storedConfig = app(FooterConfigService::class)->defaults();
        $storedConfig['sections'] = [
            [
                'id' => 'copyright',
                'type' => 'copyright',
                'enabled' => true,
                'settings' => [
                    'template' => 'Persisted footer {year} {store_name}',
                ],
                'visibility' => [
                    'guest' => true,
                    'authenticated' => true,
                ],
            ],
        ];

        $this->saveSiteIdentity([
            'name' => 'Concurrent Preview Shop',
        ]);
        $this->saveStoredFooterConfig($storedConfig);

        $firstPayload = app(FooterConfigService::class)->defaults();
        $firstPayload['sections'] = [
            [
                'id' => 'copyright',
                'type' => 'copyright',
                'enabled' => true,
                'settings' => [
                    'template' => 'First preview {year} {store_name}',
                ],
                'visibility' => [],
            ],
        ];

        $secondPayload = app(FooterConfigService::class)->defaults();
        $secondPayload['sections'] = [
            [
                'id' => 'brand-primary',
                'type' => 'brand',
                'enabled' => false,
                'settings' => [
                    'show_logo' => false,
                    'show_store_name' => false,
                    'show_description' => false,
                ],
                'visibility' => [],
            ],
            [
                'id' => 'copyright',
                'type' => 'copyright',
                'enabled' => true,
                'settings' => [
                    'template' => 'Second preview {year} {store_name}',
                ],
                'visibility' => [],
            ],
        ];

        $user = User::query()->first();

        $firstResponse = $this->actingAs($user)
            ->postJson(route('admin.settings.footer.preview'), [
                'config' => $firstPayload,
            ]);

        $secondResponse = $this->actingAs($user)
            ->postJson(route('admin.settings.footer.preview'), [
                'config' => $secondPayload,
            ]);

        $firstResponse->assertOk()
            ->assertJsonPath('meta.total_sections', 1)
            ->assertJsonPath('meta.visible_sections', 1)
            ->assertJsonPath('meta.hidden_sections', 0);

        $secondResponse->assertOk()
            ->assertJsonPath('meta.total_sections', 2)
            ->assertJsonPath('meta.visible_sections', 1)
            ->assertJsonPath('meta.hidden_sections', 1)
            ->assertJsonPath('meta.hidden_reasons.0.section_id', 'brand-primary')
            ->assertJsonPath('meta.hidden_reasons.0.reason', 'disabled');

        $this->assertStringContainsString('First preview 2026 Concurrent Preview Shop', (string) $firstResponse->json('html'));
        $this->assertStringNotContainsString('Second preview 2026 Concurrent Preview Shop', (string) $firstResponse->json('html'));
        $this->assertStringContainsString('Second preview 2026 Concurrent Preview Shop', (string) $secondResponse->json('html'));
        $this->assertStringNotContainsString('First preview 2026 Concurrent Preview Shop', (string) $secondResponse->json('html'));

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
        $service = app(FooterConfigService::class);
        $service->ensureRegistered();

        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'footer',
            values: [
                'config' => $config,
            ],
        ));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveSiteIdentity(array $values): void
    {
        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'site',
            values: $values,
        ));
    }
}
