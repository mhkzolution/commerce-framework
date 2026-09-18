<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Middleware;

use Closure;
use Commerce\Cart\Services\StorefrontAccessResolver;
use Commerce\Customers\Support\StorefrontAuthRedirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureStorefrontCanPurchase
{
    public function __construct(
        private readonly StorefrontAccessResolver $resolver,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $access = $this->resolver->resolve();

        if ($access->canPurchase) {
            return $next($request);
        }

        $redirect = $request->headers->get('referer') ?: $request->fullUrl();
        if (! is_string($redirect) || $redirect === '') {
            $redirect = route('storefront.shop.index');
        }

        StorefrontAuthRedirect::rememberUrl($redirect, $request);
        $loginUrl = route('storefront.account.login', ['redirect' => $redirect]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => __('storefront::storefront.login_to_see_price'),
                'login_url' => $loginUrl,
            ], 403);
        }

        return redirect()->to($loginUrl);
    }
}
