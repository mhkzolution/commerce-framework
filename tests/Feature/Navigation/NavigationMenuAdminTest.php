<?php

declare(strict_types=1);

namespace Tests\Feature\Navigation;

use Commerce\Contracts\Navigation\NavigationQueryServiceInterface;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Navigation\Models\NavigationMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NavigationMenuAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_view_and_update_footer_menu_items(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.navigation.show'))
            ->assertOk()
            ->assertSee('main', false)
            ->assertSee('footer', false);

        $this->actingAs(User::query()->first())
            ->get(route('admin.navigation.menus.edit', 'footer'))
            ->assertOk()
            ->assertSee('Footer', false);

        $this->actingAs(User::query()->first())
            ->put(route('admin.navigation.menus.update', 'footer'), [
                'name' => 'Footer',
                'items' => [
                    ['label' => 'About', 'url' => '/about', 'is_visible' => '1', 'footer_enabled' => '1'],
                    ['label' => '', 'url' => '/skip', 'is_visible' => '1', 'footer_enabled' => '1'],
                ],
            ])
            ->assertRedirect(route('admin.navigation.menus.edit', 'footer'));

        $menu = NavigationMenu::query()->where('handle', 'footer')->with('items')->firstOrFail();
        $this->assertCount(1, $menu->items);
        $this->assertSame('About', $menu->items->first()?->label);
        $this->assertSame('/about', $menu->items->first()?->url);

        $links = app(NavigationQueryServiceInterface::class)->links('footer');
        $this->assertCount(1, $links);
        $this->assertSame('About', $links[0]->label);
        $this->assertSame('/about', $links[0]->url);
    }

    public function test_user_without_permission_cannot_view_navigation_admin(): void
    {
        $role = Role::query()->create([
            'name' => 'No Navigation',
            'code' => 'no-navigation',
            'is_system' => false,
        ]);

        $user = app(UserServiceInterface::class)->create(new CreateUserData(
            name: 'No Nav',
            email: 'no-nav@example.test',
            password: 'password',
            roleCodes: [$role->code],
        ));

        $this->actingAs($user)
            ->get(route('admin.navigation.show'))
            ->assertForbidden();
    }
}
