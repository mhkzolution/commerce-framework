<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Settings\Services\FooterBrandingQuery;
use Tests\TestCase;

final class FooterBrandingQueryTest extends TestCase
{
    public function test_store_name_wins(): void
    {
        $branding = (new FooterBrandingQuery($this->settings([
            'store.name' => 'Harbor Shop',
        ])))->current();

        $this->assertSame('Harbor Shop', $branding->displayName);
        $this->assertNull($branding->logoUrl);
        $this->assertNull($branding->description);
    }

    public function test_missing_store_name_falls_back_to_app_name(): void
    {
        config(['app.name' => 'Harbor App']);

        $this->assertSame('Harbor App', (new FooterBrandingQuery($this->settings([])))->current()->displayName);
    }

    public function test_all_missing_falls_back_to_commerce_framework_and_never_throws(): void
    {
        config(['app.name' => '']);

        $branding = (new FooterBrandingQuery($this->settings([])))->current();

        $this->assertSame('Commerce Framework', $branding->displayName);
        $this->assertNull($branding->logoUrl);
        $this->assertNull($branding->description);
    }

    public function test_settings_failure_never_throws(): void
    {
        config(['app.name' => '']);

        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willThrowException(new \RuntimeException('settings unavailable'));
        $settings->method('has')->willReturn(false);
        $settings->method('getGroup')->willReturn([]);

        $branding = (new FooterBrandingQuery($settings))->current();

        $this->assertSame('Commerce Framework', $branding->displayName);
        $this->assertNull($branding->logoUrl);
    }

    public function test_description_reads_store_description(): void
    {
        $branding = (new FooterBrandingQuery($this->settings([
            'store.name' => 'Harbor Shop',
            'store.description' => 'Harbor goods',
        ])))->current();

        $this->assertSame('Harbor goods', $branding->description);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function settings(array $values): SettingQueryServiceInterface
    {
        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $values[$key] ?? $default,
        );
        $settings->method('has')->willReturnCallback(
            static fn (string $key): bool => array_key_exists($key, $values),
        );
        $settings->method('getGroup')->willReturn([]);

        return $settings;
    }
}
