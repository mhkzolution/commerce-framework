<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Auth;

use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\Contracts\OAuth\OAuthServiceInterface;
use Commerce\Iam\Contracts\Token\ApiTokenServiceInterface;
use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\DTO\LoginStatus;
use Commerce\Iam\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
        private readonly OAuthServiceInterface $oauth,
    ) {}

    public function create(): View
    {
        return view('iam::auth.login', [
            'oauthProviders' => $this->oauth->enabledProviders(),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = new LoginCredentialsData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
        );

        $result = $this->authentication->attempt($credentials);

        if ($result->status === LoginStatus::Failed) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('iam::auth.failed')]);
        }

        if ($result->status === LoginStatus::TwoFactorRequired) {
            return redirect()->route('admin.login.two-factor');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.iam.users.index'));
    }

    public function destroy(): RedirectResponse
    {
        $this->authentication->logout();

        return redirect()->route('admin.login');
    }

    public function oauthRedirect(string $provider): RedirectResponse
    {
        $redirectUri = route('admin.login.oauth.callback', ['provider' => $provider]);
        $state = csrf_token();

        session(['iam.oauth.state' => $state]);

        return redirect()->away($this->oauth->getAuthorizationUrl($provider, $redirectUri, $state));
    }

    public function oauthCallback(string $provider): RedirectResponse
    {
        $code = request()->string('code')->toString();
        $state = request()->string('state')->toString();

        if ($state === '' || $state !== session()->pull('iam.oauth.state')) {
            return redirect()->route('admin.login')->withErrors(['email' => 'OAuth state mismatch.']);
        }

        $result = $this->oauth->handleCallback($provider, $code, route('admin.login.oauth.callback', ['provider' => $provider]));
        Auth::login($result['user']);
        request()->session()->regenerate();

        return redirect()->intended(route('admin.iam.users.index'));
    }
}
