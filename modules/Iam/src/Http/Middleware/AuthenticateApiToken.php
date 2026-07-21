<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Middleware;

use Closure;
use Commerce\Iam\Contracts\Token\ApiTokenServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiToken
{
    public function __construct(
        private readonly ApiTokenServiceInterface $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->bearerToken();

        if ($header === null) {
            if (! Auth::check()) {
                return response()->json([
                    'error' => [
                        'code' => 'auth.unauthenticated',
                        'message' => 'Authentication required.',
                        'details' => [],
                    ],
                ], 401);
            }

            return $next($request);
        }

        $user = $this->tokens->validate($header);

        if (! $user instanceof User) {
            return response()->json([
                'error' => [
                    'code' => 'auth.invalid_token',
                    'message' => 'Invalid or expired API token.',
                    'details' => [],
                ],
            ], 401);
        }

        Auth::setUser($user);

        return $next($request);
    }
}
