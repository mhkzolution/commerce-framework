<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Commerce\Contracts\Seo\UrlRedirectServiceInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveUrlRedirect
{
    public function __construct(
        private readonly UrlRedirectServiceInterface $redirects,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $target = $this->redirects->resolve($request->getPathInfo());

        if ($target !== null && $target !== $request->getPathInfo()) {
            return redirect($target, 301);
        }

        return $next($request);
    }
}
