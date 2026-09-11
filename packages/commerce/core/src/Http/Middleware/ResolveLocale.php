<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('admin.locale.available', ['th' => 'ไทย', 'en' => 'English']));
        $sessionKey = (string) config('admin.locale.session_key', 'commerce.locale');
        $cookieName = (string) config('admin.locale.cookie', 'commerce_locale');
        $locale = $request->hasSession() ? $request->session()->get($sessionKey) : null;

        if (! is_string($locale) || ! in_array($locale, $available, true)) {
            $cookieLocale = $request->cookie($cookieName);
            $locale = is_string($cookieLocale) ? $cookieLocale : null;
        }

        if (! is_string($locale) || ! in_array($locale, $available, true)) {
            $locale = (string) config('app.locale', config('admin.locale.default', 'th'));
        }

        if (! in_array($locale, $available, true)) {
            $locale = (string) config('admin.locale.default', config('app.fallback_locale', 'en'));
        }

        if (! in_array($locale, $available, true)) {
            $locale = (string) config('app.fallback_locale', 'en');
        }

        if ($request->hasSession() && $request->session()->get($sessionKey) !== $locale) {
            $request->session()->put($sessionKey, $locale);
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
