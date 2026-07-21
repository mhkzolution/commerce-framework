<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Auth;

use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class LoginController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
    ) {}

    public function create(): View
    {
        return view('iam::auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = new LoginCredentialsData(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            remember: $request->boolean('remember'),
        );

        if (! $this->authentication->attempt($credentials)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('iam::auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.iam.users.index'));
    }

    public function destroy(): RedirectResponse
    {
        $this->authentication->logout();

        return redirect()->route('admin.login');
    }
}
