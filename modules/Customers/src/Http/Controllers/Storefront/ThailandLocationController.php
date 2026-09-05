<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Storefront;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Customers\Services\ThailandLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class ThailandLocationController extends Controller
{
    public function __construct(
        private readonly ThailandLocationService $locations,
    ) {}

    public function provinces(): JsonResponse
    {
        return ApiResponse::success($this->locations->provinces());
    }

    public function districts(int $province): JsonResponse
    {
        return ApiResponse::success($this->locations->districts($province));
    }

    public function subdistricts(int $district): JsonResponse
    {
        return ApiResponse::success($this->locations->subdistricts($district));
    }
}
