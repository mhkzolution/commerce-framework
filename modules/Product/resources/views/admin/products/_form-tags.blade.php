@php
    $selectedTagIds = old('tag_ids', $product?->tags->pluck('id')->all() ?? []);
    $showLabel = $showLabel ?? true;
@endphp

@if ($showLabel)
    <label class="block text-sm font-medium text-text">Tags</label>
@endif

<div @class(['space-y-2', 'mt-2' => $showLabel])>
    @forelse ($tags as $tag)
        <label class="flex items-center gap-2 text-sm text-text">
            <input
                type="checkbox"
                name="tag_ids[]"
                value="{{ $tag->id }}"
                @checked(in_array($tag->id, $selectedTagIds, true))
                class="rounded border-border"
            >
            <span>{{ $tag->name }}</span>
        </label>
    @empty
        <p class="text-sm text-muted">No tags yet.</p>
    @endforelse
</div>
