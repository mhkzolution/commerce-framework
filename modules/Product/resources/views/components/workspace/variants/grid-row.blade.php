@props([
    'variant' => null,
    'inventoryUrl' => null,
])

@php
    $variant = is_array($variant) ? $variant : [];
    $stock = is_array($variant['stock'] ?? null) ? $variant['stock'] : [];
    $status = $variant['status'] ?? 'active';
    $trackInventory = (bool) ($variant['trackInventory'] ?? true);
@endphp

<tr
    class="cf-variant-grid__row"
    data-variant-row
    @if ($variant !== []) data-variant-id="{{ $variant['id'] ?? '' }}" @endif
>
    <td class="cf-variant-grid__td">
        <input type="checkbox" data-variant-select aria-label="Select variant">
    </td>
    <td class="cf-variant-grid__td cf-variant-grid__td--image">
        <button type="button" class="cf-variant-grid__image-btn" data-variant-image aria-label="Assign image">
            <span class="cf-variant-grid__image-placeholder" data-variant-image-preview>
                @if (! empty($variant['imagePreviewUrl']))
                    <img src="{{ $variant['imagePreviewUrl'] }}" alt="" class="cf-variant-grid__image-thumb">
                @else
                    +
                @endif
            </span>
        </button>
    </td>
    <td class="cf-variant-grid__td">
        <x-product::workspace.variants.inline-cell type="text" name="name" placeholder="Variant name" :value="$variant['name'] ?? ''" />
    </td>
    <td class="cf-variant-grid__td">
        <x-product::workspace.variants.inline-cell type="text" name="sku" placeholder="Auto" :value="$variant['sku'] ?? ''" />
    </td>
    <td class="cf-variant-grid__td">
        <x-product::workspace.variants.inline-cell type="number" name="price" placeholder="0.00" step="0.01" min="0" :value="$variant['price'] ?? ''" />
    </td>
    <td class="cf-variant-grid__td">
        <x-product::workspace.variants.inline-cell type="number" name="cost" placeholder="0.00" step="0.01" min="0" :value="$variant['cost'] ?? ''" />
    </td>
    <td class="cf-variant-grid__td">
        <x-product::workspace.variants.inline-cell type="number" name="comparePrice" placeholder="0.00" step="0.01" min="0" :value="$variant['comparePrice'] ?? ''" />
    </td>
    <td class="cf-variant-grid__td">
        <x-product::workspace.variants.inline-cell type="number" name="weight" placeholder="0" step="0.01" min="0" :value="$variant['weight'] ?? ''" />
    </td>
    <td class="cf-variant-grid__td">
        <div class="cf-variant-stock-summary">
            <label data-variant-on-hand-wrap @if (! $trackInventory) hidden @endif>
                <span class="sr-only">{{ __('product::workspace.quantity_on_hand') }}</span>
                <input
                    type="number"
                    class="cf-input"
                    min="0"
                    step="1"
                    value="{{ $stock['onHand'] ?? 0 }}"
                    data-variant-stock-on-hand-input
                >
            </label>
            <span class="cf-variant-stock-summary__meta">
                <span><strong data-variant-stock-reserved>{{ $stock['reserved'] ?? 0 }}</strong> {{ __('product::workspace.reserved') }}</span>
                ·
                <span><strong data-variant-stock-available>{{ $stock['available'] ?? 0 }}</strong> {{ __('product::workspace.available') }}</span>
            </span>
        </div>
    </td>
    <td class="cf-variant-grid__td">
        <select class="cf-variant-grid__status-select" data-variant-field="status">
            <option value="active" @selected($status === 'active')>Active</option>
            <option value="draft" @selected($status === 'draft')>Draft</option>
            <option value="archived" @selected($status === 'archived')>Archived</option>
        </select>
    </td>
    <td class="cf-variant-grid__td cf-variant-grid__td--actions">
        <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-variant-delete aria-label="Delete variant">
            Delete
        </button>
    </td>
</tr>
