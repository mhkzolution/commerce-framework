<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::get('/settings/public', function (SettingQueryServiceInterface $settings) {
        return ApiResponse::success([
            'store.name' => $settings->get('store.name'),
            'store.currency' => $settings->get('store.currency'),
            'store.locale' => $settings->get('store.locale'),
        ]);
    })->name('api.v1.settings.public');
});
