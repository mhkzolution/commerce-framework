@props([
    'product' => null,
    'brands' => collect(),
    'categories' => collect(),
    'collections' => collect(),
    'statuses' => [],
    'visibilities' => [],
    'workspaceProduct' => [],
])

@php
    $workspaceType = $workspaceProduct['type'] ?? $product?->type ?? 'simple';
    $trackInventory = (bool) ($workspaceProduct['trackInventory'] ?? true);
    $backorderPolicy = $workspaceProduct['backorderPolicy'] ?? $product?->backorder_policy ?? 'deny';
@endphp

<section class="cf-product-workspace__section">
    <header class="cf-product-workspace__section-header">
        <h2 class="cf-product-workspace__section-title">{{ __('product::workspace.general_title') }}</h2>
        <p class="cf-product-workspace__section-desc">{{ __('product::workspace.general_desc') }}</p>
    </header>

    <div class="cf-product-workspace__field-grid cf-product-workspace__field-grid--2">
        <div class="cf-product-workspace__field cf-product-workspace__field--full">
            <label class="cf-product-workspace__label" for="slug">{{ __('product::workspace.slug') }}</label>
            <input
                id="slug"
                name="slug"
                type="text"
                value="{{ old('slug', $product?->slug ?? '') }}"
                class="cf-input"
                data-workspace-slug-input
                placeholder="auto-generated-from-name"
            >
        </div>

        <div class="cf-product-workspace__field">
            <label class="cf-product-workspace__label" for="brand_uuid">Brand</label>
            <select id="brand_uuid" name="brand_uuid" class="cf-input">
                <option value="">— None —</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->uuid }}" @selected(old('brand_uuid', $product?->brand_uuid) === $brand->uuid)>
                        {{ $brand->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="cf-product-workspace__field">
            <label class="cf-product-workspace__label" for="status">Status</label>
            <select id="status" name="status" class="cf-input">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $product?->status ?? 'draft') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="cf-product-workspace__field">
            <label class="cf-product-workspace__label" for="visibility">Visibility</label>
            <select id="visibility" name="visibility" class="cf-input">
                @foreach ($visibilities as $value => $label)
                    <option value="{{ $value }}" @selected(old('visibility', $product?->visibility ?? 'public') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="cf-product-workspace__field">
            <label class="cf-product-workspace__label" for="publish_at">Publish date</label>
            <input
                id="publish_at"
                type="datetime-local"
                name="publish_at"
                value="{{ old('publish_at', $product?->publish_at?->format('Y-m-d\TH:i')) }}"
                class="cf-input"
            >
        </div>
    </div>

    <div class="cf-product-workspace__field-grid cf-product-workspace__field-grid--2">
        <div class="cf-product-workspace__field">
            <label class="cf-product-workspace__label" for="workspace_sku">
                <span data-workspace-sku-label-simple @if ($workspaceType !== 'simple') hidden @endif>{{ __('product::workspace.sku') }}</span>
                <span data-workspace-sku-label-prefix @if ($workspaceType === 'simple') hidden @endif>{{ __('product::workspace.sku_prefix') }}</span>
            </label>
            <input
                id="workspace_sku"
                type="text"
                class="cf-input"
                value="{{ $workspaceProduct['sku'] ?? '' }}"
                placeholder="{{ $workspaceType === 'simple' ? 'Auto' : 'TSHIRT' }}"
                data-workspace-sku
                data-workspace-simple-sku
            >
            <p class="cf-product-workspace__hint" data-workspace-sku-prefix-hint @if ($workspaceType === 'simple') hidden @endif>
                {{ __('product::workspace.sku_prefix_hint') }}
            </p>
        </div>
        <div
            class="cf-product-workspace__field"
            data-simple-product-fields
            @if ($workspaceType !== 'simple') hidden @endif
        >
            <label class="cf-product-workspace__label" for="workspace_price">{{ __('product::workspace.price') }}</label>
            <input id="workspace_price" type="number" class="cf-input" min="0" step="0.01" value="{{ $workspaceProduct['price'] ?? '' }}" data-workspace-simple-price>
        </div>
    </div>

    <section class="cf-product-workspace__section" data-product-stock-policy>
        <header class="cf-product-workspace__section-header">
            <h3 class="cf-product-workspace__section-title">{{ __('product::workspace.stock') }}</h3>
        </header>

        <label class="cf-product-workspace__label">
            <input type="checkbox" data-workspace-track-inventory @checked($trackInventory)>
            {{ __('product::workspace.track_inventory') }}
        </label>

        <div
            class="cf-product-workspace__field-grid cf-product-workspace__field-grid--2"
            data-simple-stock
            @if ($workspaceType !== 'simple') hidden @endif
        >
            <div class="cf-product-workspace__field" data-simple-quantity @if (! $trackInventory) hidden @endif>
                <label class="cf-product-workspace__label" for="workspace_on_hand">{{ __('product::workspace.quantity_on_hand') }}</label>
                <input id="workspace_on_hand" type="number" class="cf-input" min="0" step="1" value="{{ $workspaceProduct['onHand'] ?? 0 }}" data-workspace-simple-on-hand>
            </div>
            @if ($product)
                <div class="cf-product-workspace__field-grid cf-product-workspace__field-grid--2">
                    <div class="cf-product-workspace__field">
                        <label class="cf-product-workspace__label">{{ __('product::workspace.reserved') }}</label>
                        <output data-workspace-simple-reserved>{{ $workspaceProduct['reserved'] ?? 0 }}</output>
                    </div>
                    <div class="cf-product-workspace__field">
                        <label class="cf-product-workspace__label">{{ __('product::workspace.available') }}</label>
                        <output data-workspace-simple-available>{{ $workspaceProduct['available'] ?? 0 }}</output>
                    </div>
                </div>
            @endif
        </div>

        <fieldset class="cf-product-workspace__field">
            <legend class="cf-product-workspace__label">{{ __('product::workspace.backorder_policy') }}</legend>
            @foreach (['deny', 'notify', 'allow'] as $policy)
                <label>
                    <input type="radio" name="workspace_backorder_policy" value="{{ $policy }}" data-workspace-backorder @checked($backorderPolicy === $policy)>
                    {{ __('product::workspace.backorder_'.$policy) }}
                </label>
            @endforeach
        </fieldset>
    </section>

    <div class="cf-product-workspace__field cf-product-workspace__field--full">
        <label class="cf-product-workspace__label" for="description">Description</label>
        <textarea
            id="description"
            name="description"
            rows="6"
            class="cf-input"
            placeholder="Tell customers about this product…"
        >{{ old('description', $product?->description) }}</textarea>
    </div>

    <div class="cf-product-workspace__field cf-product-workspace__field--full">
        <label class="cf-product-workspace__label">Categories</label>
        @include('product::admin.products._form-categories', ['product' => $product, 'showLabel' => false])
    </div>

    <div class="cf-product-workspace__field cf-product-workspace__field--full">
        <label class="cf-product-workspace__label">Collections</label>
        @include('product::admin.products._form-collections', [
            'product' => $product,
            'collections' => $collections,
            'showLabel' => false,
        ])
        <p class="cf-product-workspace__hint">Curated product groups for campaigns and storefront sections.</p>
    </div>
</section>
