<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Controllers\Admin;

use Commerce\Media\Http\Resources\MediaResource;
use Commerce\Media\Services\MediaFolderQueryService;
use Commerce\Media\Services\MediaQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class MediaPickerController extends Controller
{
    public function __construct(
        private readonly MediaQueryService $queryService,
        private readonly MediaFolderQueryService $folderQueryService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $paginator = $this->queryService->picker(
            search: $request->string('search')->toString() ?: null,
            imagesOnly: $request->boolean('images_only', true),
            perPage: 24,
            page: max(1, (int) $request->input('page', 1)),
            folderUuid: $request->string('folder')->toString() ?: null,
            recent: $request->boolean('recent'),
        );

        $items = collect($paginator->items())->map(function ($media) use ($request): array {
            return (new MediaResource($media))->toArray($request);
        })->values();

        return response()->json([
            'data' => $items,
            'folders' => collect($this->folderQueryService->flat())->map(static fn ($folder): array => [
                'uuid' => $folder->uuid,
                'name' => $folder->name,
                'parent_uuid' => $folder->parent?->uuid,
            ])->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
