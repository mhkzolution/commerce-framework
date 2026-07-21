<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Preferences\UserPreferenceServiceInterface;
use Commerce\Iam\Models\User;

final class UserPreferenceService extends BaseService implements UserPreferenceServiceInterface
{
    public function get(User $user, string $key, mixed $default = null): mixed
    {
        $preferences = $this->all($user);

        return $preferences[$key] ?? $default;
    }

    public function set(User $user, string $key, mixed $value): void
    {
        $preferences = $this->all($user);
        $preferences[$key] = $value;

        $meta = $user->meta ?? [];
        $meta['preferences'] = $preferences;
        $user->forceFill(['meta' => $meta])->save();
    }

    public function all(User $user): array
    {
        $preferences = $user->meta['preferences'] ?? [];

        return is_array($preferences) ? $preferences : [];
    }
}
