<?php

declare(strict_types=1);

namespace Commerce\Customers;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerAuthServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\Http\Middleware\SanitizeStorefrontIntendedUrl;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Services\CustomerAddressService;
use Commerce\Customers\Services\CustomerAuthService;
use Commerce\Customers\Services\CustomerQueryService;
use Commerce\Customers\Services\CustomerService;
use Commerce\Customers\Services\ThailandLocationService;
use Illuminate\Routing\Router;

final class CustomersServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'customers';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/customers.php'), 'customers');

        $this->app->singleton(CustomerQueryService::class);
        $this->app->singleton(CustomerService::class);
        $this->app->singleton(CustomerAddressQueryService::class);
        $this->app->singleton(CustomerAddressService::class);
        $this->app->singleton(ThailandLocationService::class);
        $this->app->singleton(CustomerAuthService::class);

        $this->app->bind(CustomerQueryServiceInterface::class, CustomerQueryService::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(CustomerAddressServiceInterface::class, CustomerAddressService::class);
        $this->app->bind(CustomerAuthServiceInterface::class, CustomerAuthService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'customers');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'customers');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('storefront.intended', SanitizeStorefrontIntendedUrl::class);
    }
}
