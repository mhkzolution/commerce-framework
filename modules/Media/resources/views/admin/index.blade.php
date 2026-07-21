@extends('layouts.admin')

@section('title', 'Media Library')

@section('page')
    <x-admin.page
        title="Media Library"
        :description="$currentFolder ? 'Folder: ' . $currentFolder->name : 'Root folder'"
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Catalog'],
                ['label' => 'Media', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
            <aside class="space-y-4">
                <x-admin.card title="Folders">
                    <a
                        href="{{ route('admin.media.index') }}"
                        @class([
                            'cf-folder-link',
                            'is-active' => $currentFolder === null && !request('search'),
                        ])
                    >
                        All root files
                    </a>
                    @include('media::admin.partials.folder-tree', [
                        'folders' => $folderTree,
                        'currentFolder' => $currentFolder,
                    ])

                    <form method="POST" action="{{ route('admin.media.folders.store') }}" class="mt-4 space-y-2 border-t border-border pt-4">
                        @csrf
                        <input type="hidden" name="parent_uuid" value="{{ $currentFolder?->uuid }}">
                        <input name="name" placeholder="New folder name" required class="cf-input">
                        <x-admin.button type="submit" variant="secondary" class="w-full">Create folder</x-admin.button>
                    </form>

                    @if ($currentFolder)
                        <form method="POST" action="{{ route('admin.media.folders.update', $currentFolder) }}" class="mt-3 space-y-2 border-t border-border pt-4">
                            @csrf
                            @method('PUT')
                            <input name="name" value="{{ $currentFolder->name }}" required class="cf-input">
                            <select name="parent_uuid" class="cf-input">
                                <option value="">— Root —</option>
                                @foreach ($folders as $folderOption)
                                    @if ($folderOption->uuid !== $currentFolder->uuid)
                                        <option value="{{ $folderOption->uuid }}" @selected($currentFolder->parent_id === $folderOption->id)>{{ $folderOption->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <x-admin.button type="submit" variant="secondary" class="w-full">Rename / move folder</x-admin.button>
                        </form>
                        <form method="POST" action="{{ route('admin.media.folders.destroy', $currentFolder) }}" class="mt-2" onsubmit="return confirm('Delete this folder?')">
                            @csrf
                            @method('DELETE')
                            <x-admin.button type="submit" variant="danger" class="w-full">Delete folder</x-admin.button>
                        </form>
                    @endif
                </x-admin.card>

                <x-admin.card title="Upload">
                    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <input type="hidden" name="folder_uuid" value="{{ $currentFolder?->uuid }}">
                        <input type="file" name="file" required class="cf-file-input">
                        @error('file')<p class="text-sm text-danger">{{ $message }}</p>@enderror
                        <x-admin.button type="submit" variant="primary" class="w-full">Upload file</x-admin.button>
                    </form>

                    <form method="GET" action="{{ route('admin.media.index') }}" class="mt-6">
                        <label class="block text-sm font-medium text-text" for="search">Search all media</label>
                        <x-admin.search-input id="search" name="search" placeholder="Filename or alt text" class="mt-1" />
                    </form>
                </x-admin.card>
            </aside>

            <section>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($media as $item)
                        @php
                            $url = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)->getUrl($item->uuid, $item->isImage() ? 'thumbnail' : null);
                            $fullUrl = app(\Commerce\Contracts\Media\MediaQueryServiceInterface::class)->getUrl($item->uuid);
                        @endphp
                        <article class="overflow-hidden rounded-xl border border-border bg-card shadow-sm">
                            <div class="flex aspect-video items-center justify-center bg-primary-subtle">
                                @if ($item->isImage() && $fullUrl)
                                    <img src="{{ $url ?? $fullUrl }}" alt="{{ $item->alt_text ?? $item->original_filename }}" class="h-full w-full object-cover">
                                @else
                                    <div class="text-center text-sm text-muted">
                                        <div class="font-medium uppercase">{{ $item->media_type }}</div>
                                        <div class="mt-1">{{ $item->mime_type }}</div>
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3 p-4">
                                <div>
                                    <p class="truncate text-sm font-medium text-text" title="{{ $item->original_filename }}">{{ $item->original_filename }}</p>
                                    <p class="text-xs text-muted">
                                        {{ number_format($item->size / 1024, 1) }} KB
                                        @if ($item->width && $item->height)· {{ $item->width }}×{{ $item->height }}@endif
                                        @if ($item->variants->isNotEmpty())· {{ $item->variants->count() }} variants@endif
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('admin.media.update', $item) }}" class="space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="folder_uuid" value="{{ $currentFolder?->uuid }}">
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                    <input type="text" name="alt_text" value="{{ old('alt_text', $item->alt_text) }}" placeholder="Alt text" class="cf-input">
                                    <x-admin.button variant="link" type="submit" class="!px-0">Save alt text</x-admin.button>
                                </form>

                                <form method="POST" action="{{ route('admin.media.update', $item) }}" class="space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="alt_text" value="{{ $item->alt_text }}">
                                    <input type="hidden" name="folder_uuid" value="{{ $currentFolder?->uuid }}">
                                    <input type="hidden" name="search" value="{{ request('search') }}">
                                    <select name="folder_id" class="cf-input">
                                        <option value="">Move to root</option>
                                        @foreach ($folders as $folderOption)
                                            <option value="{{ $folderOption->id }}" @selected($item->folder_id === $folderOption->id)>{{ $folderOption->name }}</option>
                                        @endforeach
                                    </select>
                                    <x-admin.button variant="link" type="submit" class="!px-0">Move file</x-admin.button>
                                </form>

                                <div class="flex items-center justify-between text-sm">
                                    <x-admin.button variant="link" :href="route('admin.media.download', $item)" class="!px-0">Download</x-admin.button>
                                    <form method="POST" action="{{ route('admin.media.destroy', $item) }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="folder" value="{{ $currentFolder?->uuid }}">
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <button type="submit" class="text-sm text-danger hover:underline" onclick="return confirm('Delete this file?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-border bg-card p-12 text-center text-sm text-muted">
                            No media in this folder yet.
                        </div>
                    @endforelse
                </div>

                @if ($media->hasPages())
                    <div class="mt-6">{{ $media->withQueryString()->links() }}</div>
                @endif
            </section>
        </div>
    </x-admin.page>
@endsection
