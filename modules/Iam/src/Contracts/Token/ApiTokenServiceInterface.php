<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Token;

use Commerce\Iam\Models\ApiToken;
use Commerce\Iam\Models\User;

interface ApiTokenServiceInterface
{
    /**
     * @param  list<string>  $abilities
     * @return array{token: ApiToken, plainTextToken: string}
     */
    public function create(User $user, string $name, array $abilities = ['*'], ?\DateTimeInterface $expiresAt = null): array;

    public function validate(string $plainTextToken): ?User;

    /**
     * @return list<ApiToken>
     */
    public function listForUser(User $user): array;

    public function revoke(User $user, string $tokenUuid): void;

    public function revokeAll(User $user): void;
}
