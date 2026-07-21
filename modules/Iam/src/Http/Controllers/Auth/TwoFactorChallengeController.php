<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Auth;

use Commerce\Iam\Contracts\Authentication\AuthenticationServiceInterface;
use Commerce\Iam\DTO\LoginCredentialsData;
use Commerce\Iam\DTO\LoginStatus;
use Commerce\Iam\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authentication,
    ) {}

    public function create(): View|RedirectResponse
    {
        if (! session()->has('iam.pending_two_factor_user_id')) {
            return redirect()->route('admin.login');
        }

        return view('iam::auth.two-factor');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        if (! $this->authentication->completeTwoFactorChallenge($request->string('code')->toString())) {
            return back()->withErrors(['code' => __('iam::auth.two_factor_invalid')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.iam.users.index'));
    }
}
