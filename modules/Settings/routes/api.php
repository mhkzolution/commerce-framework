<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Settings\SiteIdentityServiceInterface;
use Commerce\Settings\Services\SettingQueryService;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/settings/public', function (
        SettingQueryService $settings,
        SiteIdentityServiceInterface $siteIdentity,
    ) {
        return ApiResponse::success([
            ...$settings->getPublicSettings(),
            'site' => $siteIdentity->toArray(),
        ]);
    })->name('api.v1.settings.public');
});
