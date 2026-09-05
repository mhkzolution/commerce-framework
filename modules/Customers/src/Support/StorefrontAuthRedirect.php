<?php

declare(strict_types=1);

namespace Commerce\Customers\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class StorefrontAuthRedirect
{
    public static function toIntended(?string $fallback = null): RedirectResponse
    {
        $fallback ??= route('storefront.account');
        $intended = session()->get('url.intended');

        if (is_string($intended) && self::isAllowedStorefrontUrl($intended)) {
            session()->forget('url.intended');

            return redirect()->to($intended);
        }

        session()->forget('url.intended');

        return redirect()->to($fallback);
    }

    public static function sanitizeIntended(?Request $request = null): void
    {
        $intended = session()->get('url.intended');

        if (! is_string($intended) || ! self::isAllowedStorefrontUrl($intended, $request)) {
            session()->forget('url.intended');
        }
    }

    public static function homeForRequest(Request $request): string
    {
        if (self::isStorefrontAccountRequest($request)) {
            return route('storefront.account');
        }

        return '/admin';
    }

    public static function loginForRequest(Request $request): string
    {
        if (self::isStorefrontAccountRequest($request)) {
            return route('storefront.account.login');
        }

        return '/admin/login';
    }

    public static function isStorefrontAccountRequest(Request $request): bool
    {
        if ($request->routeIs('storefront.account', 'storefront.account.*')) {
            return true;
        }

        return $request->is('account', 'account/*');
    }

    public static function isAllowedStorefrontUrl(string $url, ?Request $request = null): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '//') || str_contains($url, '\\')) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) && ! in_array($parts['scheme'], ['http', 'https'], true)) {
            return false;
        }

        $host = $request?->getHost();

        if (isset($parts['host']) && $host !== null && strcasecmp($parts['host'], $host) !== 0) {
            return false;
        }

        $path = $parts['path'] ?? '/';

        if ($path === '/admin' || str_starts_with($path, '/admin/')) {
            return false;
        }

        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return false;
        }

        return true;
    }
}
