@props([
    'imageUrl' => null,
    'name' => '',
    'variant' => '',
    'quantity' => 1,
    'unitPrice' => '',
    'discount' => '',
    'subtotal' => '',
    'stockWarning' => null,
    'lineId' => null,
])

<li
    class="pos-cart-line"
    data-pos-cart-line
    data-line-id="{{ $lineId }}"
>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="" class="pos-cart-line__image" loading="lazy">
    @else
        <div class="pos-cart-line__image" aria-hidden="true"></div>
    @endif

    <div class="pos-cart-line__body">
        <p class="pos-cart-line__name">{{ $name }}</p>
        @if ($variant)
            <p class="pos-cart-line__variant">{{ $variant }}</p>
        @endif

        <div class="pos-cart-line__controls">
            <button type="button" class="pos-cart-line__qty-btn" aria-label="Decrease quantity" data-pos-qty-decrease>−</button>
            <input
                type="number"
                class="pos-cart-line__qty-input"
                value="{{ $quantity }}"
                min="1"
                aria-label="Quantity"
                data-pos-qty-input
            >
            <button type="button" class="pos-cart-line__qty-btn" aria-label="Increase quantity" data-pos-qty-increase>+</button>
            <button type="button" class="pos-btn pos-btn--secondary pos-btn--icon" aria-label="Duplicate line" title="Duplicate">⧉</button>
            <button type="button" class="pos-btn pos-btn--secondary pos-btn--icon" aria-label="Line discount" title="Discount">%</button>
            <button type="button" class="pos-btn pos-btn--danger pos-btn--icon" aria-label="Remove line" title="Remove">×</button>
        </div>

        @if ($stockWarning)
            <p class="pos-cart-line__warning">{{ $stockWarning }}</p>
        @endif

        @if ($discount)
            <p class="text-xs text-muted">Discount: {{ $discount }}</p>
        @endif
    </div>

    <div class="pos-cart-line__pricing">
        <p class="text-xs text-muted">{{ $unitPrice }}</p>
        <p class="pos-cart-line__subtotal">{{ $subtotal }}</p>
    </div>
</li>
