<?php

declare(strict_types=1);

namespace Tests\Feature\WarehouseScanner;

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Contracts\User\UserServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\DTO\CreateUserData;
use Commerce\Iam\Models\Role;
use Commerce\Iam\Models\User;
use Commerce\Iam\Services\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WarehouseScannerModuleGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_disabled_warehouse_module_returns_404(): void
    {
        $this->setModuleStatus('warehouse', ModuleStatus::Disabled);

        $this->actingAs(User::query()->first())
            ->get(route('warehouse.index'))
            ->assertNotFound();
    }

    public function test_disabled_reports_feature_returns_404_for_dashboard(): void
    {
        $feature = SystemFeature::query()->where('code', 'warehouse-reports')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->actingAs(User::query()->first())
            ->get(route('warehouse.dashboard'))
            ->assertNotFound();
    }

    public function test_guest_is_redirected_from_scanner(): void
    {
        $this->get(route('warehouse.index'))->assertRedirect();
    }

    public function test_user_without_scan_permission_cannot_open_scanner(): void
    {
        $role = Role::query()->create([
            'name' => 'warehouse-none',
            'code' => 'warehouse-none',
            'is_system' => false,
        ]);

        $user = app(UserServiceInterface::class)->create(new CreateUserData(
            name: 'Warehouse None',
            email: 'warehouse-none@example.test',
            password: 'password',
            roleCodes: [$role->code],
        ));

        app(AuthorizationService::class)->clearCacheForUser($user->id);

        $this->actingAs($user)
            ->get(route('warehouse.index'))
            ->assertForbidden();
    }

    private function setModuleStatus(string $code, ModuleStatus $status): void
    {
        $module = SystemModule::query()->where('code', $code)->firstOrFail();
        app(ModuleService::class)->updateStatus($module, $status);
    }
}
