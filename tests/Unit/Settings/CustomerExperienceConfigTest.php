<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Settings\Services\CustomerExperienceConfig;
use PHPUnit\Framework\TestCase;

final class CustomerExperienceConfigTest extends TestCase
{
    public function test_merge_keeps_defaults_and_overrides_selected_keys(): void
    {
        $config = new CustomerExperienceConfig(
            $this->createMock(SettingQueryServiceInterface::class),
            $this->createMock(SettingRegistryServiceInterface::class),
        );

        $merged = $config->merge([
            'quickView' => [
                'enabled' => false,
                'showSku' => true,
            ],
            'notifications' => [
                'duration' => 10,
                'position' => 'top-left',
                'lowStock' => true,
            ],
            'navigation' => [
                'style' => 'pill',
                'showAfter' => '750',
            ],
        ]);

        $this->assertFalse($merged['quickView']['enabled']);
        $this->assertTrue($merged['quickView']['showSku']);
        $this->assertTrue($merged['quickView']['showImages']);
        $this->assertSame(10, $merged['notifications']['duration']);
        $this->assertSame('top-left', $merged['notifications']['position']);
        $this->assertTrue($merged['notifications']['lowStock']);
        $this->assertFalse($merged['notifications']['review']);
        $this->assertSame('pill', $merged['navigation']['style']);
        $this->assertSame(750, $merged['navigation']['showAfter']);
        $this->assertTrue($merged['productCard']['enabled']);
    }

    public function test_defaults_match_marketplace_starting_point(): void
    {
        $config = new CustomerExperienceConfig(
            $this->createMock(SettingQueryServiceInterface::class),
            $this->createMock(SettingRegistryServiceInterface::class),
        );

        $defaults = $config->defaults();

        $this->assertTrue($defaults['quickView']['enabled']);
        $this->assertTrue($defaults['notifications']['newProduct']);
        $this->assertTrue($defaults['notifications']['promotion']);
        $this->assertFalse($defaults['notifications']['lowStock']);
        $this->assertSame(5, $defaults['notifications']['duration']);
        $this->assertSame('bottom-right', $defaults['notifications']['position']);
        $this->assertTrue($defaults['navigation']['backToTop']);
        $this->assertSame(500, $defaults['navigation']['showAfter']);
    }
}
