@php
    $selectedCollectionIds = old('collection_ids', $product?->collections->pluck('id')->all() ?? []);
    $showLabel = $showLabel ?? true;
@endphp

@if ($showLabel)
    <label class="block text-sm font-medium text-text">Collections</label>
@endif

<div @class(['space-y-2', 'mt-2' => $showLabel])>
    @forelse ($collections as $collection)
        <label class="flex items-center gap-2 text-sm text-text">
            <input
                type="checkbox"
                name="collection_ids[]"
                value="{{ $collection->id }}"
                @checked(in_array($collection->id, $selectedCollectionIds, true))
                class="rounded border-border"
            >
            <span>{{ $collection->name }}</span>
        </label>
    @empty
        <p class="text-sm text-muted">No collections yet. <a href="{{ route('admin.catalog.collections.index') }}" class="text-accent hover:underline">Create one</a>.</p>
    @endforelse
</div>
