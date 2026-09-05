<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Middleware;

use Closure;
use Commerce\Customers\Support\StorefrontAuthRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SanitizeStorefrontIntendedUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        StorefrontAuthRedirect::sanitizeIntended($request);

        return $next($request);
    }
}
