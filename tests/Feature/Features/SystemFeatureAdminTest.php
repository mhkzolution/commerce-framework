<?php

declare(strict_types=1);

namespace Tests\Feature\Features;

use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\IamAuditLog;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemFeatureAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        app()->setLocale('en');
    }

    public function test_super_admin_can_view_features_index_with_parent_module_name(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.system.features.index'))
            ->assertOk()
            ->assertSee('Scheduled Publishing', false)
            ->assertSee('scheduled-publishing', false)
            ->assertSee('CMS', false);
    }

    public function test_features_index_searches_name_code_description_and_module_code(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.system.features.index', ['search' => 'seo']))
            ->assertOk()
            ->assertSee('Advanced SEO', false)
            ->assertDontSee('Review Monitor', false);

        $this->actingAs($admin)
            ->get(route('admin.system.features.index', ['search' => 'reviews']))
            ->assertOk()
            ->assertSee('Review Monitor', false)
            ->assertDontSee('Advanced SEO', false);
    }

    public function test_parent_disabled_hint_preserves_enabled_feature_status(): void
    {
        $cms = SystemModule::query()->where('code', 'cms')->firstOrFail();
        app(ModuleService::class)->updateStatus($cms, ModuleStatus::Disabled);

        $feature = SystemFeature::query()->where('code', 'scheduled-publishing')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->get(route('admin.system.features.index'))
            ->assertOk()
            ->assertSee('ENABLED', false)
            ->assertSee('INACTIVE (MODULE DISABLED)', false)
            ->assertSee('Parent module is disabled.', false);

        $this->assertSame(FeatureStatus::Enabled, $feature->fresh()?->status);
    }

    public function test_status_can_be_disabled(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->put(route('admin.system.features.update', $feature), [
                'status' => FeatureStatus::Disabled->value,
            ])
            ->assertRedirect(route('admin.system.features.index'));

        $this->assertSame(FeatureStatus::Disabled, $feature->fresh()?->status);
    }

    public function test_status_change_writes_an_audit_log(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->put(route('admin.system.features.update', $feature), [
                'status' => FeatureStatus::Disabled->value,
            ])
            ->assertRedirect(route('admin.system.features.index'));

        $entry = IamAuditLog::query()
            ->where('action', 'system.feature.status_changed')
            ->latest('id')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame($admin->id, $entry->user_id);
        $this->assertSame(SystemFeature::class, $entry->subject_type);
        $this->assertSame($feature->id, $entry->subject_id);
        $this->assertSame('advanced-seo', $entry->meta['code'] ?? null);
        $this->assertSame('Advanced SEO', $entry->meta['feature_name'] ?? null);
        $this->assertSame('cms', $entry->meta['module_code'] ?? null);
        $this->assertSame('CMS', $entry->meta['module_name'] ?? null);
        $this->assertSame(FeatureStatus::Enabled->value, $entry->meta['old_status'] ?? null);
        $this->assertSame(FeatureStatus::Disabled->value, $entry->meta['new_status'] ?? null);
    }

    public function test_unchanged_status_does_not_write_an_audit_log(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();
        $auditCount = IamAuditLog::query()
            ->where('action', 'system.feature.status_changed')
            ->count();

        $this->actingAs(User::query()->first())
            ->put(route('admin.system.features.update', $feature), [
                'status' => FeatureStatus::Enabled->value,
            ])
            ->assertRedirect(route('admin.system.features.index'));

        $this->assertSame(FeatureStatus::Enabled, $feature->fresh()?->status);
        $this->assertSame(
            $auditCount,
            IamAuditLog::query()->where('action', 'system.feature.status_changed')->count(),
        );
    }

    public function test_invalid_status_is_rejected(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();

        $this->actingAs(User::query()->first())
            ->from(route('admin.system.features.index'))
            ->put(route('admin.system.features.update', $feature), ['status' => 'ARCHIVED'])
            ->assertRedirect(route('admin.system.features.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame(FeatureStatus::Enabled, $feature->fresh()?->status);
    }

    public function test_core_feature_status_update_is_rejected(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();
        $feature->update(['is_core' => true]);

        $this->actingAs(User::query()->first())
            ->from(route('admin.system.features.index'))
            ->put(route('admin.system.features.update', $feature), [
                'status' => FeatureStatus::Disabled->value,
            ])
            ->assertRedirect(route('admin.system.features.index'))
            ->assertSessionHasErrors('status');

        $this->assertSame(FeatureStatus::Enabled, $feature->fresh()?->status);
    }
}
