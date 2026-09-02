<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemModuleSidebarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_active_blog_shows_posts_nav_and_highlights_current_item(): void
    {
        $this->assertTrue(ModuleService::isActive('blog'));

        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertOk()
            ->assertSee('admin-nav-item', false)
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('admin.cms.posts.index'), '/').'"[^>]*class="[^"]*\bis-active\b/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-nav-label="(Posts|บทความ)"/',
            $html,
        );
    }

    public function test_hidden_blog_hides_nav_but_keeps_sidebar_intact_on_direct_url(): void
    {
        $this->setModuleStatus('blog', ModuleStatus::Hidden);

        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'))
            ->assertOk()
            ->assertSee('admin-nav-item', false)
            ->getContent();

        $this->assertDoesNotMatchRegularExpression(
            '/data-nav-label="(Posts|บทความ)"/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-nav-label="(Pages|หน้าเพจ)"/',
            $html,
        );
        $this->assertStringNotContainsString('href="'.route('admin.cms.posts.index').'"', $html);
        $this->assertDoesNotMatchRegularExpression('/Exception|ErrorException|Undefined/', $html);
    }

    public function test_disabled_blog_hides_nav_and_returns_404_without_nav_exception(): void
    {
        $this->setModuleStatus('blog', ModuleStatus::Disabled);

        $response = $this->actingAs(User::query()->first())
            ->get(route('admin.cms.posts.index'));

        $response->assertNotFound();
        $this->assertDoesNotMatchRegularExpression(
            '/data-nav-label="(Posts|บทความ)"/',
            $response->getContent(),
        );
    }

    public function test_core_identity_links_remain_in_navigation(): void
    {
        $labels = $this->collectLabels(
            app(AdminNavigationBuilderInterface::class)->build(User::query()->first()),
        );

        $this->assertContains('Users', $labels);
        $this->assertContains('Roles', $labels);
        $this->assertContains('Permissions', $labels);
        $this->assertContains('Media', $labels);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<string>
     */
    private function collectLabels(array $items): array
    {
        $labels = [];

        foreach ($items as $item) {
            $labels[] = (string) $item['label'];
            $labels = [...$labels, ...$this->collectLabels($item['children'] ?? [])];
        }

        return $labels;
    }

    private function setModuleStatus(string $code, ModuleStatus $status): void
    {
        $module = SystemModule::query()->where('code', $code)->firstOrFail();
        app(ModuleService::class)->updateStatus($module, $status);
    }
}
