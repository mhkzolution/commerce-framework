<?php

use Commerce\Customers\Support\StorefrontAuthRedirect;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(static function (Request $request): string {
            return StorefrontAuthRedirect::loginForRequest($request);
        });
        $middleware->redirectUsersTo(static function (Request $request): string {
            return StorefrontAuthRedirect::homeForRequest($request);
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('cms:publish-scheduled')->everyMinute();
    })->create();
