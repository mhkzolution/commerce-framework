<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Iam\Contracts\Token\ApiTokenServiceInterface;
use Commerce\Iam\Contracts\TwoFactor\TwoFactorServiceInterface;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\SessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class SecurityController extends Controller
{
    public function __construct(
        private readonly TwoFactorServiceInterface $twoFactor,
        private readonly ApiTokenServiceInterface $tokens,
        private readonly SessionService $sessions,
    ) {}

    public function show(Request $request): View
    {
        $user = $this->requireUser($request);

        return view('iam::admin.security.index', [
            'user' => $user,
            'twoFactorEnabled' => $this->twoFactor->isEnabled($user),
            'tokens' => $this->tokens->listForUser($user),
            'sessions' => $this->sessions->listForUser($user),
            'setup' => session('two_factor_setup'),
            'plainToken' => session('plain_api_token'),
        ]);
    }

    public function enableTwoFactor(Request $request): RedirectResponse
    {
        $user = $this->requireUser($request);
        $setup = $this->twoFactor->enable($user);

        return redirect()
            ->route('admin.iam.security.show')
            ->with('two_factor_setup', $setup);
    }

    public function confirmTwoFactor(Request $request): RedirectResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->confirm($user->fresh() ?? $user, $validated['code'])) {
            return back()->withErrors(['code' => __('iam::auth.two_factor_invalid')]);
        }

        return redirect()
            ->route('admin.iam.security.show')
            ->with('status', 'Two-factor authentication enabled.');
    }

    public function disableTwoFactor(Request $request): RedirectResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->disable($user, $validated['code'])) {
            return back()->withErrors(['code' => __('iam::auth.two_factor_invalid')]);
        }

        return redirect()
            ->route('admin.iam.security.show')
            ->with('status', 'Two-factor authentication disabled.');
    }

    public function storeToken(Request $request): RedirectResponse
    {
        $user = $this->requireUser($request);
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        $result = $this->tokens->create($user, $validated['name']);

        return redirect()
            ->route('admin.iam.security.show')
            ->with('plain_api_token', $result['plainTextToken'])
            ->with('status', 'API token created. Copy it now — it will not be shown again.');
    }

    public function destroyToken(Request $request, string $tokenUuid): RedirectResponse
    {
        $user = $this->requireUser($request);
        $this->tokens->revoke($user, $tokenUuid);

        return redirect()
            ->route('admin.iam.security.show')
            ->with('status', 'API token revoked.');
    }

    public function destroySession(Request $request, string $sessionId): RedirectResponse
    {
        $user = $this->requireUser($request);
        $this->sessions->revoke($user, $sessionId);

        return redirect()
            ->route('admin.iam.security.show')
            ->with('status', 'Session revoked.');
    }

    private function requireUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
