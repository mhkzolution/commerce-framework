<?php

declare(strict_types=1);

namespace Commerce\Api\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiVersionMiddleware
{
    public function handle(Request $request, Closure $next, string $version = 'v1'): Response
    {
        $request->attributes->set('api_version', $version);

        return $next($request);
    }
}
