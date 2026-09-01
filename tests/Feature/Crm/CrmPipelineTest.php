<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use Commerce\Crm\Models\Deal;
use Commerce\Crm\Models\Lead;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CrmPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_convert_qualified_lead_to_deal(): void
    {
        $lead = Lead::query()->create([
            'name' => 'Acme Corp',
            'email' => 'buyer@acme.com',
            'status' => 'qualified',
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.crm.leads.convert', $lead), [
                'title' => 'Acme annual contract',
                'amount' => 500000,
            ])
            ->assertRedirect();

        $deal = Deal::query()->first();
        $this->assertNotNull($deal);
        $this->assertSame('Acme annual contract', $deal->title);
        $this->assertSame('prospecting', $deal->stage);
        $this->assertSame($lead->id, $deal->lead_id);
    }

    public function test_admin_can_view_pipeline_board_and_move_stage(): void
    {
        Deal::query()->create([
            'title' => 'Big deal',
            'amount' => 10000,
            'stage' => 'prospecting',
            'status' => 'open',
        ]);

        $deal = Deal::query()->first();
        $this->assertNotNull($deal);

        $this->actingAs(User::query()->first())
            ->get(route('admin.crm.deals.board'))
            ->assertOk()
            ->assertSee('Big deal');

        $this->actingAs(User::query()->first())
            ->patch(route('admin.crm.deals.stage', $deal), ['stage' => 'proposal'])
            ->assertRedirect(route('admin.crm.deals.board'));

        $this->assertSame('proposal', $deal->fresh()->stage);
    }
}
