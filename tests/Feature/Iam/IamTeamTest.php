<?php

declare(strict_types=1);

namespace Tests\Feature\Iam;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\Team;
use Commerce\Iam\Models\User;
use Commerce\Iam\Team\TeamContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class IamTeamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        config(['iam.teams.enabled' => true]);
    }

    public function test_admin_can_create_team_and_add_member(): void
    {
        $user = User::query()->first();
        $this->assertNotNull($user);

        $this->actingAs($user)
            ->post(route('admin.iam.teams.store'), [
                'name' => 'Sales',
                'slug' => 'sales',
                'status' => 'active',
            ])
            ->assertRedirect();

        $team = Team::query()->first();
        $this->assertNotNull($team);

        $this->actingAs($user)
            ->post(route('admin.iam.teams.members.store', $team), [
                'user_id' => $user->id,
                'role' => 'admin',
            ])
            ->assertRedirect();

        $this->assertTrue($team->fresh()->users->contains('id', $user->id));
    }

    public function test_team_context_resolves_from_header(): void
    {
        $team = Team::query()->create([
            'name' => 'Ops',
            'slug' => 'ops',
            'status' => 'active',
        ]);

        $this->get('/admin/login', ['X-Team' => 'ops'])->assertOk();

        $this->assertSame($team->uuid, app(TeamContext::class)->uuid());
    }
}
