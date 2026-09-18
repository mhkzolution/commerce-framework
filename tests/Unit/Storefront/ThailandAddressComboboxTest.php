<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use PHPUnit\Framework\TestCase;

final class ThailandAddressComboboxTest extends TestCase
{
    public function test_address_script_builds_a_searchable_combobox(): void
    {
        $root = dirname(__DIR__, 3);
        $js = file_get_contents($root.'/resources/js/storefront/address.js');
        $css = file_get_contents($root.'/resources/css/storefront/shopper.css');

        $this->assertNotFalse($js);
        $this->assertNotFalse($css);
        $this->assertStringContainsString("setAttribute('role', 'combobox')", $js);
        $this->assertStringContainsString("setAttribute('aria-controls'", $js);
        $this->assertStringContainsString("setAttribute('aria-activedescendant'", $js);
        $this->assertStringContainsString('select.tabIndex = -1', $js);
        $this->assertStringContainsString('ArrowDown', $js);
        $this->assertStringContainsString('enhanceCombobox', $js);
        $this->assertStringContainsString('.storefront-combobox', $css);
        $this->assertStringContainsString('--space-8: 0.5rem', $css);
        $this->assertStringContainsString('padding: var(--space-8)', $css);
        $this->assertStringContainsString('padding: var(--space-12) var(--space-16)', $css);
        $this->assertStringNotContainsString('tom-select', $js);
        $this->assertStringNotContainsString('choices.js', $js);
    }
}
