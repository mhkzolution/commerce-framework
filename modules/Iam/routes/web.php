<?php

declare(strict_types=1);

use Commerce\Iam\Http\Controllers\Admin\PermissionController;
use Commerce\Iam\Http\Controllers\Admin\RoleController;
use Commerce\Iam\Http\Controllers\Admin\UserController;
use Commerce\Iam\Http\Controllers\Auth\LoginController;
use Commerce\Iam\Http\Controllers\Auth\TwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/admin/login', [LoginController::class, 'create'])->name('admin.login');
        Route::post('/admin/login', [LoginController::class, 'store'])->name('admin.login.submit');
        Route::get('/admin/login/two-factor', [TwoFactorChallengeController::class, 'create'])->name('admin.login.two-factor');
        Route::post('/admin/login/two-factor', [TwoFactorChallengeController::class, 'store'])->name('admin.login.two-factor.submit');
        Route::get('/admin/login/oauth/{provider}', [LoginController::class, 'oauthRedirect'])->name('admin.login.oauth.redirect');
        Route::get('/admin/login/oauth/{provider}/callback', [LoginController::class, 'oauthCallback'])->name('admin.login.oauth.callback');
    });

    Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
        Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

        Route::prefix('iam')->name('iam.')->group(function (): void {
            Route::middleware('permission:iam.user.view')->group(function (): void {
                Route::get('/users', [UserController::class, 'index'])->name('users.index');

                Route::middleware('permission:iam.user.create')->group(function (): void {
                    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
                    Route::post('/users', [UserController::class, 'store'])->name('users.store');
                });

                Route::middleware('permission:iam.user.update')->group(function (): void {
                    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
                    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
                });

                Route::delete('/users/{user}', [UserController::class, 'destroy'])
                    ->middleware('permission:iam.user.delete')
                    ->name('users.destroy');
            });

            Route::middleware('permission:iam.role.view')->group(function (): void {
                Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');

                Route::middleware('permission:iam.role.create')->group(function (): void {
                    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
                    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
                });

                Route::middleware('permission:iam.role.update')->group(function (): void {
                    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
                    Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
                });

                Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
                    ->middleware('permission:iam.role.delete')
                    ->name('roles.destroy');
            });

            Route::get('/permissions', [PermissionController::class, 'index'])
                ->middleware('permission:iam.permission.view')
                ->name('permissions.index');
        });
    });
});
