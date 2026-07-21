<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;

final class PosSessionService
{
    public function activeSession(Register $register): ?Session
    {
        return Session::query()
            ->where('register_id', $register->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function openSession(Register $register, string $openedBy): Session
    {
        $existing = $this->activeSession($register);

        if ($existing !== null) {
            return $existing;
        }

        return Session::query()->create([
            'register_id' => $register->id,
            'opened_by' => $openedBy,
            'opened_at' => now(),
            'status' => 'open',
        ]);
    }

    public function closeSession(Session $session): Session
    {
        $session->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return $session->fresh();
    }
}
