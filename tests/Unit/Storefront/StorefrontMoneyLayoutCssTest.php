<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class StorefrontMoneyLayoutCssTest extends TestCase
{
    public function test_payable_amounts_and_pdp_chrome_use_appearance_tokens(): void
    {
        $card = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/product-card.css');
        $pdp = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/pdp.css');
        $shopper = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/shopper.css');
        $header = file_get_contents(dirname(__DIR__, 3).'/resources/css/storefront/header.css');

        $this->assertNotFalse($card);
        $this->assertNotFalse($pdp);
        $this->assertNotFalse($shopper);
        $this->assertNotFalse($header);

        $this->assertStringContainsString('.storefront-product-card__price', $card);
        $this->assertStringContainsString('color: var(--color-money)', $card);
        $this->assertStringNotContainsString(
            ".storefront-product-card__price {\n    font-size: 1rem;\n    font-weight: 600;\n    color: var(--color-danger);",
            $card,
        );
        $this->assertMatchesRegularExpression(
            '/\.storefront-product-card__compare\s*\{[^}]*color:\s*var\(--color-muted\)/s',
            $card,
        );

        $this->assertStringContainsString('color: var(--color-money)', $pdp);
        $this->assertStringContainsString('.storefront-buy-box__cta--cart', $pdp);
        $this->assertStringContainsString('var(--color-primary)', $pdp);
        $this->assertMatchesRegularExpression(
            '/\.storefront-mobile-buy-bar__button--buy:hover\s*\{[^}]*--color-primary-hover/s',
            $pdp,
        );
        $this->assertStringNotContainsString(
            '.storefront-buy-box__cta--cart {
    border: 1px solid var(--pdp-accent, var(--color-primary));',
            $pdp,
        );
        $this->assertStringContainsString(
            ".storefront-pdp .storefront-breadcrumb__list {\n    display: flex;",
            $pdp,
        );

        $this->assertStringContainsString('color: var(--color-money)', $shopper);
        $this->assertStringContainsString('color: var(--color-money)', $header);
    }
}
