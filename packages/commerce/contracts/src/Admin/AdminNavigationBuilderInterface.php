<?php

declare(strict_types=1);

namespace Commerce\Contracts\Admin;

interface AdminNavigationBuilderInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function build(?object $user = null): array;

    /**
     * Flat searchable command palette entries.
     *
     * @return list<array{label: string, route: ?string, url: ?string, group: ?string, keywords: string}>
     */
    public function searchableItems(?object $user = null): array;
}
