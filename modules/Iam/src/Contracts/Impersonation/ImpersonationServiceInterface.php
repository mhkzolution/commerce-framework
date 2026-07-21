<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Impersonation;

use Commerce\Iam\Models\User;

interface ImpersonationServiceInterface
{
    public function canImpersonate(User $actor, User $target): bool;

    public function start(User $actor, User $target, ?string $reason = null): void;

    public function stop(): void;

    public function isImpersonating(): bool;

    public function originalUser(): ?User;
}
