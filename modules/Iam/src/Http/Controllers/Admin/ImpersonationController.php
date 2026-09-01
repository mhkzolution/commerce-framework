<?php

declare(strict_types=1);

namespace Commerce\Iam\Http\Controllers\Admin;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Contracts\Impersonation\ImpersonationServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ImpersonationController extends Controller
{
    public function __construct(private readonly ImpersonationServiceInterface $impersonation) {}

    public function store(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->impersonation->start(
                $request->user(),
                $user,
                $validated['reason'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['impersonation' => $exception->getMessage()]);
        }

        return redirect()->route('admin.iam.users.index')
            ->with('status', "Now impersonating {$user->email}.");
    }

    public function destroy(): RedirectResponse
    {
        $this->impersonation->stop();

        return redirect()->route('admin.iam.users.index')
            ->with('status', 'Impersonation ended.');
    }
}
