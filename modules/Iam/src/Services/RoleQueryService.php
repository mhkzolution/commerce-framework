<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Iam\Models\Role;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class RoleQueryService extends BaseQueryService
{
    /**
     * @return LengthAwarePaginator<int, Role>
     */
    public function paginate(?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return Role::query()
            ->withCount(['users', 'permissions'])
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return list<Role>
     */
    public function allForSelect(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get()
            ->all();
    }
}
