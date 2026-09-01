@php
    use Commerce\Cart\Contracts\CartStorageInterface;
    use Commerce\Cart\Support\StorefrontMoney;
    use Commerce\Contracts\Currency\CurrencyConverterInterface;

    $resolvedCurrency = $currency ?? null;

    if ($resolvedCurrency === null && app()->bound(CartStorageInterface::class)) {
        $resolvedCurrency = app(CartStorageInterface::class)->currency();
    }

    if ($resolvedCurrency === null || $resolvedCurrency === '') {
        $resolvedCurrency = app()->bound(CurrencyConverterInterface::class)
            ? app(CurrencyConverterInterface::class)->baseCurrency()
            : config('cart.default_currency', 'THB');
    }
@endphp

<script>
    window.__storefrontMoney = @json(StorefrontMoney::jsPayload($resolvedCurrency));
</script>
