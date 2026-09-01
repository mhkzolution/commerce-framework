<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Admin;

final class AdminNavigationLabelResolver
{
    /**
     * @param  array<string, mixed>  $entry
     */
    public function resolve(array $entry): string
    {
        if (! empty($entry['label_key'])) {
            return __((string) $entry['label_key']);
        }

        if (! empty($entry['route'])) {
            $routeKey = 'admin::nav.routes.'.str_replace('.', '_', (string) $entry['route']);
            $translated = __($routeKey);

            if ($translated !== $routeKey) {
                return $translated;
            }
        }

        if (! empty($entry['id'])) {
            foreach (['admin::nav.groups.', 'admin::nav.items.'] as $prefix) {
                $idKey = $prefix.str_replace('-', '_', (string) $entry['id']);
                $translated = __($idKey);

                if ($translated !== $idKey) {
                    return $translated;
                }
            }
        }

        $label = (string) ($entry['label'] ?? 'Untitled');
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '_', $label));
        $slug = trim($slug, '_');
        $slugKey = 'admin::nav.labels.'.$slug;
        $translated = __($slugKey);

        return $translated !== $slugKey ? $translated : $label;
    }
}
