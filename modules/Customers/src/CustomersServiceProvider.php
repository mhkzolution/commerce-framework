<?php

declare(strict_types=1);

namespace Commerce\Customers;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Customers\Contracts\CustomerAddressServiceInterface;
use Commerce\Customers\Contracts\CustomerAuthServiceInterface;
use Commerce\Customers\Contracts\CustomerServiceInterface;
use Commerce\Customers\Contracts\SmsSenderInterface;
use Commerce\Customers\Services\CustomerAddressQueryService;
use Commerce\Customers\Services\CustomerAddressService;
use Commerce\Customers\Services\CustomerAuthService;
use Commerce\Customers\Services\CustomerOtpService;
use Commerce\Customers\Services\CustomerPasswordResetService;
use Commerce\Customers\Services\CustomerQueryService;
use Commerce\Customers\Services\CustomerService;
use Commerce\Customers\Services\LineOAuthService;
use Commerce\Customers\Services\RecaptchaVerifier;
use Commerce\Customers\Services\Sms\HttpSmsSender;
use Commerce\Customers\Services\Sms\LogSmsSender;
use Commerce\Customers\Services\StorefrontAuthConfigService;

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
        $this->app->singleton(CustomerAuthService::class);
        $this->app->singleton(CustomerPasswordResetService::class);
        $this->app->singleton(CustomerOtpService::class);
        $this->app->singleton(RecaptchaVerifier::class);
        $this->app->singleton(LineOAuthService::class);
        $this->app->singleton(StorefrontAuthConfigService::class);

        $this->app->bind(SmsSenderInterface::class, function (): SmsSenderInterface {
            return config('customers.storefront.sms.driver') === 'http'
                ? app(HttpSmsSender::class)
                : app(LogSmsSender::class);
        });

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
    }
}
