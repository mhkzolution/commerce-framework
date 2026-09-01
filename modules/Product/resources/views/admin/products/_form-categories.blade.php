@php
    $selectedCategoryIds = old('category_ids', $product?->categories->pluck('id')->all() ?? []);
    $showLabel = $showLabel ?? true;
@endphp

@if ($showLabel)
    <label class="block text-sm font-medium text-text">Categories</label>
@endif

<div @class(['space-y-2', 'mt-2' => $showLabel])>
    @forelse ($categories as $category)
        <label class="flex items-center gap-2 text-sm text-text">
            <input
                type="checkbox"
                name="category_ids[]"
                value="{{ $category->id }}"
                @checked(in_array($category->id, $selectedCategoryIds, true))
                class="rounded border-border"
            >
            <span>{{ $category->name }}</span>
        </label>
    @empty
        <p class="text-sm text-muted">No categories yet.</p>
    @endforelse
</div>
