<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Preferences;

use Commerce\Iam\Models\User;

interface UserPreferenceServiceInterface
{
    public function get(User $user, string $key, mixed $default = null): mixed;

    public function set(User $user, string $key, mixed $value): void;

    /**
     * @return array<string, mixed>
     */
    public function all(User $user): array;
}
