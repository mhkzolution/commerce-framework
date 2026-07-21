<?php

declare(strict_types=1);

namespace Commerce\Contracts\Admin;

interface AdminWidgetRegistryInterface
{
    /**
     * @param  array{
     *     id: string,
     *     label: string,
     *     view: string,
     *     permission?: string|null,
     *     order?: int,
     *     columns?: int
     * }  $widget
     */
    public function register(array $widget): void;

    /**
     * @return list<array<string, mixed>>
     */
    public function widgets(?object $user = null): array;
}
