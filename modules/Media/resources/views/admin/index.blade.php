@extends('layouts.admin')

@section('title', __('media::admin.library'))

@php
    $folderKey = $currentFolderKey ?? 'all';
    $type = request('type');
    $period = request('period');
    $folderOptions = collect($folders)->map(fn ($folder) => [
        'uuid' => $folder->uuid,
        'name' => $folder->name,
    ])->values();
    $authz = app(\Commerce\Contracts\Authorization\AuthorizationServiceInterface::class);
    $actor = auth()->user();
    $canUpload = $authz->can($actor, 'media.media.upload');
    $canUpdate = $authz->can($actor, 'media.media.update');
    $canDelete = $authz->can($actor, 'media.media.delete');
    $canFolderCreate = $authz->can($actor, 'media.folder.create');
@endphp

@section('page')
    <x-admin.page :title="__('media::admin.library')" wide>
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => __('media::admin.library'), 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <div
            class="cf-media-library"
            data-media-library
            data-browse-url="{{ route('admin.media.index') }}"
            data-upload-url="{{ route('admin.media.store') }}"
            data-import-url="{{ route('admin.media.import') }}"
            data-folder-store-url="{{ route('admin.media.folders.store') }}"
            data-bulk-delete-url="{{ route('admin.media.bulk-delete') }}"
            data-bulk-move-url="{{ route('admin.media.bulk-move') }}"
            data-show-url="{{ url('/admin/media') }}"
            data-download-url="{{ url('/admin/media') }}"
            data-folder="{{ $folderKey }}"
            data-type="{{ $type }}"
            data-period="{{ $period }}"
            data-search="{{ request('search') }}"
            data-page="{{ $media->currentPage() }}"
            data-last-page="{{ $media->lastPage() }}"
            data-total="{{ $media->total() }}"
            data-can-upload="{{ $canUpload ? '1' : '0' }}"
            data-can-update="{{ $canUpdate ? '1' : '0' }}"
            data-can-delete="{{ $canDelete ? '1' : '0' }}"
            data-can-folder-create="{{ $canFolderCreate ? '1' : '0' }}"
            data-folders="{{ $folderOptions->toJson() }}"
        >
            <div class="cf-media-toolbar">
                <div class="cf-media-toolbar__actions">
                    @if ($canUpload)
                        <x-admin.button variant="primary" type="button" data-open-upload>
                            {{ __('media::admin.upload') }}
                        </x-admin.button>
                        <input type="file" hidden multiple data-upload-input accept="{{ implode(',', config('media.allowed_mimes', [])) }}">
                        <x-admin.button variant="secondary" type="button" data-open-import>
                            {{ __('media::admin.import_url') }}
                        </x-admin.button>
                    @endif
                    @if ($canFolderCreate)
                        <x-admin.button variant="secondary" type="button" data-open-folder>
                            {{ __('media::admin.new_folder') }}
                        </x-admin.button>
                    @endif
                </div>
                <div class="cf-media-toolbar__search">
                    <x-admin.search-input
                        id="media-library-search"
                        name="search"
                        :placeholder="__('media::admin.search_placeholder')"
                        :value="request('search')"
                        data-library-search
                    />
                </div>
            </div>

            <div class="cf-media-bulk" data-bulk-bar>
                <strong data-bulk-count>{{ __('media::admin.selected', ['count' => 0]) }}</strong>
                @if ($canUpdate)
                    <div class="cf-media-bulk__move">
                        <select class="cf-input" data-bulk-folder data-bulk-action disabled>
                            <option value="">{{ __('media::admin.unfiled') }}</option>
                            @foreach ($folders as $folderOption)
                                <option value="{{ $folderOption->uuid }}">{{ $folderOption->name }}</option>
                            @endforeach
                        </select>
                        <x-admin.button variant="secondary" type="button" data-bulk-move data-bulk-action disabled>{{ __('media::admin.move') }}</x-admin.button>
                    </div>
                @endif
                <x-admin.button variant="secondary" type="button" data-bulk-copy data-bulk-action disabled>{{ __('media::admin.copy_urls') }}</x-admin.button>
                @if ($canDelete)
                    <x-admin.button variant="danger" type="button" data-bulk-delete data-bulk-action disabled>{{ __('media::admin.delete') }}</x-admin.button>
                @endif
                <x-admin.button variant="ghost" type="button" data-bulk-clear data-bulk-action disabled>{{ __('media::admin.clear_selection') }}</x-admin.button>
                <button
                    type="button"
                    class="cf-media-bulk__select-all"
                    data-select-all
                    data-select-all-template="{{ __('media::admin.select_all_loaded', ['count' => ':count']) }}"
                >
                    {{ __('media::admin.select_all_loaded', ['count' => $media->count()]) }}
                </button>
            </div>

            <div class="cf-media-filters" role="toolbar" aria-label="{{ __('media::admin.type') }}">
                @foreach ([
                    '' => 'all_media',
                    'images' => 'images',
                    'pdfs' => 'pdfs',
                    'svg' => 'svg',
                    'webp' => 'webp',
                ] as $value => $label)
                    <button
                        type="button"
                        class="cf-media-chip {{ ($type ?: '') === $value ? 'is-active' : '' }}"
                        data-type-filter="{{ $value }}"
                    >{{ __('media::admin.'.$label) }}</button>
                @endforeach
                <label class="cf-media-filter-select">
                    <span class="sr-only">{{ __('media::admin.date') }}</span>
                    <select class="cf-input" data-period-filter>
                        <option value="">{{ __('media::admin.any_date') }}</option>
                        <option value="today" @selected($period === 'today')>{{ __('media::admin.today') }}</option>
                        <option value="week" @selected($period === 'week')>{{ __('media::admin.past_week') }}</option>
                        <option value="month" @selected($period === 'month')>{{ __('media::admin.past_month') }}</option>
                    </select>
                </label>
            </div>

            <div class="cf-media-layout">
                <aside class="cf-media-folders" data-folder-sidebar>
                    <div class="cf-media-folders__header">
                        <h2>{{ __('media::admin.folders') }}</h2>
                        <button type="button" class="cf-btn cf-btn--ghost" data-toggle-folders aria-label="{{ __('media::admin.collapse_folders') }}">
                            <x-admin.icon name="bars-3" class="h-4 w-4" />
                        </button>
                    </div>
                    <nav class="cf-media-folders__nav">
                        <a
                            href="{{ route('admin.media.index') }}"
                            class="cf-folder-link {{ $folderKey === 'all' ? 'is-active' : '' }}"
                            data-folder-link
                            data-folder="all"
                        >{{ __('media::admin.all_files') }}</a>
                        <a
                            href="{{ route('admin.media.index', ['folder' => 'unfiled']) }}"
                            class="cf-folder-link {{ $folderKey === 'unfiled' ? 'is-active' : '' }}"
                            data-folder-link
                            data-folder="unfiled"
                        >{{ __('media::admin.unfiled') }}</a>
                        @include('media::admin.partials.folder-tree', [
                            'folders' => $folderTree,
                            'currentFolderKey' => $folderKey,
                        ])
                    </nav>
                    <div class="cf-media-folders__resize" data-folder-resize></div>
                </aside>

                <section class="cf-media-stage" data-media-stage>
                    <div class="cf-media-drop-overlay" data-drop-overlay hidden>{{ __('media::admin.drop_to_upload') }}</div>

                    <div class="cf-media-grid" data-media-grid>
                        @forelse ($media as $item)
            @include('media::admin.partials.tile', ['item' => $item, 'canDelete' => $canDelete])
                        @empty
                            <p class="cf-media-empty" data-empty-state>{{ request()->hasAny(['search', 'type', 'period']) ? __('media::admin.empty_filtered') : __('media::admin.empty') }}</p>
                        @endforelse
                    </div>
                    <div class="cf-media-sentinel" data-infinite-sentinel hidden></div>
                    <p class="cf-media-status" data-infinite-status>{{ $media->hasMorePages() ? __('media::admin.load_more') : ($media->isNotEmpty() ? __('media::admin.end_of_library') : '') }}</p>
                </section>
            </div>

            <aside class="cf-media-drawer" data-media-drawer hidden>
                <div class="cf-media-drawer__backdrop" data-drawer-close></div>
                <div class="cf-media-drawer__panel" role="dialog" aria-modal="true" aria-labelledby="media-drawer-title">
                    <header class="cf-media-drawer__header">
                        <h2 id="media-drawer-title">{{ __('media::admin.details') }}</h2>
                        <button type="button" class="cf-btn cf-btn--ghost" data-drawer-close aria-label="{{ __('media::admin.close') }}">
                            <x-admin.icon name="x-mark" class="h-5 w-5" />
                        </button>
                    </header>
                    <div class="cf-media-drawer__body">
                        <div class="cf-media-drawer__preview" data-drawer-preview></div>
                        <dl class="cf-media-drawer__meta">
                            <div><dt>{{ __('media::admin.file_name') }}</dt><dd data-drawer-filename></dd></div>
                            <div><dt>{{ __('media::admin.uuid') }}</dt><dd data-drawer-uuid></dd></div>
                            <div><dt>{{ __('media::admin.mime_type') }}</dt><dd data-drawer-mime></dd></div>
                            <div><dt>{{ __('media::admin.file_size') }}</dt><dd data-drawer-size></dd></div>
                            <div><dt>{{ __('media::admin.dimensions') }}</dt><dd data-drawer-dimensions></dd></div>
                            <div><dt>{{ __('media::admin.uploaded_at') }}</dt><dd data-drawer-created></dd></div>
                        </dl>
                        @if ($canUpdate)
                            <form class="cf-media-drawer__form" data-drawer-form>
                                <label class="block text-sm font-medium text-text">{{ __('media::admin.alt_text') }}
                                    <input type="text" name="alt_text" class="cf-input mt-1" data-drawer-alt maxlength="255">
                                </label>
                                <label class="block text-sm font-medium text-text">{{ __('media::admin.folder') }}
                                    <select name="folder_uuid" class="cf-input mt-1" data-drawer-folder>
                                        <option value="">{{ __('media::admin.unfiled') }}</option>
                                        @foreach ($folders as $folderOption)
                                            <option value="{{ $folderOption->uuid }}">{{ $folderOption->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <x-admin.button variant="primary" type="submit">{{ __('media::admin.save') }}</x-admin.button>
                            </form>
                        @endif
                    </div>
                    <footer class="cf-media-drawer__footer">
                        <x-admin.button variant="secondary" type="button" data-drawer-copy>{{ __('media::admin.copy_url') }}</x-admin.button>
                        <x-admin.button variant="secondary" type="button" data-drawer-copy-uuid>{{ __('media::admin.copy_uuid') }}</x-admin.button>
                        <x-admin.button variant="secondary" type="button" data-drawer-copy-markdown>{{ __('media::admin.copy_markdown') }}</x-admin.button>
                        <x-admin.button variant="secondary" type="button" data-drawer-download>{{ __('media::admin.download') }}</x-admin.button>
                        @if ($canDelete)
                            <x-admin.button variant="danger" type="button" data-drawer-delete>{{ __('media::admin.delete') }}</x-admin.button>
                        @endif
                    </footer>
                </div>
            </aside>

            <div class="cf-media-queue" data-upload-queue hidden>
                <header>
                    <strong data-queue-title>{{ __('media::admin.uploading') }}</strong>
                    <span data-queue-progress></span>
                </header>
                <div class="cf-media-queue__bar"><i data-queue-bar></i></div>
                <ul data-queue-list></ul>
            </div>
        </div>

        @if ($canUpload)
            <dialog class="cf-media-dialog" data-import-dialog>
                <form method="dialog" class="space-y-3" data-import-form>
                    <h3 class="text-lg font-medium">{{ __('media::admin.import_url') }}</h3>
                    <input type="url" required class="cf-input" placeholder="{{ __('media::admin.url_placeholder') }}" data-import-url>
                    <div class="flex justify-end gap-2">
                        <x-admin.button variant="ghost" value="cancel">{{ __('media::admin.cancel') }}</x-admin.button>
                        <x-admin.button variant="primary" value="default">{{ __('media::admin.import') }}</x-admin.button>
                    </div>
                </form>
            </dialog>
        @endif

        @if ($canFolderCreate)
            <dialog class="cf-media-dialog" data-folder-dialog>
                <form method="dialog" class="space-y-3" data-folder-form>
                    <h3 class="text-lg font-medium">{{ __('media::admin.create_folder') }}</h3>
                    <label class="block text-sm font-medium">{{ __('media::admin.folder_name') }}
                        <input name="name" required class="cf-input mt-1" data-folder-name>
                    </label>
                    <label class="block text-sm font-medium">{{ __('media::admin.parent_folder') }}
                        <select class="cf-input mt-1" data-folder-parent>
                            <option value="">{{ __('media::admin.root') }}</option>
                            @foreach ($folders as $folderOption)
                                <option value="{{ $folderOption->uuid }}">{{ $folderOption->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex justify-end gap-2">
                        <x-admin.button variant="ghost" value="cancel">{{ __('media::admin.cancel') }}</x-admin.button>
                        <x-admin.button variant="primary" value="default">{{ __('media::admin.create_folder') }}</x-admin.button>
                    </div>
                </form>
            </dialog>
        @endif
    </x-admin.page>
@endsection
