<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Commerce\Core\Channel\ChannelContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveLocale
{
    public function __construct(private readonly ChannelContext $channelContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $available = array_keys(config('admin.locale.available', ['th' => 'ไทย', 'en' => 'English']));
        $sessionKey = (string) config('admin.locale.session_key', 'commerce.locale');
        $locale = $request->hasSession() ? $request->session()->get($sessionKey) : null;

        if (! is_string($locale) || ! in_array($locale, $available, true)) {
            $locale = (string) config('admin.locale.default', config('app.locale', 'th'));
        }

        if (! in_array($locale, $available, true)) {
            $locale = (string) config('app.fallback_locale', 'en');
        }

        app()->setLocale($locale);
        $this->channelContext->setLocale($locale);

        return $next($request);
    }
}
