<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Controllers\Admin;

use Commerce\Media\Services\MediaQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class MediaPickerController extends Controller
{
    public function __construct(
        private readonly MediaQueryService $queryService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $paginator = $this->queryService->picker(
            search: $request->string('search')->toString() ?: null,
            imagesOnly: $request->boolean('images_only', true),
            perPage: 24,
            page: max(1, (int) $request->input('page', 1)),
        );

        $items = collect($paginator->items())->map(function ($media): array {
            return [
                'uuid' => $media->uuid,
                'filename' => $media->original_filename,
                'mime_type' => $media->mime_type,
                'url' => $this->queryService->getUrl($media->uuid, 'thumbnail'),
                'preview_url' => $this->queryService->getUrl($media->uuid),
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
