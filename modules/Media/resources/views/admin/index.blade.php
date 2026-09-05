@extends('layouts.admin')

@section('title', __('media::admin.library'))

@php
    $folderKey = $currentFolderKey ?? 'all';
    $type = request('type');
    $period = request('period');
    $size = request('size');
    $tag = request('tag');
    $sort = request('sort', 'created_at');
    $direction = request('direction', 'desc');
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
    $canFolderUpdate = $authz->can($actor, 'media.folder.update');
    $canFolderDelete = $authz->can($actor, 'media.folder.delete');
    $formatBytes = static function (int $bytes): string {
        $abs = abs($bytes);
        if ($abs < 1024) {
            return $bytes.' B';
        }
        if ($abs < 1048576) {
            return number_format($bytes / 1024, 1).' KB';
        }
        if ($abs < 1073741824) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format($bytes / 1073741824, 2).' GB';
    };
    $insights = $insights ?? ['total' => 0, 'storage_bytes' => 0, 'images' => 0, 'videos' => 0, 'documents' => 0, 'recent' => []];
    $query = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class);
    $mediaLabels = [
        'selected' => __('media::admin.selected', ['count' => ':count']),
        'empty' => __('media::admin.empty'),
        'emptyFiltered' => __('media::admin.empty_filtered'),
        'loadMore' => __('media::admin.load_more'),
        'end' => __('media::admin.end_of_library'),
        'uploading' => __('media::admin.uploading'),
        'completed' => __('media::admin.completed'),
        'failed' => __('media::admin.failed'),
        'retry' => __('media::admin.retry'),
        'queued' => __('media::admin.queued'),
        'processing' => __('media::admin.generating_variants'),
        'deleteOne' => __('media::admin.delete_confirm'),
        'deleteMany' => __('media::admin.delete_selected_confirm'),
        'deleteFolder' => __('media::admin.confirm_delete_folder'),
        'copied' => __('media::admin.copied'),
        'openDetails' => __('media::admin.open_details'),
        'download' => __('media::admin.download'),
        'delete' => __('media::admin.delete'),
        'select' => __('media::admin.select'),
        'selectAll' => __('media::admin.select_all_loaded', ['count' => ':count']),
        'preview' => __('media::admin.preview'),
        'copyUrl' => __('media::admin.copy_url'),
        'edit' => __('media::admin.edit'),
        'unfiled' => __('media::admin.unfiled'),
        'notUsed' => __('media::admin.not_used'),
        'inUse' => __('media::admin.delete_in_use'),
        'create_folder' => __('media::admin.create_folder'),
        'rename_folder' => __('media::admin.rename_folder'),
    ];
    $cropPresets = config('media.crop_presets', []);
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
            data-folder-base-url="{{ url('/admin/media/folders') }}"
            data-bulk-delete-url="{{ route('admin.media.bulk-delete') }}"
            data-bulk-move-url="{{ route('admin.media.bulk-move') }}"
            data-bulk-tag-url="{{ route('admin.media.bulk-tag') }}"
            data-bulk-regenerate-url="{{ route('admin.media.bulk-regenerate') }}"
            data-show-url="{{ url('/admin/media') }}"
            data-download-url="{{ url('/admin/media') }}"
            data-folder="{{ $folderKey }}"
            data-type="{{ $type }}"
            data-period="{{ $period }}"
            data-size="{{ $size }}"
            data-tag="{{ $tag }}"
            data-sort="{{ $sort }}"
            data-direction="{{ $direction }}"
            data-search="{{ request('search') }}"
            data-page="{{ $media->currentPage() }}"
            data-last-page="{{ $media->lastPage() }}"
            data-total="{{ $media->total() }}"
            data-can-upload="{{ $canUpload ? '1' : '0' }}"
            data-can-update="{{ $canUpdate ? '1' : '0' }}"
            data-can-delete="{{ $canDelete ? '1' : '0' }}"
            data-can-folder-create="{{ $canFolderCreate ? '1' : '0' }}"
            data-can-folder-update="{{ $canFolderUpdate ? '1' : '0' }}"
            data-can-folder-delete="{{ $canFolderDelete ? '1' : '0' }}"
            data-folders="{{ $folderOptions->toJson() }}"
        >
            <div class="cf-media-insights" data-media-insights>
                <article class="cf-media-insight">
                    <span>{{ __('media::admin.total_assets') }}</span>
                    <strong data-insight="total">{{ number_format($insights['total'] ?? 0) }}</strong>
                </article>
                <article class="cf-media-insight">
                    <span>{{ __('media::admin.storage_used') }}</span>
                    <strong data-insight="storage">{{ $formatBytes((int) ($insights['storage_bytes'] ?? 0)) }}</strong>
                </article>
                <article class="cf-media-insight">
                    <span>{{ __('media::admin.images') }}</span>
                    <strong data-insight="images">{{ number_format($insights['images'] ?? 0) }}</strong>
                </article>
                <article class="cf-media-insight">
                    <span>{{ __('media::admin.videos') }}</span>
                    <strong data-insight="videos">{{ number_format($insights['videos'] ?? 0) }}</strong>
                </article>
                <article class="cf-media-insight">
                    <span>{{ __('media::admin.documents') }}</span>
                    <strong data-insight="documents">{{ number_format($insights['documents'] ?? 0) }}</strong>
                </article>
                <article class="cf-media-insight cf-media-insight--recent">
                    <span>{{ __('media::admin.recent_uploads') }}</span>
                    <div class="cf-media-insight__recent" data-insight-recent>
                        @forelse (($insights['recent'] ?? []) as $recent)
                            @php
                                $recentUrl = $query->getUrl($recent->uuid, $recent->isImage() ? 'thumbnail' : null)
                                    ?? $query->getUrl($recent->uuid);
                            @endphp
                            <button type="button" data-recent-uuid="{{ $recent->uuid }}" title="{{ $recent->original_filename }}">
                                @if ($recent->isImage() && $recentUrl)
                                    <img src="{{ $recentUrl }}" alt="{{ $recent->original_filename }}">
                                @else
                                    <span>{{ strtoupper($recent->media_type) }}</span>
                                @endif
                            </button>
                        @empty
                            <em>{{ __('media::admin.empty') }}</em>
                        @endforelse
                    </div>
                </article>
            </div>

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
                <div class="cf-media-view-toggle" data-view-toggle role="group" aria-label="{{ __('media::admin.grid_view') }}">
                    <button type="button" class="cf-media-chip is-active" data-view="grid">{{ __('media::admin.grid_view') }}</button>
                    <button type="button" class="cf-media-chip" data-view="list">{{ __('media::admin.list_view') }}</button>
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
                    <div class="cf-media-bulk__move">
                        <input type="text" class="cf-input" data-bulk-tags data-bulk-action disabled placeholder="{{ __('media::admin.tag_placeholder') }}" list="media-tag-suggestions">
                        <x-admin.button variant="secondary" type="button" data-bulk-tag data-bulk-action disabled>{{ __('media::admin.add_tags') }}</x-admin.button>
                    </div>
                    <x-admin.button variant="secondary" type="button" data-bulk-regenerate data-bulk-action disabled>{{ __('media::admin.regenerate_variants') }}</x-admin.button>
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
                    'videos' => 'videos',
                    'documents' => 'documents',
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
                <label class="cf-media-filter-select">
                    <span class="sr-only">{{ __('media::admin.file_size') }}</span>
                    <select class="cf-input" data-size-filter>
                        <option value="">{{ __('media::admin.any_size') }}</option>
                        <option value="small" @selected($size === 'small')>{{ __('media::admin.size_small') }}</option>
                        <option value="medium" @selected($size === 'medium')>{{ __('media::admin.size_medium') }}</option>
                        <option value="large" @selected($size === 'large')>{{ __('media::admin.size_large') }}</option>
                    </select>
                </label>
                <label class="cf-media-filter-select">
                    <span class="sr-only">{{ __('media::admin.tags') }}</span>
                    <select class="cf-input" data-tag-filter>
                        <option value="">{{ __('media::admin.tags') }}</option>
                        @foreach ($tags as $tagOption)
                            <option value="{{ $tagOption->slug }}" @selected($tag === $tagOption->slug)>{{ $tagOption->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <datalist id="media-tag-suggestions">
                @foreach ($tags as $tagOption)
                    <option value="{{ $tagOption->name }}"></option>
                @endforeach
            </datalist>

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
                            'canFolderUpdate' => $canFolderUpdate,
                            'canFolderDelete' => $canFolderDelete,
                            'parentUuid' => null,
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
                            <p class="cf-media-empty" data-empty-state>{{ request()->hasAny(['search', 'type', 'period', 'size', 'tag']) ? __('media::admin.empty_filtered') : __('media::admin.empty') }}</p>
                        @endforelse
                    </div>

                    <div class="cf-media-list-wrap" data-media-list hidden>
                        <table class="cf-media-table">
                            <thead>
                                <tr>
                                    <th class="cf-media-row__check"></th>
                                    <th>{{ __('media::admin.preview') }}</th>
                                    <th><button type="button" data-sort="name">{{ __('media::admin.column_name') }}</button></th>
                                    <th><button type="button" data-sort="type">{{ __('media::admin.column_type') }}</button></th>
                                    <th><button type="button" data-sort="dimensions">{{ __('media::admin.dimensions') }}</button></th>
                                    <th><button type="button" data-sort="size">{{ __('media::admin.file_size') }}</button></th>
                                    <th><button type="button" data-sort="folder">{{ __('media::admin.column_folder') }}</button></th>
                                    <th><button type="button" data-sort="created_at">{{ __('media::admin.column_uploaded') }}</button></th>
                                </tr>
                            </thead>
                            <tbody data-media-list-body>
                                @foreach ($media as $item)
                                    @include('media::admin.partials.list-row', ['item' => $item])
                                @endforeach
                            </tbody>
                        </table>
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
                        <section class="cf-media-drawer__section">
                            <h3>{{ __('media::admin.variants') }}</h3>
                            <ul class="cf-media-variant-list" data-drawer-variants></ul>
                        </section>
                        <section class="cf-media-drawer__section">
                            <h3>{{ __('media::admin.used_by') }}</h3>
                            <ul class="cf-media-usage-list" data-drawer-usage></ul>
                        </section>
                        @if ($canUpdate)
                            <form class="cf-media-drawer__form" data-drawer-form>
                                <label class="block text-sm font-medium text-text">{{ __('media::admin.alt_text') }}
                                    <input type="text" name="alt_text" class="cf-input mt-1" data-drawer-alt maxlength="255">
                                </label>
                                <label class="block text-sm font-medium text-text">{{ __('media::admin.caption') }}
                                    <input type="text" name="caption" class="cf-input mt-1" data-drawer-caption maxlength="255">
                                </label>
                                <label class="block text-sm font-medium text-text">{{ __('media::admin.description') }}
                                    <textarea name="description" class="cf-input mt-1" rows="3" data-drawer-description></textarea>
                                </label>
                                <label class="block text-sm font-medium text-text">{{ __('media::admin.tags') }}
                                    <input type="text" class="cf-input mt-1" data-drawer-tags list="media-tag-suggestions" placeholder="{{ __('media::admin.tag_placeholder') }}">
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
                            <div class="cf-media-drawer__tools">
                                <input type="file" hidden data-replace-input accept="{{ implode(',', config('media.allowed_mimes', [])) }}">
                                <x-admin.button variant="secondary" type="button" data-drawer-replace>{{ __('media::admin.replace_file') }}</x-admin.button>
                                <x-admin.button variant="secondary" type="button" data-drawer-crop>{{ __('media::admin.crop') }}</x-admin.button>
                            </div>
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

            <div class="cf-media-preview" data-media-preview hidden>
                <button type="button" class="cf-media-preview__close" data-preview-close aria-label="{{ __('media::admin.close') }}">&times;</button>
                <img alt="" data-preview-image>
            </div>

            <div class="cf-media-queue" data-upload-queue hidden>
                <header>
                    <strong data-queue-title>{{ __('media::admin.uploading') }}</strong>
                    <span data-queue-progress></span>
                </header>
                <div class="cf-media-queue__bar"><i data-queue-bar></i></div>
                <ul data-queue-list></ul>
            </div>
        </div>

        <dialog class="cf-media-dialog cf-media-dialog--wide" data-crop-dialog>
            <form method="dialog" class="space-y-3" data-crop-form>
                <h3 class="text-lg font-medium">{{ __('media::admin.crop') }}</h3>
                <p class="text-sm text-muted">{{ __('media::admin.crop_hint') }}</p>
                <div class="cf-media-crop-presets">
                    @foreach (config('media.crop_presets', []) as $preset => $config)
                        <button type="button" class="cf-media-chip" data-crop-preset="{{ $preset }}">{{ __('media::admin.crop_'.$preset) }}</button>
                    @endforeach
                </div>
                <canvas class="cf-media-crop-canvas" data-crop-canvas width="480" height="320"></canvas>
                <div class="flex justify-end gap-2">
                    <x-admin.button variant="ghost" value="cancel">{{ __('media::admin.cancel') }}</x-admin.button>
                    <x-admin.button variant="primary" value="default" data-apply-crop>{{ __('media::admin.apply_crop') }}</x-admin.button>
                </div>
            </form>
        </dialog>

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

        @if ($canFolderCreate || $canFolderUpdate)
            <dialog class="cf-media-dialog" data-folder-dialog>
                <form method="dialog" class="space-y-3" data-folder-form>
                    <h3 class="text-lg font-medium" data-folder-dialog-title>{{ __('media::admin.create_folder') }}</h3>
                    <input type="hidden" data-folder-edit-uuid>
                    <label class="block text-sm font-medium">{{ __('media::admin.folder_name') }}
                        <input name="name" required class="cf-input mt-1" data-folder-name>
                    </label>
                    <label class="block text-sm font-medium" data-folder-parent-wrap>
                        {{ __('media::admin.parent_folder') }}
                        <select class="cf-input mt-1" data-folder-parent>
                            <option value="">{{ __('media::admin.root') }}</option>
                            @foreach ($folders as $folderOption)
                                <option value="{{ $folderOption->uuid }}">{{ $folderOption->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <div class="flex justify-end gap-2">
                        <x-admin.button variant="ghost" value="cancel">{{ __('media::admin.cancel') }}</x-admin.button>
                        <x-admin.button variant="primary" value="default" data-folder-submit>{{ __('media::admin.create_folder') }}</x-admin.button>
                    </div>
                </form>
            </dialog>
        @endif

        <script type="application/json" data-media-labels>@json($mediaLabels)</script>
        <script type="application/json" data-crop-presets>@json($cropPresets)</script>
    </x-admin.page>
@endsection
