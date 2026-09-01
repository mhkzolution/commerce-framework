@php
    $selectedBrandUuid = old('brand_uuid', $product?->brand_uuid);
    $showLabel = $showLabel ?? true;
@endphp

<div data-searchable-list>
    @if ($showLabel)
        <label class="block text-sm font-medium text-text">Brand</label>
    @endif

    <input
        type="search"
        class="cf-input mt-2"
        placeholder="ค้นหาแบรนด์..."
        data-searchable-filter
        autocomplete="off"
    >

    <div @class(['mt-2 space-y-2', 'mt-2' => ! $showLabel])>
        <label class="flex items-center gap-2 text-sm text-text" data-searchable-item data-searchable-text="none">
            <input
                type="radio"
                name="brand_uuid"
                value=""
                @checked($selectedBrandUuid === null || $selectedBrandUuid === '')
                class="border-border"
            >
            <span>— None —</span>
        </label>

        @forelse ($brands as $brand)
            <label
                class="flex items-center gap-2 text-sm text-text"
                data-searchable-item
                data-searchable-text="{{ Str::lower($brand->name) }}"
            >
                <input
                    type="radio"
                    name="brand_uuid"
                    value="{{ $brand->uuid }}"
                    @checked($selectedBrandUuid === $brand->uuid)
                    class="border-border"
                >
                <span>{{ $brand->name }}</span>
            </label>
        @empty
            <p class="text-sm text-muted">No brands yet.</p>
        @endforelse
    </div>
</div>

@include('product::components.searchable-filter')
