<?php

declare(strict_types=1);

namespace Tests\Unit\Cart;

use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Cart\DTO\ShopCategoryStripData;
use PHPUnit\Framework\TestCase;

final class ShopCategoryStripDataTest extends TestCase
{
    public function test_unfiltered_shop_shows_parents(): void
    {
        $tree = $this->tree();

        $strip = ShopCategoryStripData::for(null, $tree);

        $this->assertFalse($strip->showBack);
        $this->assertSame(['apparel', 'toys'], array_map(
            static fn (HomepageNavigationData $item): string => $item->slug,
            $strip->items,
        ));
    }

    public function test_selected_parent_shows_children_and_back(): void
    {
        $strip = ShopCategoryStripData::for('apparel', $this->tree());

        $this->assertTrue($strip->showBack);
        $this->assertSame(['tops', 'bottoms'], array_map(
            static fn (HomepageNavigationData $item): string => $item->slug,
            $strip->items,
        ));
    }

    public function test_selected_child_shows_siblings_and_back(): void
    {
        $strip = ShopCategoryStripData::for('tops', $this->tree());

        $this->assertTrue($strip->showBack);
        $this->assertSame(['tops', 'bottoms'], array_map(
            static fn (HomepageNavigationData $item): string => $item->slug,
            $strip->items,
        ));
    }

    public function test_unknown_slug_falls_back_to_parents(): void
    {
        $strip = ShopCategoryStripData::for('missing', $this->tree());

        $this->assertFalse($strip->showBack);
        $this->assertSame(['apparel', 'toys'], array_map(
            static fn (HomepageNavigationData $item): string => $item->slug,
            $strip->items,
        ));
    }

    /**
     * @return list<HomepageNavigationData>
     */
    private function tree(): array
    {
        return [
            new HomepageNavigationData(
                uuid: 'apparel',
                name: 'Apparel',
                slug: 'apparel',
                children: [
                    new HomepageNavigationData(uuid: 'tops', name: 'Tops', slug: 'tops'),
                    new HomepageNavigationData(uuid: 'bottoms', name: 'Bottoms', slug: 'bottoms'),
                ],
            ),
            new HomepageNavigationData(
                uuid: 'toys',
                name: 'Toys',
                slug: 'toys',
            ),
        ];
    }
}
