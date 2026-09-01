<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Middleware;

use Closure;
use Commerce\Api\Responses\ApiResponse;
use Commerce\Cart\Cart\CartTokenContext;
use Commerce\Cart\Models\CartToken;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveCartToken
{
    public function __construct(private readonly CartTokenContext $cartTokenContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('cart.token_enabled', true)) {
            return $next($request);
        }

        $header = (string) config('cart.token_header', 'X-Cart-Token');
        $identifier = $request->header($header);
        $token = null;

        if (is_string($identifier) && $identifier !== '') {
            $token = CartToken::query()->where('uuid', $identifier)->first();

            if ($token === null || $token->isExpired()) {
                return ApiResponse::error('cart.invalid_token', 'Cart token is invalid or expired.', status: 404);
            }
        } elseif ($this->shouldIssueToken($request)) {
            $token = CartToken::issue();
        }

        if ($token !== null) {
            $this->cartTokenContext->set($token);
        }

        $response = $next($request);

        if ($token !== null) {
            $response->headers->set($header, $token->uuid);
        }

        return $response;
    }

    private function shouldIssueToken(Request $request): bool
    {
        if (! $request->is('api/v1/cart*')) {
            return false;
        }

        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
