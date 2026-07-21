<?php

declare(strict_types=1);

use Commerce\Iam\Http\Controllers\Api\AuthApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::prefix('auth')->name('api.v1.auth.')->group(function (): void {
        Route::post('/login', [AuthApiController::class, 'login'])->name('login');
        Route::post('/two-factor/verify', [AuthApiController::class, 'verifyTwoFactor'])->name('two-factor.verify');
        Route::post('/forgot-password', [AuthApiController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('/reset-password', [AuthApiController::class, 'resetPassword'])->name('reset-password');
        Route::get('/oauth/{provider}/redirect', [AuthApiController::class, 'oauthRedirect'])->name('oauth.redirect');
        Route::get('/oauth/{provider}/callback', [AuthApiController::class, 'oauthCallback'])->name('oauth.callback');

        Route::middleware('api.token')->group(function (): void {
            Route::get('/me', [AuthApiController::class, 'me'])->name('me');
            Route::post('/logout', [AuthApiController::class, 'logout'])->name('logout');
            Route::get('/tokens', [AuthApiController::class, 'listTokens'])->name('tokens.index');
            Route::post('/tokens', [AuthApiController::class, 'createToken'])->name('tokens.store');
            Route::delete('/tokens/{tokenUuid}', [AuthApiController::class, 'revokeToken'])->name('tokens.destroy');
            Route::post('/two-factor/enable', [AuthApiController::class, 'enableTwoFactor'])->name('two-factor.enable');
            Route::post('/two-factor/confirm', [AuthApiController::class, 'confirmTwoFactor'])->name('two-factor.confirm');
            Route::post('/two-factor/disable', [AuthApiController::class, 'disableTwoFactor'])->name('two-factor.disable');
            Route::get('/sessions', [AuthApiController::class, 'sessions'])->name('sessions.index');
            Route::put('/profile', [AuthApiController::class, 'updateProfile'])->name('profile.update');
            Route::post('/impersonation/stop', [AuthApiController::class, 'stopImpersonation'])->name('impersonation.stop');
        });
    });
});
