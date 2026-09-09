<?php

declare(strict_types=1);

namespace Tests\Unit\Product;

use Tests\TestCase;

final class WorkspaceAttributesPanelTest extends TestCase
{
    public function test_non_axis_select_uses_checkbox(): void
    {
        $js = file_get_contents(base_path('resources/js/admin/product-workspace/attributes-panel.js'));
        $this->assertNotFalse($js);
        $this->assertStringNotContainsString("inputType = usedForVariations ? 'checkbox' : 'radio'", $js);
        $this->assertStringContainsString("const inputType = 'checkbox'", $js);
        $this->assertStringContainsString('type="${inputType}"', $js);
    }
}
