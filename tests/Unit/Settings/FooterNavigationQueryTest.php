<?php

declare(strict_types=1);

namespace Tests\Unit\Settings;

use Commerce\Contracts\Navigation\NavigationLinkData;
use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Settings\Services\FooterNavigationQuery;
use RuntimeException;
use Tests\TestCase;

final class FooterNavigationQueryTest extends TestCase
{
    public function test_links_are_empty_when_navigation_is_unbound(): void
    {
        $this->assertSame([], (new FooterNavigationQuery)->links('main'));
        $this->assertSame([], (new FooterNavigationQuery)->links('footer'));
    }

    public function test_links_map_navigation_dtos_to_driver_arrays(): void
    {
        $query = new FooterNavigationQuery(new class implements NavigationQueryServiceInterface
        {
            public function links(string $source): array
            {
                if ($source !== 'footer') {
                    return [];
                }

                return [
                    new NavigationLinkData('About', '/about', 'about', true),
                ];
            }
        });

        $this->assertSame([], $query->links('main'));
        $this->assertSame([
            [
                'label' => 'About',
                'url' => '/about',
                'key' => 'about',
                'footer_enabled' => true,
            ],
        ], $query->links('footer'));
    }

    public function test_links_fail_soft_when_navigation_throws(): void
    {
        $query = new FooterNavigationQuery(new class implements NavigationQueryServiceInterface
        {
            public function links(string $source): array
            {
                throw new RuntimeException('navigation unavailable');
            }
        });

        $this->assertSame([], $query->links('footer'));
    }
}
