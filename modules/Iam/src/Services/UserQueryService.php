<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Iam\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserQueryService extends BaseQueryService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $search = null, ?string $status = null, int $perPage = 25): LengthAwarePaginator
    {
        return User::query()
            ->with(['profile', 'roles'])
            ->when($status, static fn ($query) => $query->where('status', $status))
            ->when($search, static function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage);
    }
}
