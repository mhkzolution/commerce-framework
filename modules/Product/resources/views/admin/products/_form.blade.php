@php
    $defaultVariant = $product?->defaultVariant();
    $price = old('price', $defaultVariant ? number_format($defaultVariant->price / 100, 2, '.', '') : '0.00');
    $compareAt = old('compare_at_price', $defaultVariant?->compare_at_price ? number_format($defaultVariant->compare_at_price / 100, 2, '.', '') : '');
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $product?->name) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="slug">Slug</label>
        <input id="slug" name="slug" value="{{ old('slug', $product?->slug) }}" class="cf-input mt-1">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-text" for="description">Description</label>
    <textarea id="description" name="description" rows="4" class="cf-input mt-1">{{ old('description', $product?->description) }}</textarea>
</div>

<div class="grid gap-4 md:grid-cols-3">
    <div>
        <label class="block text-sm font-medium text-text" for="type">Type</label>
        <select id="type" name="type" class="cf-input mt-1">
            @foreach ($types as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $product?->type ?? 'simple') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="status">Status</label>
        <select id="status" name="status" class="cf-input mt-1">
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $product?->status ?? 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="publish_at">Publish at (for scheduled)</label>
        <input id="publish_at" type="datetime-local" name="publish_at" value="{{ old('publish_at', $product?->publish_at?->format('Y-m-d\TH:i')) }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="visibility">Visibility</label>
        <select id="visibility" name="visibility" class="cf-input mt-1">
            @foreach ($visibilities as $value => $label)
                <option value="{{ $value }}" @selected(old('visibility', $product?->visibility ?? 'public') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="brand_uuid">Brand</label>
        <select id="brand_uuid" name="brand_uuid" class="cf-input mt-1">
            <option value="">— None —</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->uuid }}" @selected(old('brand_uuid', $product?->brand_uuid) === $brand->uuid)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </div>
    @if (($sellers ?? collect())->isNotEmpty())
        <div>
            <label class="block text-sm font-medium text-text" for="seller_uuid">Seller</label>
            <select id="seller_uuid" name="seller_uuid" class="cf-input mt-1">
                <option value="">— None —</option>
                @foreach ($sellers as $seller)
                    <option value="{{ $seller->uuid }}" @selected(old('seller_uuid', $product?->seller_uuid) === $seller->uuid)>{{ $seller->name }}</option>
                @endforeach
            </select>
        </div>
    @endif
    <div>
        <label class="block text-sm font-medium text-text" for="attribute_set_id">Attribute set</label>
        <select id="attribute_set_id" name="attribute_set_id" class="cf-input mt-1">
            <option value="">— None —</option>
            @foreach ($attributeSets as $set)
                <option value="{{ $set->id }}" @selected((string) old('attribute_set_id', $product?->attribute_set_id) === (string) $set->id)>{{ $set->name }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid gap-4 md:grid-cols-3">
    <div>
        <label class="block text-sm font-medium text-text" for="sku">SKU (default variant)</label>
        <input id="sku" name="sku" value="{{ old('sku', $defaultVariant?->sku) }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="price">Price</label>
        <input id="price" type="number" step="0.01" min="0" name="price" value="{{ $price }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="compare_at_price">Compare at price</label>
        <input id="compare_at_price" type="number" step="0.01" min="0" name="compare_at_price" value="{{ $compareAt }}" class="cf-input mt-1">
    </div>
</div>

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text">Categories</label>
        <select name="category_ids[]" multiple class="cf-input mt-1 h-32">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_ids', $product?->categories->pluck('id')->all() ?? []), true))>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-text">Tags</label>
        <select name="tag_ids[]" multiple class="cf-input mt-1 h-32">
            @foreach ($tags as $tag)
                <option value="{{ $tag->id }}" @selected(in_array($tag->id, old('tag_ids', $product?->tags->pluck('id')->all() ?? []), true))>{{ $tag->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@include('product::components.media-gallery', [
    'mediaUuids' => old('media_uuids', $product?->media->pluck('media_uuid')->all() ?? []),
    'mediaPreviews' => $mediaPreviews ?? [],
])

@include('product::admin.products._attributes')

@include('product::admin.products._seo', ['seo' => $seo ?? null])
