<?php

declare(strict_types=1);

namespace Commerce\Navigation\Services;

use Commerce\Contracts\Navigation\NavigationLinkData;
use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Navigation\Models\NavigationMenu;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class NavigationQueryService implements NavigationQueryServiceInterface
{
    /**
     * @return list<NavigationLinkData>
     */
    public function links(string $source): array
    {
        try {
            if (! preg_match('/^[a-z][a-z0-9-]*$/', $source)) {
                return [];
            }

            if (! Schema::hasTable('navigation_menus') || ! Schema::hasTable('navigation_menu_items')) {
                return [];
            }

            $menu = NavigationMenu::query()
                ->where('handle', $source)
                ->with(['items' => static fn ($query) => $query->where('is_visible', true)->orderBy('position')])
                ->first();

            if ($menu === null) {
                return [];
            }

            $links = [];

            foreach ($menu->items as $item) {
                $label = trim((string) $item->label);
                $url = trim((string) $item->url);

                if ($label === '' || $url === '') {
                    continue;
                }

                $links[] = new NavigationLinkData(
                    label: $label,
                    url: $url,
                    key: (string) $item->uuid,
                    footerEnabled: (bool) $item->footer_enabled,
                );
            }

            return $links;
        } catch (Throwable) {
            return [];
        }
    }
}
