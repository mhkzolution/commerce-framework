<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Session;

use Commerce\Iam\Models\User;

interface SessionServiceInterface
{
    /**
     * @return list<array{id: string, ip_address: ?string, user_agent: ?string, last_activity: int}>
     */
    public function listForUser(User $user): array;

    public function revoke(User $user, string $sessionId): void;

    public function revokeOthers(User $user, string $currentSessionId): void;
}
