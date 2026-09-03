<?php

declare(strict_types=1);

namespace Tests\Unit\Footer;

use Commerce\Settings\Footer\Registry\FooterSectionRegistry;
use PHPUnit\Framework\TestCase;

final class FooterSectionRegistryTest extends TestCase
{
    public function test_registry_exposes_required_driver_mappings(): void
    {
        $registry = new FooterSectionRegistry;

        $this->assertSame([
            'brand' => 'Commerce\\Settings\\Footer\\Drivers\\BrandSectionDriver',
            'navigation' => 'Commerce\\Settings\\Footer\\Drivers\\NavigationSectionDriver',
            'cms' => 'Commerce\\Settings\\Footer\\Drivers\\CmsSectionDriver',
            'social' => 'Commerce\\Settings\\Footer\\Drivers\\SocialSectionDriver',
            'marketplace' => 'Commerce\\Settings\\Footer\\Drivers\\MarketplaceSectionDriver',
            'copyright' => 'Commerce\\Settings\\Footer\\Drivers\\CopyrightSectionDriver',
            'powered_by' => 'Commerce\\Settings\\Footer\\Drivers\\PoweredBySectionDriver',
        ], $registry->drivers());
    }

    public function test_registry_returns_template_defaults_by_type_and_template_id(): void
    {
        $registry = new FooterSectionRegistry;

        $brand = $registry->template('brand');
        $navigationDefaults = $registry->defaultSettings('navigation');
        $copyrightDefaults = $registry->defaultSettings('copyright');

        $this->assertNotNull($brand);
        $this->assertSame('settings::footer.section.brand', $brand['label_key']);
        $this->assertSame([
            'show_logo' => true,
            'show_store_name' => true,
            'show_description' => true,
        ], $brand['default_settings']);
        $this->assertSame([
            'source' => 'main',
            'max_links' => 6,
            'visibility_mode' => 'footer_enabled_only',
        ], $navigationDefaults);
        $this->assertSame([
            'template' => '© {year} {store_name}',
        ], $copyrightDefaults);
    }

    public function test_registry_wires_multiplicity_metadata(): void
    {
        $registry = new FooterSectionRegistry;

        $this->assertFalse($registry->supportsMultiple('brand'));
        $this->assertTrue($registry->supportsMultiple('navigation'));
        $this->assertTrue($registry->supportsMultiple('cms'));
        $this->assertFalse($registry->supportsMultiple('social'));
        $this->assertFalse($registry->supportsMultiple('unknown'));
    }
}
