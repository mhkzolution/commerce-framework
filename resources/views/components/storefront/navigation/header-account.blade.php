@props([
    'storeLocales' => [],
    'storeDisplayLocale' => null,
    'storeCurrencies' => [],
    'storeDisplayCurrency' => null,
    'storeBaseCurrency' => null,
])

@php
    if (! isset($storeLocales) || $storeLocales === []) {
        $storeLocales = config('admin.locale.available', []);
    }
    $storeDisplayLocale = $storeDisplayLocale ?? app()->getLocale();

    if (! isset($storeCurrencies) && app()->bound(\Commerce\Contracts\Currency\CurrencyConverterInterface::class)) {
        $converter = app(\Commerce\Contracts\Currency\CurrencyConverterInterface::class);
        $storeCurrencies = $converter->activeCurrencies();
        $storeBaseCurrency = $converter->baseCurrency();

        if (app()->bound(\Commerce\Cart\Contracts\CartStorageInterface::class)) {
            $storeDisplayCurrency = app(\Commerce\Cart\Contracts\CartStorageInterface::class)->currency();
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'storefront-header-account']) }}>
    @auth('customer')
        <x-storefront.navigation.user-menu
            :store-locales="$storeLocales"
            :store-display-locale="$storeDisplayLocale"
            :store-currencies="$storeCurrencies ?? []"
            :store-display-currency="$storeDisplayCurrency ?? null"
            :store-base-currency="$storeBaseCurrency ?? null"
        />
    @else
        <x-storefront.navigation.guest-menu
            :store-locales="$storeLocales"
            :store-display-locale="$storeDisplayLocale"
            :store-currencies="$storeCurrencies ?? []"
            :store-display-currency="$storeDisplayCurrency ?? null"
            :store-base-currency="$storeBaseCurrency ?? null"
        />
    @endauth
</div>
