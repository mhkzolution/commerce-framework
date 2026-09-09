<?php

declare(strict_types=1);

namespace Commerce\Currency;

use Commerce\Cart\Contracts\CartStorageInterface;
use Commerce\Contracts\Currency\CurrencyConverterInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Currency\Contracts\CurrencyServiceInterface;
use Commerce\Currency\Services\CurrencyConverterService;
use Commerce\Currency\Services\CurrencyQueryService;
use Commerce\Currency\Services\CurrencyService;
use Illuminate\Support\Facades\View;

final class CurrencyServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'currency';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/currency.php'), 'currency');

        $this->app->singleton(CurrencyQueryService::class);
        $this->app->singleton(CurrencyConverterService::class);
        $this->app->singleton(CurrencyService::class);

        $this->app->bind(CurrencyConverterInterface::class, CurrencyConverterService::class);
        $this->app->bind(CurrencyServiceInterface::class, CurrencyService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'currency');

        View::composer('cart::layouts.storefront', function ($view): void {
            if (! app()->bound(CurrencyConverterInterface::class)) {
                return;
            }

            $converter = app(CurrencyConverterInterface::class);
            $currencies = $converter->activeCurrencies();
            $display = app()->bound(CartStorageInterface::class)
                ? app(CartStorageInterface::class)->currency()
                : $converter->baseCurrency();
            $meta = collect($currencies)->first(
                fn (object $currency): bool => strtoupper((string) $currency->code) === strtoupper($display),
            );

            $view->with('storeCurrencies', $currencies);
            $view->with('storeBaseCurrency', $converter->baseCurrency());
            $view->with('storeDisplayCurrency', $display);
            $view->with('storefrontMoney', [
                'currency' => $display,
                'symbol' => is_string($meta?->symbol) && $meta->symbol !== '' ? $meta->symbol : $display,
                'decimals' => (int) ($meta?->decimal_places ?? 2),
            ]);
        });
    }
}
