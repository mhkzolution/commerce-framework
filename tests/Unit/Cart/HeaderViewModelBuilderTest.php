<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Cart\DTO\CartData;
use Commerce\Cart\Services\HeaderViewModelBuilder;
use Commerce\Cart\Services\HomepageBrandingQuery;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Navigation\NavigationLinkData;
use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use RuntimeException;
use Tests\TestCase;

final class HeaderViewModelBuilderTest extends TestCase
{
    public function test_maps_website_settings_brand_and_shop_search_url(): void
    {
        $header = $this->builder(storeName: 'Harbor Shop')->build();

        $this->assertSame('Harbor Shop', $header->brand->name);
        $this->assertNull($header->brand->logoUrl);
        $this->assertNotSame('', $header->brand->homeUrl);
        $this->assertSame(route('storefront.shop.index'), $header->actions->searchUrl);
        $this->assertSame(route('storefront.cart.index'), $header->actions->cartUrl);
        $this->assertFalse($header->actions->authenticated);
        $this->assertSame(route('storefront.account.login'), $header->actions->loginUrl);
    }

    public function test_uses_main_navigation_links_when_present(): void
    {
        $header = $this->builder(
            navigation: $this->navigation([
                new NavigationLinkData('About', '/about', 'about'),
            ]),
        )->build();

        $this->assertCount(1, $header->navigation->links);
        $this->assertSame('About', $header->navigation->links[0]->label);
        $this->assertSame('/about', $header->navigation->links[0]->url);
    }

    public function test_empty_main_menu_fail_softs_shop_and_blog_but_not_cart(): void
    {
        $header = $this->builder(navigation: $this->navigation([]))->build();

        $labels = array_map(
            static fn (NavigationLinkData $link): string => $link->label,
            $header->navigation->links,
        );

        $this->assertContains('Shop', $labels);
        $this->assertNotContains('Cart', $labels);
    }

    public function test_search_query_comes_from_request(): void
    {
        $this->get(route('storefront.shop.index', ['search' => 'harbor mug']));

        $header = $this->builder()->build();

        $this->assertSame('harbor mug', $header->actions->searchQuery);
    }

    public function test_cart_count_comes_from_cart_service(): void
    {
        $header = $this->builder(itemCount: 4)->build();

        $this->assertSame(4, $header->actions->cartCount);
    }

    public function test_cart_failure_returns_zero_count(): void
    {
        $cart = $this->createMock(CartServiceInterface::class);
        $cart->method('get')->willThrowException(new RuntimeException('cart unavailable'));

        $header = $this->builder(cart: $cart)->build();

        $this->assertSame(0, $header->actions->cartCount);
    }

    public function test_currency_codes_are_mapped_when_converter_is_present(): void
    {
        $converter = $this->createMock(CurrencyConverterInterface::class);
        $converter->method('activeCurrencies')->willReturn([
            (object) ['code' => 'USD'],
            (object) ['code' => 'THB'],
        ]);
        $converter->method('baseCurrency')->willReturn('USD');

        $storage = $this->createMock(CartStorageInterface::class);
        $storage->method('currency')->willReturn('THB');

        $header = $this->builder(currencies: $converter, cartStorage: $storage)->build();

        $this->assertSame(['USD', 'THB'], $header->actions->currencyCodes);
        $this->assertSame('THB', $header->actions->currentCurrency);
        $this->assertSame(route('storefront.cart.currency'), $header->actions->currencyActionUrl);
    }

    public function test_missing_currency_converter_hides_switcher(): void
    {
        $header = $this->builder()->build();

        $this->assertSame([], $header->actions->currencyCodes);
        $this->assertNull($header->actions->currentCurrency);
        $this->assertNull($header->actions->currencyActionUrl);
    }

    private function builder(
        string $storeName = 'Harbor Shop',
        int $itemCount = 0,
        ?CartServiceInterface $cart = null,
        ?NavigationQueryServiceInterface $navigation = null,
        ?CurrencyConverterInterface $currencies = null,
        ?CartStorageInterface $cartStorage = null,
    ): HeaderViewModelBuilder {
        return new HeaderViewModelBuilder(
            branding: new HomepageBrandingQuery($this->settings($storeName)),
            cart: $cart ?? $this->cart($itemCount),
            navigation: $navigation,
            currencies: $currencies,
            cartStorage: $cartStorage,
        );
    }

    /**
     * @param  list<NavigationLinkData>  $links
     */
    private function navigation(array $links): NavigationQueryServiceInterface
    {
        return new class($links) implements NavigationQueryServiceInterface
        {
            /**
             * @param  list<NavigationLinkData>  $links
             */
            public function __construct(private readonly array $links) {}

            public function links(string $source): array
            {
                return $source === 'main' ? $this->links : [];
            }
        };
    }

    private function cart(int $itemCount): CartServiceInterface
    {
        $cart = $this->createMock(CartServiceInterface::class);
        $cart->method('get')->willReturn(new CartData(
            currency: 'USD',
            lines: [],
            subtotal: 0,
            itemCount: $itemCount,
        ));

        return $cart;
    }

    private function settings(string $storeName): SettingQueryServiceInterface
    {
        $settings = $this->createMock(SettingQueryServiceInterface::class);
        $settings->method('get')->willReturnCallback(
            static fn (string $key, mixed $default = null): mixed => $key === 'store.name' ? $storeName : $default,
        );
        $settings->method('has')->willReturn(true);
        $settings->method('getGroup')->willReturn([]);

        return $settings;
    }
}
