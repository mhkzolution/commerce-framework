<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Iam\Contracts\Profile\ProfileServiceInterface;
use Commerce\Iam\Models\User;
use Commerce\Iam\Models\UserProfile;

final class ProfileService extends BaseService implements ProfileServiceInterface
{
    public function getOrCreate(User $user): UserProfile
    {
        return $user->profile()->firstOrCreate([]);
    }

    public function update(User $user, array $data): UserProfile
    {
        $profile = $this->getOrCreate($user);
        $profile->fill($data)->save();

        if (isset($data['name'])) {
            $user->forceFill(['name' => (string) $data['name']])->save();
        }

        return $profile->fresh() ?? $profile;
    }
}
