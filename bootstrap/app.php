<?php

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
    ->withSchedule(function (Schedule $schedule): void {
        if (config('commerce.outbox.schedule', true)) {
            $schedule->command('commerce:outbox:publish')->everyMinute();
        }

        $schedule->command('catalog:sync-automated-collections')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(static function (Request $request): string {
            if ($request->is('account') || $request->is('account/*')) {
                return route('storefront.account.login');
            }

            return '/admin/login';
        });
        $middleware->redirectUsersTo('/admin');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('cms:publish-scheduled')->everyMinute();
    })->create();
