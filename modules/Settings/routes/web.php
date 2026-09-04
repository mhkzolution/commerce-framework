<?php

declare(strict_types=1);

use Commerce\Settings\Http\Controllers\Admin\AppearanceController;
use Commerce\Settings\Http\Controllers\Admin\AuthSettingsController;
use Commerce\Settings\Http\Controllers\Admin\CustomerExperienceController;
use Commerce\Settings\Http\Controllers\Admin\FooterController;
use Commerce\Settings\Http\Controllers\Admin\MailSettingsController;
use Commerce\Settings\Http\Controllers\Admin\SettingsController;
use Commerce\Settings\Http\Controllers\Admin\SiteIdentityController;
use Commerce\Settings\Http\Controllers\Admin\TranslationController;
use Commerce\Settings\Http\Controllers\Admin\WebsiteSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware(['auth', 'permission:settings.setting.view'])
        ->prefix('admin/settings')
        ->name('admin.settings.')
        ->group(function (): void {
            Route::get('/appearance', [AppearanceController::class, 'show'])->name('appearance.show');
            Route::put('/appearance', [AppearanceController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('appearance.update');

            Route::middleware('module:customer-experience')->group(function (): void {
                Route::get('/customer-experience', [CustomerExperienceController::class, 'show'])->name('customer-experience.show');
                Route::put('/customer-experience', [CustomerExperienceController::class, 'update'])
                    ->middleware('permission:settings.setting.update')
                    ->name('customer-experience.update');
            });

            Route::middleware('module:footer-management')->group(function (): void {
                Route::get('/footer', [FooterController::class, 'show'])->name('footer.show');
                Route::put('/footer', [FooterController::class, 'update'])
                    ->middleware('permission:settings.setting.update')
                    ->name('footer.update');
                Route::post('/footer/preview', [FooterController::class, 'preview'])
                    ->middleware('permission:settings.setting.update')
                    ->name('footer.preview');
            });

            Route::get('/website', [WebsiteSettingsController::class, 'show'])->name('website.show');
            Route::put('/website', [WebsiteSettingsController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('website.update');
            Route::get('/site-identity', [SiteIdentityController::class, 'show'])->name('site-identity.show');
            Route::put('/site-identity', [SiteIdentityController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('site-identity.update');

            Route::get('/mail', [MailSettingsController::class, 'show'])->name('mail.show');
            Route::put('/mail', [MailSettingsController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('mail.update');

            Route::get('/auth', [AuthSettingsController::class, 'show'])->name('auth.show');
            Route::put('/auth', [AuthSettingsController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('auth.update');

            Route::get('/translations', [TranslationController::class, 'index'])->name('translations.index');
            Route::get('/translations/{namespace}/{file}', [TranslationController::class, 'edit'])->name('translations.edit');
            Route::put('/translations/{namespace}/{file}', [TranslationController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('translations.update');

            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::put('/{group}', [SettingsController::class, 'update'])
                ->middleware('permission:settings.setting.update')
                ->name('update');
            Route::post('/{group}/reset', [SettingsController::class, 'reset'])
                ->middleware('permission:settings.setting.update')
                ->name('reset');
        });
});
