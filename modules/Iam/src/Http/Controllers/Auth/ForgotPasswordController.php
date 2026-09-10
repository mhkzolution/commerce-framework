<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Auth;

use Commerce\Iam\Contracts\Security\PasswordResetServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordResetServiceInterface $passwordReset,
    ) {}

    public function create(): View
    {
        return view('iam::auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $this->passwordReset->sendResetLink($validated['email']);

        return back()->with('status', __('iam::auth.forgot_password_sent'));
    }
}
