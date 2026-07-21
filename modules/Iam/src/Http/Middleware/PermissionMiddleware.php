<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Middleware;

use Closure;
use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PermissionMiddleware
{
    public function __construct(
        private readonly AuthorizationServiceInterface $authorization,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $this->authorization->can($user, $permission)) {
            abort(403, 'This action is unauthorized.');
        }

        return $next($request);
    }
}
