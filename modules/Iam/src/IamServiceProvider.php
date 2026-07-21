<?php

declare(strict_types=1);

namespace Commerce\Iam;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Contracts\Authorization\PermissionRegistryInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\Contracts\Role\RoleServiceInterface;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Http\Middleware\PermissionMiddleware;
use Commerce\Iam\Services\AuthenticationService;
use Commerce\Iam\Services\AuthorizationService;
use Commerce\Iam\Services\PermissionRegistryService;
use Commerce\Iam\Services\RoleService;
use Commerce\Iam\Services\UserService;
use Illuminate\Routing\Router;

final class IamServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string
    {
        return 'iam';
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->modulePath('config/iam.php'), 'iam');

        $this->app->singleton(PermissionRegistryService::class);
        $this->app->singleton(AuthorizationService::class);

        $this->app->bind(PermissionRegistryInterface::class, PermissionRegistryService::class);
        $this->app->bind(AuthorizationServiceInterface::class, AuthorizationService::class);
        $this->app->bind(AuthenticationServiceInterface::class, AuthenticationService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));
        $this->loadRoutesFrom($this->modulePath('routes/web.php'));
        $this->loadRoutesFrom($this->modulePath('routes/api.php'));
        $this->loadViewsFrom($this->modulePath('resources/views'), 'iam');
        $this->loadTranslationsFrom($this->modulePath('resources/lang'), 'iam');

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
    }
}
