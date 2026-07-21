<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Middleware;

use Closure;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Core\Tenant\TenantService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function __construct(
        private readonly TenantContext $context,
        private readonly TenantService $tenants,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();

        if (! $this->context->isEnabled()) {
            return $next($request);
        }

        $tenant = $this->tenants->resolveFromRequest(
            $request->header((string) config('commerce.tenant.header', 'X-Tenant')),
            $request->getHost(),
        );

        if ($tenant !== null) {
            $this->context->set($tenant);
        }

        return $next($request);
    }
}
