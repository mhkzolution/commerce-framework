@props([
    'product' => null,
    'brands' => collect(),
    'categories' => collect(),
    'collections' => collect(),
    'statuses' => [],
    'visibilities' => [],
])

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
