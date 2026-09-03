<?php

declare(strict_types=1);

namespace Commerce\Navigation\Services;

use Commerce\Navigation\Models\NavigationMenu;
use Commerce\Navigation\Models\NavigationMenuItem;
use Illuminate\Support\Facades\DB;

final class NavigationMenuService
{
    /**
     * @param  list<array{label: string, url: string, is_visible?: bool, footer_enabled?: bool}>  $items
     */
    public function syncItems(NavigationMenu $menu, string $name, array $items): void
    {
        DB::transaction(function () use ($menu, $name, $items): void {
            $menu->forceFill(['name' => $name])->save();

            $menu->items()->delete();

            foreach (array_values($items) as $position => $item) {
                NavigationMenuItem::query()->create([
                    'menu_id' => $menu->id,
                    'label' => $item['label'],
                    'url' => $item['url'],
                    'position' => $position,
                    'is_visible' => $item['is_visible'] ?? true,
                    'footer_enabled' => $item['footer_enabled'] ?? true,
                ]);
            }
        });
    }
}
