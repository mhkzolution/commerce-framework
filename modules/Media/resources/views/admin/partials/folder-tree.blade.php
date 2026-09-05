@props(['folders', 'currentFolderKey' => 'all', 'depth' => 0, 'canFolderUpdate' => false, 'canFolderDelete' => false, 'parentUuid' => null])

<ul class="{{ $depth === 0 ? 'cf-media-folder-list' : 'cf-media-folder-list cf-media-folder-list--nested' }}">
    @foreach ($folders as $folder)
        <li class="cf-media-folder-item">
            <div class="cf-folder-row">
                <a
                    href="{{ route('admin.media.index', ['folder' => $folder->uuid]) }}"
                    class="cf-folder-link {{ ($currentFolderKey ?? null) === $folder->uuid ? 'is-active' : '' }}"
                    data-folder-link
                    data-folder="{{ $folder->uuid }}"
                >
                    <span class="cf-media-folder-name">{{ $folder->name }}</span>
                    <span class="cf-media-folder-count">{{ $folder->media_count }}</span>
                </a>
                @if ($canFolderUpdate || $canFolderDelete)
                    <div class="cf-folder-row__actions">
                        @if ($canFolderUpdate)
                            <button
                                type="button"
                                class="cf-folder-action"
                                data-rename-folder
                                data-folder="{{ $folder->uuid }}"
                                data-folder-name="{{ $folder->name }}"
                                data-parent-uuid="{{ $parentUuid }}"
                                aria-label="{{ __('media::admin.rename_folder') }}"
                            >{{ __('media::admin.rename_folder') }}</button>
                        @endif
                        @if ($canFolderDelete)
                            <button
                                type="button"
                                class="cf-folder-action"
                                data-delete-folder
                                data-folder="{{ $folder->uuid }}"
                                aria-label="{{ __('media::admin.delete_folder') }}"
                            >{{ __('media::admin.delete') }}</button>
                        @endif
                    </div>
                @endif
            </div>
            @if ($folder->children->isNotEmpty())
                @include('media::admin.partials.folder-tree', [
                    'folders' => $folder->children,
                    'currentFolderKey' => $currentFolderKey,
                    'depth' => $depth + 1,
                    'canFolderUpdate' => $canFolderUpdate,
                    'canFolderDelete' => $canFolderDelete,
                    'parentUuid' => $folder->uuid,
                ])
            @endif
        </li>
    @endforeach
</ul>
