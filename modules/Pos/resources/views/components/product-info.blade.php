@props([
    'name' => null,
    'description' => null,
])

<div class="pos-product-detail" id="pos-product-info" data-pos-product-info>
    @if ($name)
        <p class="text-sm font-semibold text-text">{{ $name }}</p>
        @if ($description)
            <p class="mt-1 text-xs text-muted">{{ $description }}</p>
        @endif
        <x-pos::quick-actions />
    @endif
</div>
