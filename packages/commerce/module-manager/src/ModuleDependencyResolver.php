<?php

declare(strict_types=1);

namespace Commerce\ModuleManager;

use Commerce\Core\Exceptions\DomainException;

final class ModuleDependencyResolver
{
    /**
     * @param  array<string, array<string, mixed>>  $modules
     * @return list<array<string, mixed>>
     */
    public function resolve(array $modules): array
    {
        $sorted = [];
        $visited = [];

        $visit = function (string $alias) use (&$visit, &$sorted, &$visited, $modules): void {
            if (isset($visited[$alias])) {
                return;
            }

            $visited[$alias] = true;
            $manifest = $modules[$alias] ?? null;

            if ($manifest === null) {
                throw new DomainException("Module [{$alias}] is not registered.");
            }

            $deps = $manifest['dependencies']['hard'] ?? $manifest['dependencies'] ?? [];

            if (is_array($deps)) {
                foreach ($deps as $dep) {
                    if (is_string($dep)) {
                        $visit($dep);
                    }
                }
            }

            $sorted[$alias] = $manifest;
        };

        foreach (array_keys($modules) as $alias) {
            $visit($alias);
        }

        uasort($sorted, static fn (array $a, array $b): int => ($a['priority'] ?? 100) <=> ($b['priority'] ?? 100));

        return array_values($sorted);
    }
}
