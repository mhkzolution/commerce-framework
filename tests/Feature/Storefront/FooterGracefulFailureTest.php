<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Settings\Contracts\SettingServiceInterface;
use Commerce\Settings\Database\Seeders\SettingsSeeder;
use Commerce\Settings\DTO\UpdateSettingsGroupData;
use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\Drivers\CmsSectionDriver;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Services\FooterConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

final class FooterGracefulFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(SettingsSeeder::class);
        Carbon::setTestNow('2026-08-18 15:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_unknown_section_type_is_ignored_without_breaking_footer_rendering(): void
    {
        $this->saveStoreName('Graceful Store');

        $this->saveFooterConfig([
            'sections' => [
                [
                    'id' => 'mystery-block',
                    'type' => 'unknown',
                    'enabled' => true,
                    'settings' => [],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Copyright {year} {store_name}',
                    ],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Copyright 2026 Graceful Store', false)
            ->assertDontSee('mystery-block', false);
    }

    public function test_malformed_section_object_is_skipped_without_invalidating_footer(): void
    {
        $this->saveStoreName('Graceful Store');

        $this->saveFooterConfig([
            'sections' => [
                [
                    'id' => ['invalid'],
                    'type' => 'cms',
                    'enabled' => true,
                    'settings' => [
                        'page_ids' => [1],
                    ],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Stable footer {year}',
                    ],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Stable footer 2026', false)
            ->assertDontSee('footer.section.cms', false);
    }

    public function test_driver_exception_is_isolated_so_other_sections_still_render(): void
    {
        $this->app->bind(
            CmsSectionDriver::class,
            static fn (): FooterSectionDriver => new FooterThrowingCmsDriver
        );

        $this->saveStoreName('Graceful Store');

        $this->saveFooterConfig([
            'sections' => [
                [
                    'id' => 'help-pages',
                    'type' => 'cms',
                    'enabled' => true,
                    'settings' => [
                        'page_ids' => [1],
                    ],
                ],
                [
                    'id' => 'copyright',
                    'type' => 'copyright',
                    'enabled' => true,
                    'settings' => [
                        'template' => 'Copyright {year} {store_name}',
                    ],
                ],
            ],
        ]);

        $this->get(route('storefront.shop.index'))
            ->assertOk()
            ->assertSee('Copyright 2026 Graceful Store', false)
            ->assertDontSee('footer.section.cms', false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function saveFooterConfig(array $overrides): void
    {
        $config = app(FooterConfigService::class);
        $config->ensureRegistered();

        app(SettingServiceInterface::class)->updateGroup(new UpdateSettingsGroupData(
            group: 'footer',
            values: [
                'config' => $config->merge($overrides),
            ],
        ));

        $config->forgetResolved();
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

final class FooterThrowingCmsDriver implements FooterSectionDriver
{
    public function build(FooterSectionConfig $config): ?FooterSection
    {
        throw new RuntimeException('CMS driver exploded');
    }

    public function supportsMultiple(): bool
    {
        return true;
    }
}
