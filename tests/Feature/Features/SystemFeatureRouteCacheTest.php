<?php

declare(strict_types=1);

namespace Tests\Feature\Features;

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Http\Middleware\EnsureFeatureEnabled;
use Commerce\Core\Models\SystemFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class SystemFeatureRouteCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if ($this->app->routesAreCached()) {
            Artisan::call('route:clear');
        }

        parent::tearDown();
    }

    public function test_feature_middleware_alias_is_registered(): void
    {
        $router = app(Router::class);

        $this->assertSame(EnsureFeatureEnabled::class, $router->getMiddleware()['feature'] ?? null);
    }

    public function test_route_cache_serializes_parameterized_feature_middleware(): void
    {
        $path = app()->getCachedRoutesPath();

        $this->artisan('route:cache')->assertSuccessful();

        $this->assertFileExists($path);
        $this->assertStringContainsString('feature:ai-writer', (string) file_get_contents($path));
    }

    public function test_cached_feature_middleware_still_enforces_status(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        $this->refreshApplication();
        $this->artisan('migrate')->assertSuccessful();

        $this->get(route('testing.feature.probe'))
            ->assertOk()
            ->assertSeeText('ok');

        $feature = SystemFeature::query()->where('code', 'ai-writer')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->get(route('testing.feature.probe'))
            ->assertNotFound()
            ->assertStatus(404);
    }
}
