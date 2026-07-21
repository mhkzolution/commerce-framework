<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Session\SessionServiceInterface;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\DB;

final class SessionService extends BaseService implements SessionServiceInterface
{
    public function listForUser(User $user): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(static fn (object $session): array => [
                'id' => (string) $session->id,
                'ip_address' => $session->ip_address ?? null,
                'user_agent' => $session->user_agent ?? null,
                'last_activity' => (int) $session->last_activity,
            ])
            ->all();
    }

    public function revoke(User $user, string $sessionId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();
    }

    public function revokeOthers(User $user, string $currentSessionId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }
}
