<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use Commerce\Crm\Models\Lead;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CrmLeadServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_qualify_lead(): void
    {
        $this->actingAs(User::query()->first());

        $this->post(route('admin.crm.leads.store'), [
            'name' => 'Jane Prospect',
            'email' => 'jane@example.com',
            'status' => 'new',
        ])->assertRedirect();

        $lead = Lead::query()->first();
        $this->assertNotNull($lead);

        $this->post(route('admin.crm.leads.qualify', $lead))
            ->assertRedirect();

        $this->assertSame('qualified', $lead->fresh()->status);
    }
}
