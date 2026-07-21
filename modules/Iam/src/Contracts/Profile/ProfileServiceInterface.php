<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Profile;

use Commerce\Iam\Models\User;
use Commerce\Iam\Models\UserProfile;

interface ProfileServiceInterface
{
    public function getOrCreate(User $user): UserProfile;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): UserProfile;
}
