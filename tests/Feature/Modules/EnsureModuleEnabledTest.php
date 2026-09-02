<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Commerce\Cms\Models\Post;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EnsureModuleEnabledTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_active_module_routes_are_allowed(): void
    {
        $this->assertTrue(ModuleService::isActive('blog'));

        Post::query()->create([
            'title' => 'Live Post',
            'slug' => 'live-post',
            'content' => 'Hello',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->assertModuleDoesNotBlock(route('storefront.cms.posts.index'));
        $this->assertModuleDoesNotBlock(route('storefront.cms.posts.show', 'live-post'));

        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertOk();
    }

    public function test_hidden_module_routes_still_work(): void
    {
        $this->setModuleStatus('blog', ModuleStatus::Hidden);

        Post::query()->create([
            'title' => 'Hidden Module Post',
            'slug' => 'hidden-module-post',
            'content' => 'Still reachable',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->assertModuleDoesNotBlock(route('storefront.cms.posts.index'));
        $this->assertModuleDoesNotBlock(route('storefront.cms.posts.show', 'hidden-module-post'));

        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertOk();
    }

    public function test_disabled_module_routes_return_404_not_403(): void
    {
        $this->setModuleStatus('blog', ModuleStatus::Disabled);

        $this->get(route('storefront.cms.posts.index'))
            ->assertNotFound()
            ->assertDontSee('This action is unauthorized.', false);

        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertNotFound();
    }

    public function test_disabled_cms_hides_pages_but_does_not_affect_unrelated_admin(): void
    {
        $this->setModuleStatus('cms', ModuleStatus::Disabled);

        $this->get(route('storefront.cms.pages.show', 'about'))->assertNotFound();

        $this->actingAs(User::query()->first())
            ->get(route('admin.cms.pages.index'))
            ->assertNotFound();

        $this->actingAs(User::query()->first())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    private function setModuleStatus(string $code, ModuleStatus $status): void
    {
        $module = SystemModule::query()->where('code', $code)->firstOrFail();
        app(ModuleService::class)->updateStatus($module, $status);
    }

    private function assertModuleDoesNotBlock(string $uri): void
    {
        $status = $this->get($uri)->status();

        $this->assertNotContains($status, [403, 404], 'Module middleware blocked an allowed route.');
    }
}
