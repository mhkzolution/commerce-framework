<?php

declare(strict_types=1);

namespace Plugins\HelloWorld;

use Commerce\Contracts\Hook\HookRegistryInterface;
use Illuminate\Support\ServiceProvider;
use Plugins\HelloWorld\Contracts\GreetingServiceInterface;
use Plugins\HelloWorld\Services\GreetingService;

final class HelloWorldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GreetingServiceInterface::class, GreetingService::class);
    }

    public function boot(): void
    {
        if (! $this->app->bound(HookRegistryInterface::class)) {
            return;
        }

        $hooks = $this->app->make(HookRegistryInterface::class);
        $greeting = $this->app->make(GreetingServiceInterface::class);

        $hooks->register('storefront.home.banner', static function (array $context) use ($greeting): void {
            $context['view']->with('pluginGreeting', $greeting->greet('storefront'));
        });

        $hooks->registerFilter('admin.dashboard.message', static function (mixed $message) use ($greeting): mixed {
            return $greeting->greet('admin');
        }, 20);
    }
}
