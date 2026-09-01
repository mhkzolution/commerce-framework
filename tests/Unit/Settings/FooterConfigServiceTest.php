<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Settings\Services\FooterConfigService;
use PHPUnit\Framework\TestCase;

final class FooterConfigServiceTest extends TestCase
{
    public function test_defaults_contains_schema_version(): void
    {
        $service = new FooterConfigService(
            $this->createMock(SettingQueryServiceInterface::class),
            $this->createMock(SettingRegistryServiceInterface::class),
        );

        $defaults = $service->defaults();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('schema_version', $defaults);
        $this->assertSame(1, $defaults['schema_version']);
    }

    public function test_merge_overlays_primitives_safely(): void
    {
        $service = new FooterConfigService(
            $this->createMock(SettingQueryServiceInterface::class),
            $this->createMock(SettingRegistryServiceInterface::class),
        );

        $merged = $service->merge([
            'enabled' => false,
            'layout' => [
                'columns' => 6,
                'divider_style' => 'dashed',
            ],
        ]);

        $this->assertFalse($merged['enabled']);
        $this->assertSame(6, $merged['layout']['columns']);
        $this->assertSame('dashed', $merged['layout']['divider_style']);

        // unchanged defaults
        $this->assertSame('footer', $merged['layout']['surface']);
    }

    public function test_merge_ignores_invalid_override_types_without_crashing(): void
    {
        $service = new FooterConfigService(
            $this->createMock(SettingQueryServiceInterface::class),
            $this->createMock(SettingRegistryServiceInterface::class),
        );

        $merged = $service->merge([
            'enabled' => 'not-a-bool',
            'layout' => [
                'columns' => ['bad', 'type'],
            ],
            'sections' => 'not-an-array',
        ]);

        // enabled should remain a boolean and not crash
        $this->assertIsBool($merged['enabled']);

        // columns override has invalid type -> keep default
        $this->assertSame(4, $merged['layout']['columns']);

        // invalid sections override should be ignored
        $this->assertIsArray($merged['sections']);
        $this->assertNotEmpty($merged['sections']);
    }
}
