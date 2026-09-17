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
        $this->assertStringContainsString('enhanceCombobox', $js);
        $this->assertStringContainsString('.storefront-combobox', $css);
        $this->assertStringNotContainsString('tom-select', $js);
        $this->assertStringNotContainsString('choices.js', $js);
    }
}
