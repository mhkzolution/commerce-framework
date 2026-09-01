<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Commerce\Core\Channel\ChannelContext;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveChannel
{
    public function __construct(private readonly ChannelContext $channelContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $channel = $request->header('X-Channel')
            ?? $request->query('channel')
            ?? $this->resolveFromPath($request)
            ?? config('commerce.channel.default', 'web');

        $this->channelContext->setChannel((string) $channel);

        if ($request->hasHeader('X-Currency')) {
            $this->channelContext->setCurrency($request->header('X-Currency'));
        } elseif ($request->has('currency')) {
            $this->channelContext->setCurrency((string) $request->query('currency'));
        }

        if ($request->hasHeader('X-Locale')) {
            $this->channelContext->setLocale($request->header('X-Locale'));
        }

        return $next($request);
    }

    private function resolveFromPath(Request $request): ?string
    {
        if ($request->is('admin/*') || $request->is('admin')) {
            return 'admin';
        }

        if ($request->is('api/*')) {
            return 'api';
        }

        if ($request->is('admin/pos/*')) {
            return 'pos';
        }

        return null;
    }
}
