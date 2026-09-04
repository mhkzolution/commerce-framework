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
        private readonly ?StorefrontPrimaryNavigation $primaryNavigation = null,
    ) {}

    public function build(): HeaderViewData
    {
        return new HeaderViewData(
            brand: $this->brand(),
            navigation: $this->navigation(),
            actions: $this->actions(),
            primaryNav: $this->primaryNav(),
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
            searchQuery: $this->searchQuery(),
            customerName: $this->customerName(),
            customerInitials: $this->customerInitials(),
        );
    }

    /**
     * @return array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}
     */
    private function primaryNav(): array
    {
        $empty = [
            'promo' => ['enabled' => false, 'message' => '', 'dismissible' => true],
            'items' => [],
        ];

        if ($this->primaryNavigation === null) {
            return $this->primaryNavFromLinks($empty);
        }

        try {
            $nav = $this->primaryNavigation->build();
        } catch (Throwable) {
            return $this->primaryNavFromLinks($empty);
        }

        if (($nav['items'] ?? []) === []) {
            return $this->primaryNavFromLinks($nav);
        }

        return $this->appendMainLinks($nav);
    }

    /**
     * @param  array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}  $nav
     * @return array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}
     */
    private function primaryNavFromLinks(array $nav): array
    {
        $items = [];

        foreach ($this->navigation()->links as $link) {
            $items[] = [
                'id' => $link->key ?? $link->label,
                'label' => $link->label,
                'type' => 'link',
                'url' => $link->url,
                'active' => false,
                'columns' => [],
            ];
        }

        $nav['items'] = $items;

        return $nav;
    }

    /**
     * @param  array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}  $nav
     * @return array{promo: array{enabled: bool, message: string, dismissible: bool}, items: list<array<string, mixed>>}
     */
    private function appendMainLinks(array $nav): array
    {
        $seen = [];

        foreach ($nav['items'] as $item) {
            $seen[mb_strtolower((string) ($item['label'] ?? ''))] = true;
            $url = rtrim((string) ($item['url'] ?? ''), '/');
            if ($url !== '') {
                $seen[$url] = true;
            }
        }

        foreach ($this->navigation()->links as $link) {
            $labelKey = mb_strtolower($link->label);
            $urlKey = rtrim($link->url, '/');

            if (isset($seen[$labelKey]) || ($urlKey !== '' && isset($seen[$urlKey]))) {
                continue;
            }

            $nav['items'][] = [
                'id' => $link->key ?? $link->label,
                'label' => $link->label,
                'type' => 'link',
                'url' => $link->url,
                'active' => false,
                'columns' => [],
            ];
            $seen[$labelKey] = true;
            if ($urlKey !== '') {
                $seen[$urlKey] = true;
            }
        }

        return $nav;
    }

    private function customerName(): string
    {
        try {
            $user = Auth::guard('customer')->user();
        } catch (Throwable) {
            return '';
        }

        return is_object($user) && isset($user->name) ? trim((string) $user->name) : '';
    }

    private function customerInitials(): string
    {
        $name = $this->customerName();
        if ($name === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $name) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $initials;
    }

    private function searchQuery(): string
    {
        try {
            $search = trim((string) request()->query('search', ''));
        } catch (Throwable) {
            return '';
        }

        return $search;
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
