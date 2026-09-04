@props(['categories', 'depth' => 0, 'imageUrls' => []])

<ul class="{{ $depth === 0 ? 'divide-y divide-border' : 'mt-2 space-y-2 border-l border-border pl-4' }}">
    @foreach ($categories as $category)
        <li class="{{ $depth === 0 ? 'py-3' : 'py-1' }}">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0 flex items-center gap-3">
                    @if (! empty($imageUrls[$category->uuid] ?? null))
                        <img src="{{ $imageUrls[$category->uuid] }}" alt="" class="h-10 w-10 shrink-0 rounded object-cover">
                    @endif
                    <div>
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-text">{{ $category->name }}</span>
                        <span class="text-xs text-muted">/{{ $category->slug }}</span>
                        <x-admin.badge :variant="$category->is_active ? 'published' : 'archived'">
                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                        </x-admin.badge>
                    </div>
                    @if ($category->description)
                        <p class="mt-1 truncate text-sm text-muted">{{ $category->description }}</p>
                    @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 text-sm">
                    <form method="POST" action="{{ route('admin.catalog.categories.reorder', $category) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <label class="text-muted">Pos</label>
                        <input type="number" name="position" value="{{ $category->position }}" min="0" class="cf-input w-16 py-1">
                        <x-admin.button variant="ghost" type="submit">Save</x-admin.button>
                    </form>
                    <x-admin.button variant="link" :href="route('admin.catalog.categories.edit', $category)">Edit</x-admin.button>
                    <form method="POST" action="{{ route('admin.catalog.categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-danger hover:underline">Delete</button>
                    </form>
                </div>
            </div>

            @if ($category->children->isNotEmpty())
                @include('catalog::admin.categories._tree', [
                    'categories' => $category->children,
                    'depth' => $depth + 1,
                    'imageUrls' => $imageUrls,
                ])
            @endif
        </li>
    @endforeach
</ul>
