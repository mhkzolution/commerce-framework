@php
    $method = $method ?? null;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="block text-sm font-medium text-text" for="code">Code</label>
        <input id="code" name="code" value="{{ old('code', $method?->code) }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $method?->name) }}" required class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="description">Description</label>
        <textarea id="description" name="description" rows="2" class="cf-input mt-1">{{ old('description', $method?->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="price">Price</label>
        <input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $method ? number_format($method->price / 100, 2, '.', '') : '0.00') }}" required class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="free_above">Free above (subtotal)</label>
        <input id="free_above" type="number" step="0.01" min="0" name="free_above" value="{{ old('free_above', $method?->free_above ? number_format($method->free_above / 100, 2, '.', '') : '') }}" class="cf-input mt-1">
        <p class="mt-1 text-xs text-muted">Optional. Method becomes free when cart subtotal reaches this amount.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="min_subtotal">Min subtotal</label>
        <input id="min_subtotal" type="number" step="0.01" min="0" name="min_subtotal" value="{{ old('min_subtotal', $method?->min_subtotal ? number_format($method->min_subtotal / 100, 2, '.', '') : '') }}" class="cf-input mt-1">
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="max_subtotal">Max subtotal</label>
        <input id="max_subtotal" type="number" step="0.01" min="0" name="max_subtotal" value="{{ old('max_subtotal', $method?->max_subtotal ? number_format($method->max_subtotal / 100, 2, '.', '') : '') }}" class="cf-input mt-1">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-text" for="countries">Countries</label>
        <input id="countries" name="countries" value="{{ old('countries', $method?->countries ? implode(', ', $method->countries) : '') }}" placeholder="US, CA, TH" class="cf-input mt-1">
        <p class="mt-1 text-xs text-muted">Comma-separated ISO codes. Leave empty for all countries.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-text" for="sort_order">Sort order</label>
        <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $method?->sort_order ?? 0) }}" class="cf-input mt-1">
    </div>
    <div class="flex items-end">
        <label class="flex items-center gap-2 text-sm text-text-secondary">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $method?->is_active ?? true)) class="rounded border-border">
            Active
        </label>
    </div>
</div>
