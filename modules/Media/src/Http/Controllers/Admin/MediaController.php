<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Controllers\Admin;

use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Media\Contracts\MediaServiceInterface;
use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Http\Requests\UpdateMediaRequest;
use Commerce\Media\Http\Requests\UploadMediaRequest;
use Commerce\Media\Models\MediaFolder;
use Commerce\Media\Services\MediaFolderQueryService;
use Commerce\Media\Services\MediaQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class MediaController extends Controller
{
    public function __construct(
        private readonly MediaQueryService $queryService,
        private readonly MediaFolderQueryService $folderQueryService,
        private readonly MediaUploadServiceInterface $uploadService,
        private readonly MediaServiceInterface $mediaService,
    ) {}

    public function index(Request $request): View
    {
        $folderUuid = $request->string('folder')->toString() ?: null;
        $currentFolder = $folderUuid
            ? $this->folderQueryService->findByUuid($folderUuid)
            : null;

        return view('media::admin.index', [
            'media' => $this->queryService->paginate(
                folderUuid: $folderUuid,
                search: $request->string('search')->toString() ?: null,
            ),
            'folderTree' => $this->folderQueryService->tree(),
            'folders' => $this->folderQueryService->flat(),
            'currentFolder' => $currentFolder,
        ]);
    }

    public function store(UploadMediaRequest $request): RedirectResponse
    {
        $this->uploadService->upload(
            $request->file('file'),
            $request->validated('folder_uuid'),
        );

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->validated('folder_uuid'),
            ]))
            ->with('status', 'File uploaded successfully.');
    }

    public function update(UpdateMediaRequest $request, string $media): RedirectResponse
    {
        $this->mediaService->update($media, new UpdateMediaData(
            altText: $request->validated('alt_text'),
            folderId: $request->filled('folder_id') ? (int) $request->input('folder_id') : null,
            syncFolder: $request->has('folder_id'),
        ));

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->input('folder_uuid'),
                'search' => $request->input('search'),
            ]))
            ->with('status', 'Media updated.');
    }

    public function destroy(Request $request, string $media): RedirectResponse
    {
        $this->mediaService->delete($media);

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->input('folder'),
                'search' => $request->input('search'),
            ]))
            ->with('status', 'Media deleted.');
    }

    public function download(string $media): mixed
    {
        $item = $this->queryService->findByUuid($media);

        if ($item === null) {
            abort(404);
        }

        return Storage::disk($item->disk)->download($item->path, $item->original_filename);
    }
}
