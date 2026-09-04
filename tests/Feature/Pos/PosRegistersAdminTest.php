<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Permission;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Commerce\Pos\Models\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PosRegistersAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_registers_index_links_to_pos_terminal_for_the_machine(): void
    {
        $admin = User::query()->first();
        $register = Register::query()->create([
            'name' => 'Front Counter',
            'code' => 'REG-OPEN',
            'location' => 'Store A',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pos.registers.index'))
            ->assertOk()
            ->assertSee('Open POS', false)
            ->assertSee(route('pos.index', ['register' => $register->uuid]), false);
    }

    public function test_pos_index_honors_register_query_and_shows_switcher(): void
    {
        $admin = User::query()->first();
        $first = Register::query()->create([
            'name' => 'Counter A',
            'code' => 'REG-A',
            'is_active' => true,
        ]);
        $second = Register::query()->create([
            'name' => 'Counter B',
            'code' => 'REG-B',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('pos.index', ['register' => $second->uuid]))
            ->assertOk()
            ->assertSee('Counter B', false)
            ->assertSee('เปลี่ยนเครื่อง', false)
            ->assertSee($first->code, false);

        $this->assertSame($second->uuid, session('commerce.pos.register_uuid'));

        $this->actingAs($admin)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Counter B', false);
    }

    public function test_user_without_pos_permission_cannot_open_terminal(): void
    {
        Register::query()->create([
            'name' => 'Locked',
            'code' => 'REG-LOCK',
            'is_active' => true,
        ]);

        $this->actingAs($this->userWithPermissions('pos-none@example.test', []))
            ->get(route('pos.index'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_pos(): void
    {
        $this->get(route('pos.index'))->assertRedirect();
    }

    /**
     * @param  list<string>  $permissionNames
     */
    private function userWithPermissions(string $email, array $permissionNames): User
    {
        $role = Role::query()->create([
            'name' => $email,
            'code' => str_replace(['@', '.'], '-', $email),
            'is_system' => false,
        ]);

        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissionNames)->pluck('id'),
        );

        $user = app(UserServiceInterface::class)->create(new CreateUserData(
            name: $email,
            email: $email,
            password: 'password',
            roleCodes: [$role->code],
        ));

        app(AuthorizationService::class)->clearCacheForUser($user->id);

        return $user;
    }
}
