<?php

declare(strict_types=1);

namespace Tests\Unit\Footer;

use Commerce\Settings\Footer\Contracts\FooterSectionDriver;
use Commerce\Settings\Footer\Drivers\CmsSectionDriver;
use Commerce\Settings\Footer\DTO\FooterBuildContext;
use Commerce\Settings\Footer\DTO\FooterSection;
use Commerce\Settings\Footer\DTO\FooterSectionConfig;
use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use Commerce\Settings\Services\FooterSectionManager;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

final class FooterSectionManagerTest extends TestCase
{
    #[Test]
    public function it_ignores_unknown_section_types(): void
    {
        Log::spy();

        $sections = $this->manager()->buildSections([
            [
                'id' => 'quick-links',
                'type' => 'navigation',
                'enabled' => true,
                'settings' => [
                    'source' => 'main',
                    'visibility_mode' => 'all',
                ],
            ],
            [
                'id' => 'mystery',
                'type' => 'unknown',
                'enabled' => true,
            ],
        ], $this->context());

        $this->assertCount(1, $sections);
        $this->assertSame('quick-links', $sections[0]->id);
        $this->assertSame('navigation', $sections[0]->type);
    }

    #[Test]
    public function it_skips_duplicate_ids_and_continues_building(): void
    {
        Log::spy();

        $sections = $this->manager()->buildSections([
            [
                'id' => 'duplicate-id',
                'type' => 'navigation',
                'settings' => [
                    'source' => 'main',
                ],
            ],
            [
                'id' => 'duplicate-id',
                'type' => 'cms',
                'settings' => [
                    'page_ids' => [2],
                ],
            ],
            [
                'id' => 'help-pages',
                'type' => 'cms',
                'settings' => [
                    'page_ids' => [1],
                ],
            ],
        ], $this->context());

        $this->assertSame(['duplicate-id', 'help-pages'], array_map(
            static fn ($section) => $section->id,
            $sections,
        ));

        Log::shouldHaveReceived('warning')
            ->withArgs(static function (string $message, array $context): bool {
                return $message === 'footer.section.cms.skipped'
                    && $context['section_id'] === 'duplicate-id'
                    && $context['type'] === 'cms'
                    && $context['reason'] === 'duplicate_id';
            })
            ->once();
    }

    #[Test]
    public function it_enforces_supports_multiple_for_singleton_sections(): void
    {
        Log::spy();

        $sections = $this->manager()->buildSections([
            [
                'id' => 'brand-primary',
                'type' => 'brand',
                'settings' => [
                    'show_store_name' => true,
                    'show_logo' => false,
                    'show_description' => false,
                ],
            ],
            [
                'id' => 'brand-secondary',
                'type' => 'brand',
                'settings' => [
                    'show_store_name' => true,
                    'show_logo' => false,
                    'show_description' => false,
                ],
            ],
            [
                'id' => 'quick-links',
                'type' => 'navigation',
                'settings' => [
                    'source' => 'main',
                ],
            ],
        ], $this->context());

        $this->assertSame(['brand-primary', 'quick-links'], array_map(
            static fn ($section) => $section->id,
            $sections,
        ));

        Log::shouldHaveReceived('warning')
            ->withArgs(static function (string $message, array $context): bool {
                return $message === 'footer.section.brand.skipped'
                    && $context['section_id'] === 'brand-secondary'
                    && $context['type'] === 'brand'
                    && $context['reason'] === 'multiple_not_supported';
            })
            ->once();
    }

    #[Test]
    public function it_catches_driver_exceptions_logs_them_and_continues(): void
    {
        Log::spy();
        $this->app->bind(
            CmsSectionDriver::class,
            static fn (): FooterSectionDriver => new ThrowingCmsDriver
        );

        $sections = $this->manager()->buildSections([
            [
                'id' => 'help-pages',
                'type' => 'cms',
                'settings' => [
                    'page_ids' => [1],
                ],
            ],
            [
                'id' => 'quick-links',
                'type' => 'navigation',
                'settings' => [
                    'source' => 'main',
                ],
            ],
        ], $this->context());

        $this->assertCount(1, $sections);
        $this->assertSame('quick-links', $sections[0]->id);

        Log::shouldHaveReceived('warning')
            ->withArgs(static function (string $message, array $context): bool {
                return $message === 'footer.section.cms.failed'
                    && $context['section_id'] === 'help-pages'
                    && $context['type'] === 'cms'
                    && $context['reason'] === 'driver_exception'
                    && $context['error'] === 'CMS driver exploded';
            })
            ->once();
    }

    private function manager(): FooterSectionManager
    {
        return new FooterSectionManager(
            new FooterSectionRegistry,
            $this->app,
        );
    }

    private function context(): FooterBuildContext
    {
        return new FooterBuildContext(
            device: 'desktop',
            planTier: 'free',
            featureFlags: ['marketplace' => false],
            serviceAvailability: ['cms' => true],
            meta: [
                'footer_navigation' => [
                    'main' => [
                        ['id' => 'shop', 'label' => 'Shop', 'url' => '/shop'],
                    ],
                ],
                'cms_pages' => [
                    ['id' => 1, 'title' => 'Help', 'url' => '/pages/help'],
                    ['id' => 2, 'title' => 'Returns', 'url' => '/pages/returns'],
                ],
            ],
        );
    }
}

final class ThrowingCmsDriver implements FooterSectionDriver
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
