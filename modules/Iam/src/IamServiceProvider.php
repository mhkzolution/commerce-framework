<?php

declare(strict_types=1);

namespace Commerce\Iam;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Contracts\Authorization\PermissionRegistryInterface;
use Commerce\Core\Base\BaseModuleServiceProvider;
use Commerce\Core\Events\SystemModuleStatusChanged;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;
use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\Contracts\Impersonation\ImpersonationServiceInterface;
use Commerce\Iam\Contracts\OAuth\OAuthServiceInterface;
use Commerce\Iam\Contracts\Preferences\UserPreferenceServiceInterface;
use Commerce\Iam\Contracts\Profile\ProfileServiceInterface;
use Commerce\Iam\Contracts\Role\RoleServiceInterface;
use Commerce\Iam\Contracts\Security\PasswordResetServiceInterface;
use Commerce\Iam\Contracts\Session\SessionServiceInterface;
use Commerce\Iam\Contracts\Token\ApiTokenServiceInterface;
use Commerce\Iam\Contracts\TwoFactor\TwoFactorServiceInterface;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Http\Middleware\AuthenticateApiToken;
use Commerce\Iam\Http\Middleware\PermissionMiddleware;
use Commerce\Iam\Listeners\LogSystemModuleStatusChanged;
use Commerce\Iam\OAuth\GitHubOAuthProvider;
use Commerce\Iam\OAuth\GoogleOAuthProvider;
use Commerce\Iam\Services\ApiTokenService;
use Commerce\Iam\Services\AuthenticationService;
use Commerce\Iam\Services\AuthorizationService;
use Commerce\Iam\Services\IamAuditService;
use Commerce\Iam\Services\ImpersonationService;
use Commerce\Iam\Services\OAuthService;
use Commerce\Iam\Services\PasswordResetService;
use Commerce\Iam\Services\PermissionRegistryService;
use Commerce\Iam\Services\ProfileService;
use Commerce\Iam\Services\RoleService;
use Commerce\Iam\Services\SessionService;
use Commerce\Iam\Services\TwoFactorService;
use Commerce\Iam\Services\UserPreferenceService;
use Commerce\Iam\Services\UserService;
use Commerce\Iam\Support\TotpGenerator;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;

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
        $this->app->singleton(TotpGenerator::class);

        $this->app->bind(PermissionRegistryInterface::class, PermissionRegistryService::class);
        $this->app->bind(AuthorizationServiceInterface::class, AuthorizationService::class);
        $this->app->bind(AuthenticationServiceInterface::class, AuthenticationService::class);
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(ApiTokenServiceInterface::class, ApiTokenService::class);
        $this->app->bind(TwoFactorServiceInterface::class, TwoFactorService::class);
        $this->app->bind(OAuthServiceInterface::class, OAuthService::class);
        $this->app->bind(PasswordResetServiceInterface::class, PasswordResetService::class);
        $this->app->bind(SessionServiceInterface::class, SessionService::class);
        $this->app->bind(ProfileServiceInterface::class, ProfileService::class);
        $this->app->bind(IamAuditServiceInterface::class, IamAuditService::class);
        $this->app->bind(ImpersonationServiceInterface::class, ImpersonationService::class);
        $this->app->bind(UserPreferenceServiceInterface::class, UserPreferenceService::class);

        $this->app->singleton(GoogleOAuthProvider::class);
        $this->app->singleton(GitHubOAuthProvider::class);
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
        $router->aliasMiddleware('api.token', AuthenticateApiToken::class);

        Event::listen(SystemModuleStatusChanged::class, LogSystemModuleStatusChanged::class);
    }
}
