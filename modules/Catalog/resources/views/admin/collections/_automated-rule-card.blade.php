@php
    $namePrefix = $namePrefix ?? 'rules';
    $ruleValues = $ruleValues ?? [];
    $type = $type ?? 'on_sale';
    $labels = [
        'on_sale' => 'On sale',
        'categories' => 'Category',
        'brands' => 'Brand',
        'tags' => 'Tag',
        'price' => 'Price',
    ];
@endphp

<li class="rule-builder__card" data-rule-card data-rule-type="{{ $type }}" draggable="true">
    <div class="rule-card">
        <button type="button" class="rule-card__handle" data-rule-drag-handle aria-label="Drag to reorder">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <div class="rule-card__body">
            <p class="rule-card__title">{{ $labels[$type] ?? ucfirst($type) }}</p>

            @if ($type === 'on_sale')
                <label class="inline-flex items-center gap-2 text-sm text-text-secondary">
                    <input type="hidden" name="{{ $namePrefix }}[on_sale]" value="0">
                    <input type="checkbox" name="{{ $namePrefix }}[on_sale]" value="1" @checked(old($namePrefix . '.on_sale', $ruleValues['on_sale'] ?? false)) class="rounded border-border">
                    Product is on sale
                </label>
            @elseif ($type === 'categories')
                @php
                    $selectedCategoryIds = old($namePrefix . '.category_ids', $ruleValues['category_ids'] ?? array_filter([$ruleValues['category_id'] ?? null]));
                @endphp
                <select name="{{ $namePrefix }}[category_ids][]" class="cf-input" multiple size="4">
                    @foreach ($categories ?? [] as $category)
                        <option value="{{ $category->id }}" @selected(in_array((string) $category->id, array_map('strval', (array) $selectedCategoryIds), true))>{{ $category->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-muted">Hold Cmd/Ctrl to select multiple categories.</p>
                <div class="mt-2">
                    <label class="block text-sm font-medium text-text">Category match</label>
                    <select name="{{ $namePrefix }}[category_match]" class="cf-input mt-1">
                        <option value="any" @selected(old($namePrefix . '.category_match', $ruleValues['category_match'] ?? 'any') === 'any')>In any selected category</option>
                        <option value="all" @selected(old($namePrefix . '.category_match', $ruleValues['category_match'] ?? 'any') === 'all')>In all selected categories</option>
                    </select>
                </div>
            @elseif ($type === 'brands')
                @php
                    $selectedBrandUuids = old($namePrefix . '.brand_uuids', $ruleValues['brand_uuids'] ?? array_filter([$ruleValues['brand_uuid'] ?? null]));
                @endphp
                <select name="{{ $namePrefix }}[brand_uuids][]" class="cf-input" multiple size="4">
                    @foreach ($brands ?? [] as $brand)
                        <option value="{{ $brand->uuid }}" @selected(in_array((string) $brand->uuid, array_map('strval', (array) $selectedBrandUuids), true))>{{ $brand->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-muted">Hold Cmd/Ctrl to select multiple brands.</p>
                <div class="mt-2">
                    <label class="block text-sm font-medium text-text">Brand match</label>
                    <select name="{{ $namePrefix }}[brand_match]" class="cf-input mt-1">
                        <option value="any" @selected(old($namePrefix . '.brand_match', $ruleValues['brand_match'] ?? 'any') === 'any')>Any selected brand</option>
                        <option value="all" @selected(old($namePrefix . '.brand_match', $ruleValues['brand_match'] ?? 'any') === 'all')>All selected brands</option>
                    </select>
                </div>
            @elseif ($type === 'tags')
                @php
                    $selectedTagIds = old($namePrefix . '.tag_ids', $ruleValues['tag_ids'] ?? array_filter([$ruleValues['tag_id'] ?? null]));
                @endphp
                <select name="{{ $namePrefix }}[tag_ids][]" class="cf-input" multiple size="4">
                    @foreach ($tags ?? [] as $tag)
                        <option value="{{ $tag->id }}" @selected(in_array((string) $tag->id, array_map('strval', (array) $selectedTagIds), true))>{{ $tag->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-muted">Hold Cmd/Ctrl to select multiple tags.</p>
                <div class="mt-2">
                    <label class="block text-sm font-medium text-text">Tag match</label>
                    <select name="{{ $namePrefix }}[tag_match]" class="cf-input mt-1">
                        <option value="any" @selected(old($namePrefix . '.tag_match', $ruleValues['tag_match'] ?? 'any') === 'any')>Has any selected tag</option>
                        <option value="all" @selected(old($namePrefix . '.tag_match', $ruleValues['tag_match'] ?? 'any') === 'all')>Has all selected tags</option>
                    </select>
                </div>
            @elseif ($type === 'price')
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-text">Min price</label>
                        <input type="number" name="{{ $namePrefix }}[price_min]" value="{{ old($namePrefix . '.price_min', $ruleValues['price_min'] ?? '') }}" min="0" step="0.01" class="cf-input mt-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-text">Max price</label>
                        <input type="number" name="{{ $namePrefix }}[price_max]" value="{{ old($namePrefix . '.price_max', $ruleValues['price_max'] ?? '') }}" min="0" step="0.01" class="cf-input mt-1">
                    </div>
                </div>
            @endif
        </div>

        <button type="button" class="rule-card__remove" data-rule-remove aria-label="Remove condition">Remove</button>
    </div>
</li>
