<?php

declare(strict_types=1);

namespace Tests\Feature\Modules;

use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\IamAuditLog;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemModuleAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        app()->setLocale('en');
    }

    public function test_super_admin_can_view_modules_index(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.system.modules.index'))
            ->assertOk()
            ->assertSee(__('commerce::admin.modules_title'), false)
            ->assertSee('Blog', false)
            ->assertSee('blog', false)
            ->assertSee('ACTIVE', false);
    }

    public function test_core_modules_are_visible_but_not_editable(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.system.modules.index'))
            ->assertOk()
            ->assertSee('Media Library', false)
            ->assertSee('Users', false)
            ->assertSee('CORE MODULE', false);

        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.system.modules.index'))
            ->getContent();

        $media = SystemModule::query()->where('code', 'media')->firstOrFail();
        $this->assertStringNotContainsString(
            'action="'.route('admin.system.modules.update', $media).'"',
            $html,
        );
    }

    public function test_core_module_status_update_is_rejected(): void
    {
        $media = SystemModule::query()->where('code', 'media')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->from(route('admin.system.modules.index'))
            ->put(route('admin.system.modules.update', $media), ['status' => ModuleStatus::Disabled->value])
            ->assertRedirect(route('admin.system.modules.index'))
            ->assertSessionHasErrors('status');

        $this->assertTrue($media->fresh()?->is_core);
        $this->assertSame(ModuleStatus::Active, $media->fresh()?->status);
        $this->assertTrue(ModuleService::isActive('media'));
    }

    public function test_status_change_writes_an_audit_log(): void
    {
        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->put(route('admin.system.modules.update', $blog), ['status' => ModuleStatus::Disabled->value])
            ->assertRedirect(route('admin.system.modules.index'));

        $entry = IamAuditLog::query()->where('action', 'system.module.status_changed')->latest('id')->first();
        $this->assertNotNull($entry);
        $this->assertSame($admin->id, $entry->user_id);
        $this->assertSame(SystemModule::class, $entry->subject_type);
        $this->assertSame($blog->id, $entry->subject_id);
        $this->assertSame('blog', $entry->meta['code'] ?? null);
        $this->assertSame(ModuleStatus::Active->value, $entry->meta['old_status'] ?? null);
        $this->assertSame(ModuleStatus::Disabled->value, $entry->meta['new_status'] ?? null);
        $this->assertNotNull($entry->created_at);
    }

    public function test_modules_index_supports_search(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.system.modules.index', ['search' => 'market']))
            ->assertOk()
            ->assertSee('Marketplace', false)
            ->assertDontSee('>Blog<', false);
    }

    public function test_modules_index_empty_state_when_search_misses(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.system.modules.index', ['search' => 'no-such-module']))
            ->assertOk()
            ->assertSee('No modules match', false);
    }

    public function test_status_can_transition_between_active_hidden_and_disabled(): void
    {
        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->put(route('admin.system.modules.update', $blog), ['status' => ModuleStatus::Hidden->value])
            ->assertRedirect(route('admin.system.modules.index'));

        $this->assertTrue(ModuleService::isHidden('blog'));
        $this->assertSame(ModuleStatus::Hidden, $blog->fresh()->status);

        $this->actingAs(User::query()->first())
            ->put(route('admin.system.modules.update', $blog), ['status' => ModuleStatus::Disabled->value])
            ->assertRedirect(route('admin.system.modules.index'));

        $this->assertTrue(ModuleService::isDisabled('blog'));

        $this->actingAs(User::query()->first())
            ->put(route('admin.system.modules.update', $blog), ['status' => ModuleStatus::Active->value])
            ->assertRedirect(route('admin.system.modules.index'));

        $this->assertTrue(ModuleService::isActive('blog'));
    }

    public function test_invalid_status_is_rejected(): void
    {
        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->from(route('admin.system.modules.index'))
            ->put(route('admin.system.modules.update', $blog), ['status' => 'ARCHIVED'])
            ->assertRedirect(route('admin.system.modules.index'))
            ->assertSessionHasErrors('status');

        $this->assertTrue(ModuleService::isActive('blog'));
    }

    public function test_hidden_module_is_removed_from_admin_navigation(): void
    {
        $nav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $this->assertContains('Posts', $this->collectLabels($nav));
        $this->assertContains('Marketplace', $this->collectLabels($nav));

        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();
        app(ModuleService::class)->updateStatus($blog, ModuleStatus::Hidden);

        $hiddenNav = app(AdminNavigationBuilderInterface::class)->build(User::query()->first());
        $labels = $this->collectLabels($hiddenNav);

        $this->assertNotContains('Posts', $labels);
        $this->assertContains('Pages', $labels);
        $this->assertContains('Marketplace', $labels);
    }

    public function test_disabled_module_is_removed_from_admin_navigation(): void
    {
        $marketplace = SystemModule::query()->where('code', 'marketplace')->firstOrFail();
        app(ModuleService::class)->updateStatus($marketplace, ModuleStatus::Disabled);

        $labels = $this->collectLabels(
            app(AdminNavigationBuilderInterface::class)->build(User::query()->first()),
        );

        $this->assertNotContains('Marketplace', $labels);
        $this->assertContains('Posts', $labels);
    }

    public function test_dashboard_blog_widget_renders_only_when_active(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Blog posts', false);

        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();
        app(ModuleService::class)->updateStatus($blog, ModuleStatus::Hidden);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Blog posts', false);
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
}
