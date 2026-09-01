@props(['folders', 'currentFolderKey' => 'all', 'depth' => 0])

<ul class="{{ $depth === 0 ? 'cf-media-folder-list' : 'cf-media-folder-list cf-media-folder-list--nested' }}">
    @foreach ($folders as $folder)
        <li>
            <a
                href="{{ route('admin.media.index', ['folder' => $folder->uuid]) }}"
                class="cf-folder-link {{ ($currentFolderKey ?? null) === $folder->uuid ? 'is-active' : '' }}"
                data-folder-link
                data-folder="{{ $folder->uuid }}"
            >
                <span class="cf-media-folder-name">{{ $folder->name }}</span>
                <span class="cf-media-folder-count">{{ $folder->media_count }}</span>
            </a>
            @if ($folder->children->isNotEmpty())
                @include('media::admin.partials.folder-tree', [
                    'folders' => $folder->children,
                    'currentFolderKey' => $currentFolderKey,
                    'depth' => $depth + 1,
                ])
            @endif
        </li>
    @endforeach
</ul>
