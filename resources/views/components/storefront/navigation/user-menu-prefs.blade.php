@props([
    'storeLocales' => [],
    'storeDisplayLocale' => null,
    'storeCurrencies' => [],
    'storeDisplayCurrency' => null,
    'storeBaseCurrency' => null,
    'idPrefix' => 'menu',
])

@if (!empty($storeLocales) && Route::has('storefront.locale'))
    <form method="POST" action="{{ route('storefront.locale') }}" class="storefront-user-menu__field">
        @csrf
        <label class="storefront-user-menu__label" for="{{ $idPrefix }}-locale">{{ __('storefront::storefront.language') }}</label>
        <select id="{{ $idPrefix }}-locale" name="locale" onchange="this.form.submit()" class="storefront-user-menu__select">
            @foreach ($storeLocales as $code => $label)
                <option value="{{ $code }}" @selected(($storeDisplayLocale ?? app()->getLocale()) === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
@endif

@if (!empty($storeCurrencies) && Route::has('storefront.cart.currency'))
    <form method="POST" action="{{ route('storefront.cart.currency') }}" class="storefront-user-menu__field">
        @csrf
        <label class="storefront-user-menu__label" for="{{ $idPrefix }}-currency">{{ __('storefront::storefront.currency') }}</label>
        <select id="{{ $idPrefix }}-currency" name="currency" onchange="this.form.submit()" class="storefront-user-menu__select">
            @foreach ($storeCurrencies as $currency)
                <option value="{{ $currency->code }}" @selected(($storeDisplayCurrency ?? $storeBaseCurrency ?? 'THB') === $currency->code)>
                    {{ $currency->code }}
                </option>
            @endforeach
        </select>
    </form>
@endif
