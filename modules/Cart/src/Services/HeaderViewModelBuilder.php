<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Contracts\Navigation\NavigationLinkData;
use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Contracts\Storefront\HeaderActionData;
use Commerce\Contracts\Storefront\HeaderBrandData;
use Commerce\Contracts\Storefront\HeaderNavigationData;
use Commerce\Contracts\Storefront\HeaderViewData;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Throwable;

final class HeaderViewModelBuilder
{
    public function __construct(
        private readonly HomepageBrandingQuery $branding,
        private readonly CartServiceInterface $cart,
        private readonly ?NavigationQueryServiceInterface $navigation = null,
        private readonly ?CurrencyConverterInterface $currencies = null,
        private readonly ?CartStorageInterface $cartStorage = null,
    ) {}

    public function build(): HeaderViewData
    {
        return new HeaderViewData(
            brand: $this->brand(),
            navigation: $this->navigation(),
            actions: $this->actions(),
        );
    }

    private function brand(): HeaderBrandData
    {
        $branding = $this->branding->current();

        return new HeaderBrandData(
            name: $branding->name,
            logoUrl: $branding->logoUrl,
            homeUrl: $this->url('storefront.home') ?? $this->url('storefront.shop.index') ?? '/',
        );
    }

    private function navigation(): HeaderNavigationData
    {
        $links = $this->mainLinks();

        if ($links === []) {
            $links = $this->failSoftLinks();
        }

        return new HeaderNavigationData($links);
    }

    /**
     * @return list<NavigationLinkData>
     */
    private function mainLinks(): array
    {
        if ($this->navigation === null) {
            return [];
        }

        try {
            return $this->navigation->links('main');
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return list<NavigationLinkData>
     */
    private function failSoftLinks(): array
    {
        $links = [];

        $shopUrl = $this->url('storefront.shop.index');
        if ($shopUrl !== null) {
            $links[] = new NavigationLinkData('Shop', $shopUrl, 'shop');
        }

        $blogUrl = $this->url('storefront.cms.posts.index');
        if ($blogUrl !== null) {
            $links[] = new NavigationLinkData('Blog', $blogUrl, 'blog');
        }

        return $links;
    }

    private function actions(): HeaderActionData
    {
        [$codes, $current, $currencyActionUrl] = $this->currency();

        return new HeaderActionData(
            searchUrl: $this->url('storefront.shop.index') ?? '/',
            cartUrl: $this->url('storefront.cart.index') ?? '/',
            cartCount: $this->cartCount(),
            authenticated: $this->authenticated(),
            accountUrl: $this->url('storefront.account') ?? '/',
            loginUrl: $this->url('storefront.account.login') ?? '/',
            logoutUrl: $this->url('storefront.account.logout') ?? '/',
            currencyCodes: $codes,
            currentCurrency: $current,
            currencyActionUrl: $currencyActionUrl,
        );
    }

    private function authenticated(): bool
    {
        try {
            return Auth::guard('customer')->check();
        } catch (Throwable) {
            return false;
        }
    }

    private function cartCount(): int
    {
        try {
            return max(0, $this->cart->get()->itemCount);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return array{0: list<string>, 1: ?string, 2: ?string}
     */
    private function currency(): array
    {
        if ($this->currencies === null) {
            return [[], null, null];
        }

        try {
            $codes = [];

            foreach ($this->currencies->activeCurrencies() as $currency) {
                $code = trim((string) ($currency->code ?? ''));
                if ($code !== '') {
                    $codes[] = $code;
                }
            }

            $current = $this->cartStorage?->currency() ?? $this->currencies->baseCurrency();
            $actionUrl = $this->url('storefront.cart.currency');

            if ($codes === [] || $actionUrl === null) {
                return [[], null, null];
            }

            return [$codes, $current !== '' ? $current : null, $actionUrl];
        } catch (Throwable) {
            return [[], null, null];
        }
    }

    private function url(string $name): ?string
    {
        try {
            if (! Route::has($name)) {
                return null;
            }

            return route($name);
        } catch (Throwable) {
            return null;
        }
    }
}
