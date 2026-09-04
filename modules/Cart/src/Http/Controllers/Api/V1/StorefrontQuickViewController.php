<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Cart\Services\StorefrontQuickViewService;
use Commerce\Core\Modules\ModuleService;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class StorefrontQuickViewController extends Controller
{
    public function __construct(
        private readonly StorefrontQuickViewService $quickViewService,
        private readonly CustomerExperienceConfig $customerExperienceConfig,
    ) {}

    public function show(string $uuid): JsonResponse
    {
        if (ModuleService::isDisabled('customer-experience') || ! $this->customerExperienceConfig->quickViewEnabled()) {
            return ApiResponse::error('quick_view.disabled', 'Quick view is disabled.', status: 404);
        }

        $payload = $this->quickViewService->payload($uuid);

        if ($payload === null) {
            return ApiResponse::error('quick_view.not_found', 'Product not found.', status: 404);
        }

        return ApiResponse::success($payload);
    }
}
