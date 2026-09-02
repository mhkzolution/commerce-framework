<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Commerce\Cms\Models\Post;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Http\Middleware\EnsureModuleEnabled;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class SystemModuleRouteCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    protected function tearDown(): void
    {
        if ($this->app->routesAreCached()) {
            Artisan::call('route:clear');
        }

        parent::tearDown();
    }

    public function test_module_middleware_alias_is_registered_with_parameter(): void
    {
        $router = app(Router::class);

        $this->assertSame(EnsureModuleEnabled::class, $router->getMiddleware()['module'] ?? null);

        $route = $router->getRoutes()->getByName('storefront.cms.posts.index');
        $this->assertNotNull($route);
        $this->assertContains('module:blog', $route->gatherMiddleware());
    }

    public function test_route_cache_serializes_parameterized_module_middleware(): void
    {
        $path = app()->getCachedRoutesPath();

        $this->artisan('route:cache')->assertSuccessful();

        $this->assertFileExists($path);
        $cached = (string) file_get_contents($path);

        $this->assertStringContainsString('module:blog', $cached);
        $this->assertStringContainsString('module:cms', $cached);
    }

    public function test_parameterized_module_middleware_still_enforces_status(): void
    {
        Post::query()->create([
            'title' => 'Cached Route Post',
            'slug' => 'cached-route-post',
            'content' => 'Hello',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->assertNotContains($this->get(route('storefront.cms.posts.index'))->status(), [403, 404]);
        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertOk();

        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();
        app(ModuleService::class)->updateStatus($blog, ModuleStatus::Disabled);

        $this->get(route('storefront.cms.posts.index'))->assertNotFound();
        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertNotFound();
    }
}
