<?php

declare(strict_types=1);

namespace Commerce\Contracts\Admin;

interface AdminGlobalSearchServiceInterface
{
    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    public function search(string $query, ?object $user = null, int $limit = 20): array;
}
