<?php

declare(strict_types=1);

namespace Commerce\Iam\Contracts\Activity;

interface IamAuditServiceInterface
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(string $action, ?object $subject = null, array $meta = [], ?int $userId = null): void;

    /**
     * @return list<\Commerce\Iam\Models\IamAuditLog>
     */
    public function recent(int $limit = 50, ?int $userId = null): array;
}
