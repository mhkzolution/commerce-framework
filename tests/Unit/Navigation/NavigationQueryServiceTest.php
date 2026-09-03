<?php

declare(strict_types=1);

namespace Tests\Unit\Navigation;

use Commerce\Contracts\Navigation\NavigationLinkData;
use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Navigation\Models\NavigationMenu;
use Commerce\Navigation\Models\NavigationMenuItem;
use Commerce\Navigation\Services\NavigationQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NavigationQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_container_binds_query_contract(): void
    {
        $this->assertTrue($this->app->bound(NavigationQueryServiceInterface::class));
        $this->assertInstanceOf(NavigationQueryService::class, $this->app->make(NavigationQueryServiceInterface::class));
    }

    public function test_seeded_menus_return_empty_link_lists(): void
    {
        $service = $this->app->make(NavigationQueryServiceInterface::class);

        $this->assertSame([], $service->links('main'));
        $this->assertSame([], $service->links('footer'));
        $this->assertSame([], $service->links('account'));
        $this->assertSame([], $service->links('missing'));
        $this->assertSame([], $service->links('MAIN'));
    }

    public function test_links_return_visible_items_as_dtos_in_position_order(): void
    {
        $menu = NavigationMenu::query()->where('handle', 'footer')->firstOrFail();

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Hidden',
            'url' => '/hidden',
            'position' => 0,
            'is_visible' => false,
            'footer_enabled' => true,
        ]);
        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Second',
            'url' => '/second',
            'position' => 2,
            'is_visible' => true,
            'footer_enabled' => false,
        ]);
        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'First',
            'url' => '/first',
            'position' => 1,
            'is_visible' => true,
            'footer_enabled' => true,
        ]);

        $links = $this->app->make(NavigationQueryServiceInterface::class)->links('footer');

        $this->assertCount(2, $links);
        $this->assertContainsOnlyInstancesOf(NavigationLinkData::class, $links);
        $this->assertSame('First', $links[0]->label);
        $this->assertSame('/first', $links[0]->url);
        $this->assertTrue($links[0]->footerEnabled);
        $this->assertSame('Second', $links[1]->label);
        $this->assertFalse($links[1]->footerEnabled);
    }
}
