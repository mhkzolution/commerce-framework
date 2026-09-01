@props([
    'product',
    'variant',
    'variants' => [],
    'variantOptionAxes' => [],
    'priceSummary' => [],
    'available' => 0,
    'displayCurrency',
    'baseCurrency',
    'currencyConverter' => null,
    'visibleAttributes' => [],
    'deliverySummary' => null,
    'galleryItems' => [],
])

@php
    use Commerce\Cart\Support\StorefrontMoney;

    $convert = static function (float $amount) use ($currencyConverter, $displayCurrency, $baseCurrency): float {
        if ($currencyConverter && $displayCurrency !== $baseCurrency) {
            return (float) $currencyConverter->convert($amount, $baseCurrency, $displayCurrency);
        }

        return $amount;
    };

    $formatAmount = static function (float $amount) use ($convert, $displayCurrency): string {
        return StorefrontMoney::formatMajor($convert($amount), (string) $displayCurrency, 0);
    };

    $summaryMin = (float) ($priceSummary['min'] ?? ($variant?->price ?? 0));
    $summaryMax = (float) ($priceSummary['max'] ?? $summaryMin);
    $compareMin = isset($priceSummary['compare_min']) ? (float) $priceSummary['compare_min'] : null;
    $compareMax = isset($priceSummary['compare_max']) ? (float) $priceSummary['compare_max'] : null;
    $discountPercent = $priceSummary['discount_percent'] ?? null;

    $displayPrice = $variant?->price ?? $summaryMin;
    $compareAt = $variant?->compare_at_price;
    if ($variant) {
        $displayPrice = $convert((float) $displayPrice);
        if ($compareAt !== null) {
            $compareAt = $convert((float) $compareAt);
        }
    }

    $rating = data_get($product->meta, 'rating');
    $reviewCount = data_get($product->meta, 'review_count');
    $soldCount = data_get($product->meta, 'sold_count');
    $inStock = $available > 0;
    $showRange = count($variants) > 1 && $summaryMin !== $summaryMax;
@endphp

<aside {{ $attributes->merge(['class' => 'storefront-buy-box storefront-buy-box--market']) }} data-buy-box>
    <h1 class="storefront-buy-box__title">{{ $product->name }}</h1>

    <div class="storefront-buy-box__meta-row">
        @if ($rating !== null && (float) $rating > 0)
            <x-storefront.rating :rating="$rating" :count="$reviewCount" class="storefront-buy-box__rating" />
            @if ($reviewCount)
                <span class="storefront-buy-box__reviews">{{ trans_choice('storefront::storefront.reviews_count', (int) $reviewCount, ['count' => $reviewCount]) }}</span>
            @endif
        @endif

        @if ($soldCount)
            <span class="storefront-buy-box__sold">{{ __('storefront::storefront.sold_count', ['count' => number_format((int) $soldCount)]) }}</span>
        @endif
    </div>

    @if ($variant)
        <div class="storefront-buy-box__price-panel" data-buy-price-panel>
            <div class="storefront-buy-box__price" data-buy-price>
                <span class="storefront-buy-box__amount" data-buy-amount>
                    @if ($showRange)
                        {{ $formatAmount($summaryMin) }} - {{ $formatAmount($summaryMax) }}
                    @else
                        {{ $formatAmount((float) ($variant->price ?? $summaryMin)) }}
                    @endif
                </span>

                @if ($compareMin !== null && $compareMin > $summaryMin)
                    <span class="storefront-buy-box__compare" data-buy-compare>
                        @if ($compareMin !== $compareMax && $compareMax !== null)
                            {{ $formatAmount($compareMin) }} - {{ $formatAmount($compareMax) }}
                        @else
                            {{ $formatAmount($compareMin) }}
                        @endif
                    </span>
                @elseif ($compareAt && (float) $compareAt > (float) $displayPrice)
                    <span class="storefront-buy-box__compare" data-buy-compare>{{ StorefrontMoney::formatMajor((float) $compareAt, (string) $displayCurrency, 0) }}</span>
                @endif

                @if ($discountPercent)
                    <span class="storefront-buy-box__discount" data-buy-discount>-{{ $discountPercent }}%</span>
                @endif
            </div>
        </div>

        <x-storefront.delivery-info :summary="$deliverySummary" class="storefront-buy-box__delivery" />

        <x-storefront.forms.variant-axis-selector
            :axes="$variantOptionAxes"
            :variants="$variants"
            :selected-uuid="$variant->uuid"
            class="storefront-buy-box__variants"
        />

        @if ($inStock)
            <form method="POST" action="{{ route('storefront.cart.items.store') }}" class="storefront-buy-box__form" data-buy-form>
                @csrf
                <input type="hidden" name="purchasable_uuid" value="{{ $variant->uuid }}" data-buy-variant-input>

                <div class="storefront-buy-box__quantity-row">
                    <span class="storefront-buy-box__quantity-label">{{ __('storefront::storefront.quantity') }}</span>
                    <div class="storefront-qty-stepper" data-qty-stepper>
                        <button type="button" class="storefront-qty-stepper__btn" data-qty-decrease aria-label="{{ __('storefront::storefront.decrease_quantity') }}">−</button>
                        <input
                            type="number"
                            name="quantity"
                            value="1"
                            min="1"
                            max="{{ $available }}"
                            class="storefront-qty-stepper__input"
                            data-buy-quantity
                            aria-label="{{ __('storefront::storefront.quantity') }}"
                        >
                        <button type="button" class="storefront-qty-stepper__btn" data-qty-increase aria-label="{{ __('storefront::storefront.increase_quantity') }}">+</button>
                    </div>
                    <span
                        class="storefront-buy-box__stock-note"
                        data-buy-stock-note
                        data-in-stock-label="{{ __('storefront::storefront.in_stock') }}"
                        data-out-of-stock-label="{{ __('storefront::storefront.out_of_stock') }}"
                    >{{ __('storefront::storefront.in_stock') }}</span>
                </div>

                <div class="storefront-buy-box__actions-row">
                    <button type="submit" class="storefront-buy-box__cta storefront-buy-box__cta--cart">
                        {{ __('storefront::storefront.add_to_cart') }}
                    </button>
                    <button type="submit" class="storefront-buy-box__cta storefront-buy-box__cta--buy" name="redirect_to" value="checkout">
                        {{ __('storefront::storefront.buy_now') }}
                    </button>
                </div>
            </form>
        @else
            <p class="storefront-buy-box__unavailable">{{ __('storefront::storefront.out_of_stock') }}</p>
        @endif
    @else
        <p class="storefront-buy-box__unavailable">{{ __('storefront::storefront.unavailable') }}</p>
    @endif
</aside>
