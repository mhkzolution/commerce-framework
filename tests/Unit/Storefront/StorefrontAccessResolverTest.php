<?php

declare(strict_types=1);

namespace Tests\Unit\Storefront;

use Commerce\Cart\Services\StorefrontAccessResolver;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Contracts\Settings\SettingRegistryServiceInterface;
use Commerce\Contracts\Storefront\StoreAccessPolicyInterface;
use Commerce\Contracts\Storefront\StorefrontAccessContext;
use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Customers\Models\Customer;
use Commerce\Settings\Services\StoreVisibilityConfig;
use Mockery;
use Tests\TestCase;

final class StorefrontAccessResolverTest extends TestCase
{
    public function test_public_guest_can_view_catalog_prices_and_purchase(): void
    {
        $context = $this->resolve(StoreVisibility::Public, customer: null);

        $this->assertTrue($context->canViewCatalog);
        $this->assertTrue($context->canViewPrices);
        $this->assertTrue($context->canPurchase);
        $this->assertSame('index,follow', $context->robots());
        $this->assertSame('index,follow', $context->robots('index,follow'));
    }

    public function test_catalog_guest_sees_catalog_but_not_prices_or_purchase(): void
    {
        $context = $this->resolve(StoreVisibility::Catalog, customer: null);

        $this->assertTrue($context->canViewCatalog);
        $this->assertFalse($context->canViewPrices);
        $this->assertFalse($context->canPurchase);
        $this->assertFalse($context->authenticated);
        $this->assertSame('index,follow', $context->robots());
    }

    public function test_catalog_customer_can_see_prices_and_purchase(): void
    {
        $context = $this->resolve(StoreVisibility::Catalog, customer: $this->customer());

        $this->assertTrue($context->canViewPrices);
        $this->assertTrue($context->canPurchase);
    }

    public function test_members_guest_cannot_view_catalog_and_is_noindex(): void
    {
        $context = $this->resolve(StoreVisibility::Members, customer: null);

        $this->assertFalse($context->canViewCatalog);
        $this->assertFalse($context->canViewPrices);
        $this->assertFalse($context->canPurchase);
        $this->assertSame('noindex,nofollow', $context->robots('index,follow'));
    }

    public function test_members_customer_has_full_access(): void
    {
        $context = $this->resolve(StoreVisibility::Members, customer: $this->customer());

        $this->assertTrue($context->canViewCatalog);
        $this->assertTrue($context->canViewPrices);
        $this->assertTrue($context->canPurchase);
        $this->assertTrue($context->entitled);
        $this->assertSame('noindex,nofollow', $context->robots());
    }

    public function test_private_guest_is_blocked_and_noindex(): void
    {
        $context = $this->resolve(StoreVisibility::Private, customer: null);

        $this->assertFalse($context->authenticated);
        $this->assertFalse($context->entitled);
        $this->assertFalse($context->canViewCatalog);
        $this->assertSame('noindex,nofollow', $context->robots());
    }

    public function test_private_customer_without_policy_is_not_entitled(): void
    {
        $policy = Mockery::mock(StoreAccessPolicyInterface::class);
        $policy->shouldReceive('canAccessStore')->andReturn(false);

        $context = $this->resolve(StoreVisibility::Private, customer: $this->customer(), policy: $policy);

        $this->assertTrue($context->authenticated);
        $this->assertFalse($context->entitled);
        $this->assertFalse($context->canViewPrices);
        $this->assertFalse($context->canPurchase);
    }

    public function test_private_entitled_customer_has_full_access(): void
    {
        $context = $this->resolve(StoreVisibility::Private, customer: $this->customer());

        $this->assertTrue($context->entitled);
        $this->assertTrue($context->canViewPrices);
        $this->assertTrue($context->canPurchase);
    }

    public function test_without_prices_nulls_pricing_keys_for_guests(): void
    {
        $context = $this->resolve(StoreVisibility::Catalog, customer: null);

        $concealed = $context->withoutPrices([
            'name' => 'Widget',
            'price' => 19900,
            'sale_price' => 15900,
            'formatted_price' => '199.00 THB',
        ]);

        $this->assertSame('Widget', $concealed['name']);
        $this->assertNull($concealed['price']);
        $this->assertNull($concealed['sale_price']);
        $this->assertNull($concealed['formatted_price']);
        $this->assertTrue($concealed['prices_hidden']);
    }

    private function resolve(
        StoreVisibility $mode,
        ?object $customer,
        ?StoreAccessPolicyInterface $policy = null,
    ): StorefrontAccessContext {
        $settings = Mockery::mock(SettingQueryServiceInterface::class);
        $settings->shouldReceive('get')->with(StoreVisibilityConfig::SETTING_KEY, StoreVisibility::Public->value)->andReturn($mode->value);
        $visibility = new StoreVisibilityConfig(
            $settings,
            Mockery::mock(SettingRegistryServiceInterface::class),
        );

        $policy ??= new class implements StoreAccessPolicyInterface
        {
            public function canAccessStore(?object $customer): bool
            {
                return $customer !== null;
            }
        };

        return (new StorefrontAccessResolver($visibility, $policy))->resolve($customer);
    }

    private function customer(): Customer
    {
        $customer = new Customer();
        $customer->email = 'member@example.com';
        $customer->status = 'active';

        return $customer;
    }
}
