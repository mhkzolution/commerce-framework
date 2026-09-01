@props([
    'variant' => null,
    'inventoryUrl' => null,
])

@php
    $variant = is_array($variant) ? $variant : [];
    $stock = is_array($variant['stock'] ?? null) ? $variant['stock'] : [];
    $status = $variant['status'] ?? 'active';
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
        <button
            type="button"
            class="cf-variant-stock-summary"
            data-variant-stock-link
            title="Manage stock in Inventory"
            @if (! empty($variant['uuid']) && $inventoryUrl)
                onclick="window.location.href='{{ rtrim($inventoryUrl, '/') }}/{{ $variant['uuid'] }}'"
            @endif
        >
            <span class="cf-variant-stock-summary__available" data-variant-stock-available>{{ $stock['available'] ?? 0 }}</span>
            <span class="cf-variant-stock-summary__meta">
                <span data-variant-stock-on-hand>{{ $stock['onHand'] ?? 0 }} on hand</span>
                ·
                <span data-variant-stock-reserved>{{ $stock['reserved'] ?? 0 }} reserved</span>
                ·
                <span data-variant-stock-incoming>{{ $stock['incoming'] ?? 0 }} incoming</span>
            </span>
        </button>
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
