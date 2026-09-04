@props([
    'storeCurrencies' => [],
    'storeDisplayCurrency' => null,
    'currencyActionUrl' => null,
    'idPrefix' => 'menu',
])

@if ($storeCurrencies !== [] && is_string($currencyActionUrl) && $currencyActionUrl !== '')
    <form method="POST" action="{{ $currencyActionUrl }}" class="storefront-user-menu__field">
        @csrf
        <label class="storefront-user-menu__label" for="{{ $idPrefix }}-currency">{{ __('storefront::storefront.currency') }}</label>
        <select id="{{ $idPrefix }}-currency" name="currency" onchange="this.form.submit()" class="storefront-user-menu__select">
            @foreach ($storeCurrencies as $code)
                <option value="{{ $code }}" @selected($storeDisplayCurrency === $code)>
                    {{ $code }}
                </option>
            @endforeach
        </select>
    </form>
@endif
