<?php

declare(strict_types=1);

namespace Tests\Feature\Features;

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Http\Middleware\EnsureFeatureEnabled;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

final class EnsureFeatureEnabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_enabled_feature_allows_request(): void
    {
        $this->get(route('testing.feature.probe'))
            ->assertOk()
            ->assertSeeText('ok');
    }

    public function test_disabled_feature_returns_404_not_403(): void
    {
        $feature = SystemFeature::query()->where('code', 'ai-writer')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->get(route('testing.feature.probe'))
            ->assertNotFound()
            ->assertStatus(404);
    }

    public function test_disabled_parent_module_returns_404_when_feature_is_enabled(): void
    {
        $cms = SystemModule::query()->where('code', 'cms')->firstOrFail();
        app(ModuleService::class)->updateStatus($cms, ModuleStatus::Disabled);

        $this->assertSame(FeatureStatus::Enabled, $this->aiWriter()->status);
        $this->get(route('testing.feature.probe'))->assertNotFound();
    }

    public function test_hidden_parent_module_allows_enabled_feature(): void
    {
        $cms = SystemModule::query()->where('code', 'cms')->firstOrFail();
        app(ModuleService::class)->updateStatus($cms, ModuleStatus::Hidden);

        $this->assertSame(FeatureStatus::Enabled, $this->aiWriter()->status);
        $this->get(route('testing.feature.probe'))->assertOk();
    }

    public function test_middleware_returns_404_not_500_when_feature_service_throws(): void
    {
        $this->app->bind(EnsureFeatureEnabled::class, static fn () => new class extends EnsureFeatureEnabled
        {
            protected function featureIsEnabled(string $code): bool
            {
                throw new RuntimeException('boom');
            }
        });

        $this->get(route('testing.feature.probe'))
            ->assertNotFound()
            ->assertStatus(404);
    }

    private function aiWriter(): SystemFeature
    {
        return SystemFeature::query()->where('code', 'ai-writer')->firstOrFail();
    }
}
