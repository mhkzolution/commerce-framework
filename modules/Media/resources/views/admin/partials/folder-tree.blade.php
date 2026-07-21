@props(['folders', 'currentFolder' => null, 'depth' => 0])

<ul class="{{ $depth === 0 ? 'space-y-1' : 'mt-1 space-y-1 border-l border-border pl-3' }}">
    @foreach ($folders as $folder)
        <li>
            <div class="flex items-center justify-between gap-2">
                <a
                    href="{{ route('admin.media.index', ['folder' => $folder->uuid]) }}"
                    @class([
                        'cf-folder-link',
                        'is-active' => ($currentFolder?->uuid ?? null) === $folder->uuid,
                    ])
                >
                    {{ $folder->name }}
                    <span class="text-xs opacity-70">({{ $folder->media_count }})</span>
                </a>
            </div>
            @if ($folder->children->isNotEmpty())
                @include('media::admin.partials.folder-tree', [
                    'folders' => $folder->children,
                    'currentFolder' => $currentFolder,
                    'depth' => $depth + 1,
                ])
            @endif
        </li>
    @endforeach
</ul>
