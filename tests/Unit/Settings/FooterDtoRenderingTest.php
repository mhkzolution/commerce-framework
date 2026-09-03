<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Settings\Footer\DTO\FooterBrandData;
use Commerce\Settings\Footer\DTO\FooterLinkData;
use Commerce\Settings\Footer\DTO\FooterPageData;
use Commerce\Settings\Footer\DTO\FooterSectionData;
use Tests\TestCase;

final class FooterDtoRenderingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_footer_hides_heading_when_title_is_null_and_uses_aria_fallback(): void
    {
        $html = view('components.storefront.layout.partials.site-footer', [
            'footer' => new FooterPageData(
                enabled: true,
                className: 'cf-footer-cols-2',
                sections: [
                    new FooterSectionData(
                        id: 'social-primary',
                        type: 'social',
                        title: null,
                        ariaLabel: 'Social links',
                        links: [
                            new FooterLinkData(
                                label: 'Instagram',
                                url: 'https://example.com/instagram',
                                key: 'instagram',
                            ),
                        ],
                    ),
                ],
            ),
        ])->render();

        $this->assertStringNotContainsString('footer.section.missing_label', $html);
        $this->assertStringNotContainsString('<h2 class="storefront-site-footer__heading">', $html);
        $this->assertStringContainsString('aria-label="Social links"', $html);
        $this->assertStringContainsString('aria-label="Instagram"', $html);
    }

    public function test_footer_renders_navigation_and_cms_from_link_dtos(): void
    {
        $html = view('components.storefront.layout.partials.site-footer', [
            'footer' => new FooterPageData(
                enabled: true,
                className: 'cf-footer-cols-2',
                sections: [
                    new FooterSectionData(
                        id: 'main-nav',
                        type: 'navigation',
                        title: 'Navigation',
                        ariaLabel: 'Navigation',
                        links: [
                            new FooterLinkData(label: 'Shop', url: '/shop'),
                            new FooterLinkData(label: 'Brands', url: '/brands'),
                        ],
                    ),
                    new FooterSectionData(
                        id: 'help-pages',
                        type: 'cms',
                        title: 'Information',
                        ariaLabel: 'Information',
                        links: [
                            new FooterLinkData(label: 'FAQ', url: '/faq'),
                        ],
                    ),
                ],
            ),
        ])->render();

        $this->assertSame(2, substr_count($html, '<nav class="storefront-site-footer__section storefront-site-footer__section--links"'));
        $this->assertSame(2, substr_count($html, '<ul class="storefront-site-footer__list" role="list">'));
        $this->assertGreaterThanOrEqual(3, substr_count($html, '<li>'));
        $this->assertStringContainsString('>Navigation<', $html);
        $this->assertStringContainsString('>Information<', $html);
        $this->assertStringContainsString('>Shop<', $html);
        $this->assertStringContainsString('>FAQ<', $html);
    }

    public function test_disabled_or_empty_footer_renders_nothing(): void
    {
        $disabled = view('components.storefront.layout.partials.site-footer', [
            'footer' => new FooterPageData(enabled: false, className: '', sections: []),
        ])->render();

        $empty = view('components.storefront.layout.partials.site-footer', [
            'footer' => new FooterPageData(enabled: true, className: 'cf-footer-cols-4', sections: []),
        ])->render();

        $this->assertStringNotContainsString('<footer', $disabled);
        $this->assertStringNotContainsString('<footer', $empty);
    }
}
