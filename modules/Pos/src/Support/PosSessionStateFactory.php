<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

use Illuminate\Contracts\Session\Session;

final class PosSessionStateFactory
{
    public function __construct(
        private readonly Session $session,
    ) {}

    public function make(string $registerUuid): PosSessionState
    {
        return new PosSessionState($this->session, $registerUuid);
    }
}
