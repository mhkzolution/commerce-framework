<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Cart\Services\StorefrontSearchAutocompleteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class StorefrontSearchApiController extends Controller
{
    public function __invoke(Request $request, StorefrontSearchAutocompleteService $autocomplete): JsonResponse
    {
        $query = trim((string) $request->query('q', $request->query('search', '')));
        $category = $request->query('category');
        $limit = (int) $request->query('limit', 8);

        if (mb_strlen($query) < 2) {
            return ApiResponse::success($autocomplete->suggest('', is_string($category) ? $category : null, $limit));
        }

        return ApiResponse::success($autocomplete->suggest(
            $query,
            is_string($category) && $category !== '' ? $category : null,
            $limit,
        ));
    }
}
