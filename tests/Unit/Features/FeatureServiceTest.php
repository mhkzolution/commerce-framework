<?php

declare(strict_types=1);

namespace Tests\Unit\Features;

use Commerce\Core\Database\Seeders\SystemFeatureSeeder;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Models\SystemFeature;
use Commerce\Core\Models\SystemModule;
use Commerce\Core\Modules\ModuleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class FeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_features_are_enabled_by_default(): void
    {
        $this->assertSame(
            ['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'],
            SystemFeature::query()->orderBy('sort_order')->orderBy('name')->pluck('code')->all(),
        );

        foreach (['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'] as $code) {
            $row = SystemFeature::query()->where('code', $code)->first();
            $this->assertNotNull($row);
            $this->assertSame(FeatureStatus::Enabled, $row->status);
            $this->assertFalse($row->is_core);
        }
    }

    public function test_seeder_does_not_overwrite_existing_status(): void
    {
        $feature = SystemFeature::query()->where('code', 'scheduled-publishing')->firstOrFail();
        $feature->update([
            'name' => 'Stale name',
            'description' => 'Stale description',
            'status' => FeatureStatus::Disabled,
        ]);

        $this->seed(SystemFeatureSeeder::class);

        $feature->refresh();

        $this->assertSame(FeatureStatus::Disabled, $feature->status);
        $this->assertSame('Scheduled Publishing', $feature->name);
        $this->assertSame('Schedule CMS content for future publish', $feature->description);
    }

    public function test_seeder_clears_cached_and_memoized_definitions(): void
    {
        SystemFeature::query()
            ->where('code', 'scheduled-publishing')
            ->update(['name' => 'Stale name']);
        FeatureService::clearCache();

        $this->assertSame('Stale name', FeatureService::get('scheduled-publishing')?->name);

        $this->seed(SystemFeatureSeeder::class);

        $this->assertSame('Scheduled Publishing', FeatureService::get('scheduled-publishing')?->name);
    }

    public function test_all_returns_catalog_codes_in_sort_order(): void
    {
        $this->assertSame(
            ['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'],
            FeatureService::all()->pluck('code')->all(),
        );
    }

    public function test_get_unknown_code_is_silent_and_null(): void
    {
        Event::fake([MessageLogged::class]);

        $this->assertNull(FeatureService::get('foobar'));

        Event::assertNotDispatched(MessageLogged::class);
    }

    public function test_enabled_unknown_code_logs_feature_unknown_once(): void
    {
        Event::fake([MessageLogged::class]);

        $this->assertFalse(FeatureService::enabled('foobar'));
        $this->assertFalse(FeatureService::enabled('foobar'));

        Event::assertDispatchedTimes(MessageLogged::class, 1);
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && $event->message === 'Unknown system feature requested.'
                && ($event->context['warning_code'] ?? null) === 'feature_unknown'
                && ($event->context['code'] ?? null) === 'foobar';
        });
    }

    public function test_enabled_logs_feature_parent_missing_when_module_absent(): void
    {
        Event::fake([MessageLogged::class]);
        SystemModule::query()->where('code', 'cms')->delete();
        ModuleService::clearCache();

        $this->assertFalse(FeatureService::enabled('advanced-seo'));

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && $event->message === 'System feature parent module is missing.'
                && ($event->context['warning_code'] ?? null) === 'feature_parent_missing'
                && ($event->context['code'] ?? null) === 'advanced-seo'
                && ($event->context['module_code'] ?? null) === 'cms';
        });
    }

    public function test_parent_active_uses_feature_status(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();

        $this->assertTrue(FeatureService::enabled('advanced-seo'));

        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->assertFalse(FeatureService::enabled('advanced-seo'));
    }

    public function test_parent_disabled_forces_feature_false_when_enabled(): void
    {
        $cms = SystemModule::query()->where('code', 'cms')->firstOrFail();
        app(ModuleService::class)->updateStatus($cms, ModuleStatus::Disabled);

        $this->assertSame(
            FeatureStatus::Enabled,
            SystemFeature::query()->where('code', 'scheduled-publishing')->value('status'),
        );
        $this->assertFalse(FeatureService::enabled('scheduled-publishing'));
    }

    public function test_it_keeps_feature_enabled_when_parent_module_is_hidden(): void
    {
        $cms = SystemModule::query()->where('code', 'cms')->firstOrFail();
        app(ModuleService::class)->updateStatus($cms, ModuleStatus::Hidden);

        $this->assertTrue(FeatureService::enabled('scheduled-publishing'));
    }

    public function test_parent_hidden_still_respects_disabled_feature(): void
    {
        $cms = SystemModule::query()->where('code', 'cms')->firstOrFail();
        app(ModuleService::class)->updateStatus($cms, ModuleStatus::Hidden);
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->assertFalse(FeatureService::enabled('advanced-seo'));
    }

    public function test_status_checks_stay_safe_when_system_features_table_is_missing(): void
    {
        Event::fake([MessageLogged::class]);
        Schema::dropIfExists('system_features');
        FeatureService::clearCache();

        $this->assertFalse(FeatureService::enabled('scheduled-publishing'));
        $this->assertNull(FeatureService::get('scheduled-publishing'));
        $this->assertTrue(FeatureService::all()->isEmpty());

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && $event->message === 'System feature registry unavailable.';
        });
        Event::assertNotDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return ($event->context['warning_code'] ?? null) === 'feature_unknown';
        });
    }

    public function test_missing_registry_is_not_cached_as_an_empty_catalog(): void
    {
        Schema::dropIfExists('system_features');
        FeatureService::clearCache();
        Event::fake([MessageLogged::class]);

        $this->assertFalse(FeatureService::enabled('scheduled-publishing'));

        app(FeatureService::class)->resetMemo();

        $this->assertFalse(FeatureService::enabled('scheduled-publishing'));
        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && $event->message === 'System feature registry unavailable.';
        });
        Event::assertNotDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return ($event->context['warning_code'] ?? null) === 'feature_unknown';
        });
    }

    public function test_helpers_match_enabled(): void
    {
        $this->assertSame(FeatureService::enabled('scheduled-publishing'), feature_enabled('scheduled-publishing'));
        $this->assertSame(feature_enabled('scheduled-publishing'), feature('scheduled-publishing'));

        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->assertSame(FeatureService::enabled('advanced-seo'), feature_enabled('advanced-seo'));
        $this->assertSame(feature_enabled('advanced-seo'), feature('advanced-seo'));
    }

    public function test_definitions_are_cached_and_get_does_not_query(): void
    {
        FeatureService::all();
        ModuleService::all();

        DB::enableQueryLog();
        FeatureService::get('advanced-seo');
        FeatureService::enabled('scheduled-publishing');
        FeatureService::all();

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_cached_definitions_do_not_check_schema_after_memo_reset(): void
    {
        FeatureService::all();
        app(FeatureService::class)->resetMemo();

        DB::enableQueryLog();

        $this->assertCount(4, FeatureService::all());
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_legacy_cached_list_is_treated_as_an_available_catalog(): void
    {
        $attributes = SystemFeature::query()
            ->where('code', 'scheduled-publishing')
            ->firstOrFail()
            ->getAttributes();

        Cache::put(FeatureService::CACHE_KEY, [$attributes]);
        Schema::dropIfExists('system_features');
        app(FeatureService::class)->resetMemo();
        Event::fake([MessageLogged::class]);

        $this->assertTrue(FeatureService::enabled('scheduled-publishing'));
        Event::assertNotDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->message === 'System feature registry unavailable.';
        });
    }

    public function test_cache_is_cleared_after_status_update(): void
    {
        $this->assertTrue(FeatureService::enabled('review-monitor'));

        $feature = SystemFeature::query()->where('code', 'review-monitor')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);

        $this->assertFalse(Cache::has(FeatureService::CACHE_KEY));
        $this->assertFalse(FeatureService::enabled('review-monitor'));
        $this->assertFalse(feature_enabled('review-monitor'));
    }

    public function test_core_feature_status_cannot_be_changed(): void
    {
        $feature = SystemFeature::query()->where('code', 'advanced-seo')->firstOrFail();
        $feature->update(['is_core' => true]);

        try {
            app(FeatureService::class)->updateStatus($feature->fresh(), FeatureStatus::Disabled);
            $this->fail('Core features must reject status changes.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(FeatureStatus::Enabled, $feature->fresh()?->status);
    }
}
