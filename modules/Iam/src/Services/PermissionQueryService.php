<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Iam\Models\Permission;
use Illuminate\Support\Collection;

final class PermissionQueryService
{
    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public function groupedByModule(): Collection
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('module');
    }

    /**
     * @return list<Permission>
     */
    public function all(): array
    {
        return Permission::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->all();
    }
}
