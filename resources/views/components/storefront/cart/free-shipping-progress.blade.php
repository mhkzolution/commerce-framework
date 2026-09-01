@props([
    'progress',
    'currency',
])

@if ($progress)
    <div {{ $attributes->merge(['class' => 'storefront-free-shipping']) }} data-free-shipping>
        <div class="storefront-free-shipping__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ (int) $progress['percent'] }}">
            <div class="storefront-free-shipping__bar" style="width: {{ $progress['percent'] }}%"></div>
        </div>
        <p class="storefront-free-shipping__message">
            @if ($progress['qualified'])
                {{ __('storefront::storefront.free_shipping_qualified') }}
            @else
                {{ __('storefront::storefront.free_shipping_progress', [
                    'amount' => \Commerce\Cart\Support\StorefrontMoney::formatMinor((int) $progress['remaining'], (string) $currency),
                ]) }}
            @endif
        </p>
    </div>
@endif
