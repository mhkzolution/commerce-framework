<?php

declare(strict_types=1);

namespace Commerce\Core\Policies;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Core\Base\BasePolicy;
use Commerce\Core\Models\SystemModule;

final class SystemModulePolicy extends BasePolicy
{
    public function viewAny(?object $user): bool
    {
        return $this->allows($user, 'system.module.view');
    }

    public function view(?object $user, SystemModule $module): bool
    {
        return $this->allows($user, 'system.module.view');
    }

    public function update(?object $user, SystemModule $module): bool
    {
        return $this->allows($user, 'system.module.update');
    }

    private function allows(?object $user, string $permission): bool
    {
        if (! app()->bound(AuthorizationServiceInterface::class)) {
            return false;
        }

        return app(AuthorizationServiceInterface::class)->can($user, $permission);
    }
}
