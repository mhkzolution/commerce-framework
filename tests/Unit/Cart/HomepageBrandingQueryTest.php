<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\Services\HomepageBrandingQuery;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Tests\TestCase;

final class HomepageBrandingQueryTest extends TestCase
{
    public function test_store_name_wins(): void
    {
        $query = new HomepageBrandingQuery($this->settings('Harbor Shop'));

        $this->assertSame('Harbor Shop', $query->current()->name);
    }

    public function test_missing_store_name_falls_back_to_app_name(): void
    {
        config(['app.name' => 'Harbor App']);

        $query = new HomepageBrandingQuery($this->settings(null));

        $this->assertSame('Harbor App', $query->current()->name);
    }

    public function test_all_missing_falls_back_to_commerce_framework_and_never_throws(): void
    {
        config(['app.name' => '']);

        $query = new HomepageBrandingQuery($this->settings(null));

        $this->assertSame('Commerce Framework', $query->current()->name);
        $this->assertNull($query->current()->logoUrl);
    }

    public function test_settings_failure_never_throws(): void
    {
        config(['app.name' => '']);

        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willThrowException(new \RuntimeException('settings unavailable'));
        $settings->method('has')->willReturn(false);
        $settings->method('getGroup')->willReturn([]);

        $query = new HomepageBrandingQuery($settings);

        $this->assertSame('Commerce Framework', $query->current()->name);
        $this->assertNull($query->current()->logoUrl);
    }

    private function settings(?string $storeName): SettingQueryServiceInterface
    {
        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $key === 'store.name' ? $storeName : $default,
        );
        $settings->method('has')->willReturn($storeName !== null);
        $settings->method('getGroup')->willReturn([]);

        return $settings;
    }
}
