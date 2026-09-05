<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Media\Contracts\MediaServiceInterface;
use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Http\Requests\UpdateMediaRequest;
use Commerce\Media\Http\Requests\UploadMediaRequest;
use Commerce\Media\Http\Resources\MediaResource;
use Commerce\Media\Services\MediaQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class MediaController extends Controller
{
    public function __construct(
        private readonly MediaQueryService $queryService,
        private readonly MediaUploadServiceInterface $uploadService,
        private readonly MediaServiceInterface $mediaService,
    ) {}

    public function show(string $uuid): JsonResponse
    {
        $media = $this->queryService->findByUuid($uuid);

        if ($media === null) {
            return ApiResponse::error('media.not_found', 'Media not found.', status: 404);
        }

        return ApiResponse::success(new MediaResource($media));
    }

    public function store(UploadMediaRequest $request): JsonResponse
    {
        $media = $this->uploadService->upload(
            $request->file('file'),
            $request->validated('folder_uuid'),
        );

        return ApiResponse::success(new MediaResource($media), status: 201);
    }

    public function update(UpdateMediaRequest $request, string $uuid): JsonResponse
    {
        $media = $this->mediaService->update($uuid, new UpdateMediaData(
            altText: $request->validated('alt_text'),
            caption: $request->validated('caption'),
            description: $request->validated('description'),
        ));

        return ApiResponse::success(new MediaResource($media));
    }

    public function destroy(string $uuid): Response
    {
        $this->mediaService->delete($uuid);

        return response()->noContent();
    }

    public function index(): JsonResponse
    {
        $paginator = $this->queryService->paginate(perPage: 24);

        return ApiResponse::success(
            MediaResource::collection($paginator->items()),
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
    }
}
