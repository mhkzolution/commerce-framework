@props([
    'ownerName' => '',
    'sku' => '',
    'orientation' => 'vertical',
])

@php
    $orientationClass = $orientation === 'vertical' ? 'bc-label--vertical' : 'bc-label--horizontal';
@endphp

<div class="bc-label {{ $orientationClass }}" data-bc-label>
    <p class="bc-label__owner">{{ $ownerName }}</p>
    <div class="bc-label__barcode" aria-hidden="true">
        <svg data-bc-barcode-svg data-sku="{{ $sku }}"></svg>
    </div>
    <p class="bc-label__sku">{{ $sku }}</p>
</div>
