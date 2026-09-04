<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Pos\Models\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PosModuleGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_disabled_pos_module_returns_404_for_terminal(): void
    {
        Register::query()->create([
            'name' => 'Gate Counter',
            'code' => 'POS-GATE',
            'is_active' => true,
        ]);

        $this->setModuleStatus('pos', ModuleStatus::Disabled);

        $this->actingAs(User::query()->first())
            ->get(route('pos.index'))
            ->assertNotFound();
    }

    public function test_disabled_hold_feature_returns_404(): void
    {
        Register::query()->create([
            'name' => 'Hold Gate',
            'code' => 'POS-HOLD-GATE',
            'is_active' => true,
        ]);

        $this->actingAs(User::query()->first())->post(route('pos.session.open'));

        $feature = SystemFeature::query()->where('code', 'pos-hold')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->actingAs(User::query()->first())
            ->postJson(route('pos.api.hold'))
            ->assertNotFound();
    }

    public function test_disabled_returns_feature_returns_404(): void
    {
        $feature = SystemFeature::query()->where('code', 'pos-returns')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->actingAs(User::query()->first())
            ->get(route('pos.returns.index'))
            ->assertNotFound();
    }

    private function setModuleStatus(string $code, ModuleStatus $status): void
    {
        $module = SystemModule::query()->where('code', $code)->firstOrFail();
        app(ModuleService::class)->updateStatus($module, $status);
    }
}
