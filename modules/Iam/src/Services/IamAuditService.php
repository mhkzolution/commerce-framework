<?php

declare(strict_types=1);

namespace Commerce\Iam\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Iam\Contracts\Activity\IamAuditServiceInterface;
use Commerce\Iam\Models\IamAuditLog;
use Commerce\Iam\Models\User;
use Illuminate\Support\Facades\Auth;

final class IamAuditService extends BaseService implements IamAuditServiceInterface
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function log(string $action, ?object $subject = null, array $meta = [], ?int $userId = null): void
    {
        $user = Auth::user();
        $resolvedUserId = $userId ?? ($user instanceof User ? $user->id : null);

        IamAuditLog::query()->create([
            'tenant_id' => $this->tenantContext->id(),
            'user_id' => $resolvedUserId,
            'action' => $action,
            'subject_type' => $subject !== null ? $subject::class : null,
            'subject_id' => $this->resolveSubjectId($subject),
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function recent(int $limit = 50, ?int $userId = null): array
    {
        $query = IamAuditLog::query()->orderByDesc('created_at')->limit($limit);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->get()->all();
    }

    private function resolveSubjectId(?object $subject): ?int
    {
        if ($subject === null) {
            return null;
        }

        if (isset($subject->id) && is_numeric($subject->id)) {
            return (int) $subject->id;
        }

        return null;
    }
}
