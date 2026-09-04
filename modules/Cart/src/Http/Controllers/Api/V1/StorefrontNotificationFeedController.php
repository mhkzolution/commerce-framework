<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers\Api\V1;

use Commerce\Api\Responses\ApiResponse;
use Commerce\Cart\Services\StorefrontNotificationFeedService;
use Commerce\Core\Modules\ModuleService;
use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class StorefrontNotificationFeedController extends Controller
{
    public function __construct(
        private readonly StorefrontNotificationFeedService $feedService,
        private readonly CustomerExperienceConfig $customerExperienceConfig,
    ) {}

    public function index(): JsonResponse
    {
        if (ModuleService::isDisabled('customer-experience')) {
            return ApiResponse::success([]);
        }

        $config = $this->customerExperienceConfig->notifications();

        return ApiResponse::success($this->feedService->items(), [
            'duration' => (int) ($config['duration'] ?? 5),
            'position' => (string) ($config['position'] ?? 'bottom-right'),
            'enabled' => (bool) ($config['enabled'] ?? false),
        ]);
    }
}
