<?php

declare(strict_types=1);

namespace Commerce\Pos\Support;

use Illuminate\Contracts\Session\Session;

final class PosCartStorageFactory
{
    public function __construct(
        private readonly Session $session,
    ) {}

    public function make(string $registerUuid): PosCartStorage
    {
        return new PosCartStorage($this->session, $registerUuid);
    }
}
