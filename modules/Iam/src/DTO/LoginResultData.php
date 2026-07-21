<?php

declare(strict_types=1);

namespace Commerce\Iam\DTO;

use Commerce\Iam\Models\User;

final class LoginResultData
{
    public function __construct(
        public readonly LoginStatus $status,
        public readonly ?User $user = null,
    ) {}
}
