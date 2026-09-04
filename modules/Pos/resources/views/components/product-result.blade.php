@props([
    'product' => null,
    'imageUrl' => null,
    'name' => '',
    'sku' => '',
    'stock' => null,
    'price' => '',
    'attributes' => [],
    'stockWarning' => null,
])

@php
    $stockClass = match (true) {
        $stockWarning === 'out' => 'pos-product-result__stock--out',
        $stockWarning === 'low' => 'pos-product-result__stock--low',
        default => '',
    };
    $stockLabel = match (true) {
        $stockWarning === 'out' => 'หมด',
        $stockWarning === 'low' => "เหลือ {$stock}",
        default => $stock !== null ? "{$stock} ชิ้น" : null,
    };
@endphp

<div
    class="pos-product-result"
    data-pos-product-result
    data-product-id="{{ $product ?? '' }}"
    role="option"
    tabindex="-1"
>
    @if ($imageUrl)
        <img src="{{ $imageUrl }}" alt="" class="pos-product-result__image" loading="lazy">
    @else
        <div class="pos-product-result__image pos-product-result__image--placeholder" aria-hidden="true">📦</div>
    @endif

    <div class="pos-product-result__body">
        <p class="pos-product-result__name">{{ $name }}</p>
        @if ($sku)
            <p class="pos-product-result__meta">{{ $sku }}</p>
        @endif
        @if (count($attributes) > 0)
            <p class="pos-product-result__meta">{{ implode(' · ', $attributes) }}</p>
        @endif
    </div>

    <div class="pos-product-result__footer">
        <p class="pos-product-result__price">{{ $price }}</p>
        @if ($stockLabel !== null)
            <span class="pos-product-result__stock {{ $stockClass }}">{{ $stockLabel }}</span>
        @endif
    </div>
</div>
