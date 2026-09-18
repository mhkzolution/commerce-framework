<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Middleware;

use Closure;
use Commerce\Cart\Services\StorefrontAccessResolver;
use Commerce\Contracts\Storefront\StorefrontAccessContext;
use Commerce\Contracts\Storefront\StoreVisibility;
use Commerce\Customers\Support\StorefrontAuthRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStorefrontAccess
{
    public function __construct(
        private readonly StorefrontAccessResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $access = $this->resolver->resolve();

        if ($access->mode === StoreVisibility::Members && $this->isCatalogRoute($request) && ! $access->authenticated) {
            StorefrontAuthRedirect::rememberUrl($request->fullUrl(), $request);

            return redirect()->route('storefront.account.login', [
                'redirect' => $request->fullUrl(),
            ]);
        }

        if ($access->mode === StoreVisibility::Private && ! $this->isAccountArea($request)) {
            if (! $access->authenticated) {
                return $this->restricted($request, $access);
            }

            if (! $access->entitled) {
                return $this->forbidden($access);
            }
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        if ($request->is('admin', 'admin/*', 'api', 'api/*', 'pos', 'pos/*', 'warehouse', 'warehouse/*', 'up', 'livewire/*')) {
            return true;
        }

        return $this->isAuthException($request);
    }

    private function isAuthException(Request $request): bool
    {
        if ($request->routeIs(
            'storefront.account.login',
            'storefront.account.login.store',
            'storefront.account.register',
            'storefront.account.register.store',
            'storefront.account.oauth.*',
        )) {
            return true;
        }

        return $request->is(
            'account/login',
            'account/login/*',
            'account/register',
            'account/register/*',
            'account/oauth',
            'account/oauth/*',
        );
    }

    private function isAccountArea(Request $request): bool
    {
        return $request->routeIs('storefront.account', 'storefront.account.*')
            || $request->is('account', 'account/*');
    }

    private function isCatalogRoute(Request $request): bool
    {
        if ($request->routeIs('storefront.shop.*', 'storefront.products.*', 'storefront.brands.*', 'storefront.suggest')) {
            return true;
        }

        return $request->is(
            'shop',
            'shop/*',
            'products',
            'products/*',
            'brands',
            'brands/*',
            'categories',
            'categories/*',
            'search',
            'search/*',
        );
    }

    private function restricted(Request $request, StorefrontAccessContext $access): Response
    {
        return response()->view('cart::storefront.access-restricted', [
            'storeAccess' => $access,
            'pageSeo' => ['robots' => 'noindex,nofollow'],
            'loginUrl' => route('storefront.account.login', ['redirect' => $request->fullUrl()]),
            'registerUrl' => route('storefront.account.register', ['redirect' => $request->fullUrl()]),
        ], 403);
    }

    private function forbidden(StorefrontAccessContext $access): Response
    {
        return response()->view('cart::storefront.access-forbidden', [
            'storeAccess' => $access,
            'pageSeo' => ['robots' => 'noindex,nofollow'],
        ], 403);
    }
}
