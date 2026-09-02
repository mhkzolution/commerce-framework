<?php

declare(strict_types=1);

namespace Commerce\Core\Policies;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Core\Base\BasePolicy;
use Commerce\Core\Models\SystemFeature;

final class SystemFeaturePolicy extends BasePolicy
{
    public function viewAny(?object $user): bool
    {
        return $this->allows($user, 'system.feature.view');
    }

    public function view(?object $user, SystemFeature $feature): bool
    {
        return $this->allows($user, 'system.feature.view');
    }

    public function update(?object $user, SystemFeature $feature): bool
    {
        return $this->allows($user, 'system.feature.update');
    }

    private function allows(?object $user, string $permission): bool
    {
        if (! app()->bound(AuthorizationServiceInterface::class)) {
            return false;
        }

        return app(AuthorizationServiceInterface::class)->can($user, $permission);
    }
}
