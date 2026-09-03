<?php

declare(strict_types=1);

namespace Tests\Unit\Barcode;

use Commerce\Barcode\Services\BarcodeOwnerResolver;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Tests\TestCase;

final class BarcodeOwnerResolverTest extends TestCase
{
    public function test_missing_seller_falls_back_to_store_name(): void
    {
        $resolver = new BarcodeOwnerResolver($this->settings('Harbor Shop'));

        $this->assertSame('Harbor Shop', $resolver->resolve(null));
        $this->assertSame('Harbor Shop', $resolver->resolveForSeller('missing-seller-uuid'));
    }

    public function test_missing_store_name_falls_back_to_app_name(): void
    {
        config(['app.name' => 'Harbor App']);

        $resolver = new BarcodeOwnerResolver($this->settings(null));

        $this->assertSame('Harbor App', $resolver->resolve(null));
        $this->assertSame('Harbor App', $resolver->resolveForSeller(null));
    }

    public function test_all_missing_falls_back_to_store_and_never_throws(): void
    {
        config(['app.name' => '']);

        $resolver = new BarcodeOwnerResolver($this->settings(null));

        $this->assertSame('Store', $resolver->resolve(null));
        $this->assertSame('Store', $resolver->resolveForSeller(null));
        $this->assertSame('Store', $resolver->resolveForSeller(''));
    }

    public function test_settings_failure_never_throws(): void
    {
        config(['app.name' => '']);

        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willThrowException(new \RuntimeException('settings unavailable'));
        $settings->method('has')->willReturn(false);
        $settings->method('getGroup')->willReturn([]);

        $resolver = new BarcodeOwnerResolver($settings);

        $this->assertSame('Store', $resolver->resolve(null));
        $this->assertSame('Store', $resolver->resolveForSeller('any-uuid'));
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
