<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Controllers\Storefront;

use Commerce\Customers\Contracts\CustomerAuthServiceInterface;
use Commerce\Customers\Services\LineOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

final class OAuthController extends Controller
{
    public function redirect(string $provider, LineOAuthService $lineOAuth): RedirectResponse
    {
        if ($provider !== 'line' || ! $lineOAuth->isConfigured()) {
            return redirect()
                ->route('storefront.account.login')
                ->withErrors(['email' => __('customers::auth.oauth_unavailable', ['provider' => ucfirst($provider)])]);
        }

        $state = Str::random(40);
        session([
            'customer.oauth.state' => $state,
            'customer.oauth.provider' => $provider,
        ]);

        return redirect()->away($lineOAuth->authorizationUrl($state));
    }

    public function callback(
        string $provider,
        Request $request,
        LineOAuthService $lineOAuth,
        CustomerAuthServiceInterface $authService,
    ): RedirectResponse {
        if ($provider !== 'line' || ! $lineOAuth->isConfigured()) {
            return redirect()
                ->route('storefront.account.login')
                ->withErrors(['email' => __('customers::auth.oauth_unavailable', ['provider' => ucfirst($provider)])]);
        }

        $expectedState = session('customer.oauth.state');
        $state = (string) $request->query('state', '');

        if (! is_string($expectedState) || $expectedState === '' || ! hash_equals($expectedState, $state)) {
            return redirect()
                ->route('storefront.account.login')
                ->withErrors(['email' => __('customers::auth.oauth_state_invalid')]);
        }

        session()->forget(['customer.oauth.state', 'customer.oauth.provider']);

        $error = $request->query('error');
        if (is_string($error) && $error !== '') {
            return redirect()
                ->route('storefront.account.login')
                ->withErrors(['email' => __('customers::auth.oauth_cancelled')]);
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return redirect()
                ->route('storefront.account.login')
                ->withErrors(['email' => __('customers::auth.oauth_failed')]);
        }

        try {
            $lineUser = $lineOAuth->exchangeCode($code);
            $authService->loginWithLine($lineUser);
        } catch (\Throwable) {
            return redirect()
                ->route('storefront.account.login')
                ->withErrors(['email' => __('customers::auth.oauth_failed')]);
        }

        return redirect()->intended(route('storefront.account'));
    }
}
