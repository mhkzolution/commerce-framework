<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Controllers\Admin;

use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Media\Contracts\MediaServiceInterface;
use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Http\Requests\BulkDeleteMediaRequest;
use Commerce\Media\Http\Requests\BulkMoveMediaRequest;
use Commerce\Media\Http\Requests\BulkRegenerateMediaRequest;
use Commerce\Media\Http\Requests\BulkTagMediaRequest;
use Commerce\Media\Http\Requests\ImportMediaRequest;
use Commerce\Media\Http\Requests\ReplaceMediaRequest;
use Commerce\Media\Http\Requests\UpdateMediaRequest;
use Commerce\Media\Http\Requests\UploadMediaRequest;
use Commerce\Media\Http\Resources\MediaResource;
use Commerce\Media\Models\MediaFolder;
use Commerce\Media\Models\MediaTag;
use Commerce\Media\Services\MediaFolderQueryService;
use Commerce\Media\Services\MediaQueryService;
use Commerce\Media\Services\MediaUsageService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
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
        private readonly MediaUsageService $usageService,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $paginator = $this->browse($request);

        if ($request->expectsJson()) {
            return $this->libraryJson($paginator);
        }

        $folderUuid = $this->folderParam($request);
        $currentFolder = ($folderUuid && ! in_array($folderUuid, ['all', 'unfiled'], true))
            ? $this->folderQueryService->findByUuid($folderUuid)
            : null;

        $insights = $this->queryService->insights();

        return view('media::admin.index', [
            'media' => $paginator,
            'folderTree' => $this->folderQueryService->tree(),
            'folders' => $this->folderQueryService->flat(),
            'currentFolder' => $currentFolder,
            'currentFolderKey' => $folderUuid ?? 'all',
            'insights' => $insights,
            'tags' => MediaTag::query()->orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, string $media): JsonResponse
    {
        $item = $this->queryService->findByUuid($media);

        if ($item === null) {
            abort(404);
        }

        $payload = (new MediaResource($item))->toArray($request);
        $payload['usage'] = $this->usageService->forUuid($item->uuid);

        return response()->json([
            'data' => $payload,
        ]);
    }

    public function store(UploadMediaRequest $request): RedirectResponse|JsonResponse
    {
        $media = $this->uploadService->upload(
            $request->file('file'),
            $request->validated('folder_uuid'),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'data' => new MediaResource($media),
            ], 201);
        }

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->validated('folder_uuid'),
            ]))
            ->with('status', 'File uploaded successfully.');
    }

    public function import(ImportMediaRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $media = $this->uploadService->upload(
                $request->validated('url'),
                $request->validated('folder_uuid'),
            );
        } catch (DomainException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return redirect()
                ->back()
                ->withErrors(['url' => $exception->getMessage()])
                ->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'data' => new MediaResource($media),
            ], 201);
        }

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->validated('folder_uuid'),
            ]))
            ->with('status', 'File imported from URL.');
    }

    public function update(UpdateMediaRequest $request, string $media): RedirectResponse|JsonResponse
    {
        $folderId = null;
        $syncFolder = false;

        if ($request->has('folder_id')) {
            $syncFolder = true;
            $folderId = $request->filled('folder_id') ? (int) $request->input('folder_id') : null;
        } elseif ($request->exists('folder_uuid')) {
            $syncFolder = true;
            $folderUuid = $request->input('folder_uuid');
            $folderId = is_string($folderUuid) && $folderUuid !== ''
                ? MediaFolder::query()->where('uuid', $folderUuid)->value('id')
                : null;
            $folderId = $folderId !== null ? (int) $folderId : null;
        }

        $item = $this->mediaService->update($media, new UpdateMediaData(
            altText: $request->validated('alt_text'),
            caption: $request->validated('caption'),
            description: $request->validated('description'),
            folderId: $folderId,
            syncFolder: $syncFolder,
            tags: $request->has('tags') ? array_values($request->validated('tags') ?? []) : null,
            crop: $request->validated('crop'),
        ));

        if ($request->expectsJson()) {
            return response()->json([
                'data' => new MediaResource($item),
            ]);
        }

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->input('folder_uuid'),
                'search' => $request->input('search'),
            ]))
            ->with('status', 'Media updated.');
    }

    public function destroy(Request $request, string $media): RedirectResponse|JsonResponse
    {
        try {
            $this->mediaService->delete($media, $request->boolean('force'));
        } catch (DomainException $exception) {
            $usages = $this->usageService->forUuid($media);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'usage' => $usages,
                ], 409);
            }

            return redirect()->back()->withErrors(['media' => $exception->getMessage()]);
        }

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->input('folder'),
                'search' => $request->input('search'),
            ]))
            ->with('status', 'Media deleted.');
    }

    public function bulkDelete(BulkDeleteMediaRequest $request): RedirectResponse|JsonResponse
    {
        $uuids = $request->validated('uuids');
        $force = $request->boolean('force');
        $blocked = [];

        if (! $force) {
            foreach ($uuids as $uuid) {
                $usage = $this->usageService->forUuid($uuid);
                if ($usage !== []) {
                    $blocked[$uuid] = $usage;
                }
            }
        }

        if ($blocked !== []) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Some files are in use and were not deleted.',
                    'usage' => $blocked,
                ], 409);
            }

            return redirect()->back()->withErrors(['media' => 'Some files are in use and were not deleted.']);
        }

        $deleted = $this->mediaService->deleteMany($uuids, $force);

        if ($request->expectsJson()) {
            return response()->json(['deleted' => $deleted]);
        }

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $request->input('folder'),
                'search' => $request->input('search'),
            ]))
            ->with('status', $deleted.' files deleted.');
    }

    public function bulkMove(BulkMoveMediaRequest $request): RedirectResponse|JsonResponse
    {
        $folderUuid = $request->validated('folder_uuid');
        $folderId = $folderUuid
            ? MediaFolder::query()->where('uuid', $folderUuid)->value('id')
            : null;

        $moved = $this->mediaService->moveMany(
            $request->validated('uuids'),
            $folderId !== null ? (int) $folderId : null,
        );

        if ($request->expectsJson()) {
            return response()->json(['moved' => $moved]);
        }

        return redirect()
            ->route('admin.media.index', array_filter([
                'folder' => $folderUuid,
                'search' => $request->input('search'),
            ]))
            ->with('status', $moved.' files moved.');
    }

    public function bulkTag(BulkTagMediaRequest $request): JsonResponse
    {
        $tagged = $this->mediaService->tagMany(
            $request->validated('uuids'),
            $request->validated('tags'),
        );

        return response()->json(['tagged' => $tagged]);
    }

    public function bulkRegenerate(BulkRegenerateMediaRequest $request): JsonResponse
    {
        $regenerated = $this->mediaService->regenerateMany($request->validated('uuids'));

        return response()->json(['regenerated' => $regenerated]);
    }

    public function replace(ReplaceMediaRequest $request, string $media): JsonResponse
    {
        $item = $this->uploadService->replace($media, $request->file('file'));

        return response()->json([
            'data' => new MediaResource($item),
        ]);
    }

    public function download(string $media): mixed
    {
        $item = $this->queryService->findByUuid($media);

        if ($item === null) {
            abort(404);
        }

        return Storage::disk($item->disk)->download($item->path, $item->original_filename);
    }

    private function browse(Request $request): LengthAwarePaginator
    {
        return $this->queryService->paginate(
            folderUuid: $this->folderParam($request),
            search: $request->string('search')->toString() ?: null,
            perPage: (int) $request->input('per_page', 24),
            type: $request->string('type')->toString() ?: null,
            period: $request->string('period')->toString() ?: null,
            page: max(1, (int) $request->input('page', 1)),
            size: $request->string('size')->toString() ?: null,
            sort: $request->string('sort')->toString() ?: null,
            direction: $request->string('direction')->toString() ?: 'desc',
            tag: $request->string('tag')->toString() ?: null,
        );
    }

    private function folderParam(Request $request): ?string
    {
        $folder = $request->string('folder')->toString();

        return $folder !== '' ? $folder : null;
    }

    private function libraryJson(LengthAwarePaginator $paginator): JsonResponse
    {
        $insights = $this->queryService->insights();
        $request = request();

        return response()->json([
            'data' => MediaResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'insights' => [
                    'total' => $insights['total'],
                    'storage_bytes' => $insights['storage_bytes'],
                    'images' => $insights['images'],
                    'videos' => $insights['videos'],
                    'documents' => $insights['documents'],
                    'recent' => array_map(
                        static fn ($item): array => (new MediaResource($item))->toArray($request),
                        $insights['recent'],
                    ),
                ],
            ],
        ]);
    }
}
