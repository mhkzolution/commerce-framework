<?php

declare(strict_types=1);

namespace Tests\Unit\Modules;

use Commerce\Core\Enums\ModuleStatus;
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

final class ModuleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_modules_are_active_by_default(): void
    {
        $this->assertTrue(ModuleService::isActive('blog'));
        $this->assertFalse(ModuleService::isHidden('blog'));
        $this->assertFalse(ModuleService::isDisabled('blog'));
        $this->assertSame('Blog', ModuleService::get('blog')?->name);
    }

    public function test_all_returns_default_registry_codes(): void
    {
        $codes = ModuleService::all()->pluck('code')->all();

        $this->assertSame(
            [
                'media',
                'settings',
                'users',
                'roles',
                'permissions',
                'cms',
                'blog',
                'footer-management',
                'customer-experience',
                'reviews',
                'marketplace',
                'kyc',
            ],
            $codes,
        );
    }

    public function test_unknown_module_is_not_active_hidden_or_disabled(): void
    {
        $this->assertNull(ModuleService::get('does-not-exist'));
        $this->assertFalse(ModuleService::isActive('does-not-exist'));
        $this->assertFalse(ModuleService::isHidden('does-not-exist'));
        $this->assertFalse(ModuleService::isDisabled('does-not-exist'));
        $this->assertFalse(module_active('foobar'));
        $this->assertFalse(module_hidden('foobar'));
        $this->assertFalse(module_disabled('foobar'));
    }

    public function test_unknown_module_helpers_never_throw_and_log_a_warning(): void
    {
        Event::fake([MessageLogged::class]);

        $this->assertFalse(module_active('unknown'));
        $this->assertFalse(module_hidden('unknown'));
        $this->assertFalse(module_disabled('unknown'));

        Event::assertDispatched(MessageLogged::class, function (MessageLogged $event): bool {
            return $event->level === 'warning'
                && str_contains((string) $event->message, 'Unknown system module')
                && ($event->context['code'] ?? null) === 'unknown';
        });
    }

    public function test_status_checks_stay_safe_when_system_modules_table_is_missing(): void
    {
        Schema::dropIfExists('system_modules');
        ModuleService::clearCache();

        $this->assertFalse(module_active('blog'));
        $this->assertFalse(module_hidden('blog'));
        $this->assertFalse(module_disabled('blog'));
        $this->assertNull(ModuleService::get('blog'));
        $this->assertTrue(ModuleService::all()->isEmpty());
    }

    public function test_core_modules_are_flagged_and_always_active(): void
    {
        foreach (['media', 'settings', 'users', 'roles', 'permissions'] as $code) {
            $module = ModuleService::get($code);
            $this->assertNotNull($module);
            $this->assertTrue($module->is_core);
            $this->assertTrue(ModuleService::isActive($code));
            $this->assertFalse(ModuleService::isHidden($code));
            $this->assertFalse(ModuleService::isDisabled($code));
        }
    }

    public function test_core_module_status_cannot_be_changed(): void
    {
        $media = SystemModule::query()->where('code', 'media')->firstOrFail();

        try {
            app(ModuleService::class)->updateStatus($media, ModuleStatus::Disabled);
            $this->fail('Core modules must reject status changes.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertTrue($media->fresh()?->is_core);
        $this->assertSame(ModuleStatus::Active, $media->fresh()?->status);
        $this->assertTrue(ModuleService::isActive('media'));
    }

    public function test_helpers_delegate_to_module_service(): void
    {
        $this->assertTrue(module_active('cms'));
        $this->assertFalse(module_hidden('cms'));
        $this->assertFalse(module_disabled('cms'));
    }

    public function test_status_helpers_reflect_hidden_and_disabled(): void
    {
        $service = app(ModuleService::class);
        $blog = SystemModule::query()->where('code', 'blog')->firstOrFail();

        $service->updateStatus($blog, ModuleStatus::Hidden);

        $this->assertFalse(ModuleService::isActive('blog'));
        $this->assertTrue(ModuleService::isHidden('blog'));
        $this->assertFalse(ModuleService::isDisabled('blog'));
        $this->assertTrue(module_hidden('blog'));

        $service->updateStatus($blog->fresh(), ModuleStatus::Disabled);

        $this->assertFalse(ModuleService::isActive('blog'));
        $this->assertFalse(ModuleService::isHidden('blog'));
        $this->assertTrue(ModuleService::isDisabled('blog'));
        $this->assertTrue(module_disabled('blog'));
    }

    public function test_definitions_are_cached_and_not_queried_on_every_read(): void
    {
        ModuleService::all();

        DB::enableQueryLog();
        ModuleService::isActive('blog');
        ModuleService::isHidden('marketplace');
        ModuleService::get('cms');
        ModuleService::all();

        $this->assertSame([], DB::getQueryLog());
    }

    public function test_cache_is_cleared_after_status_update(): void
    {
        $this->assertTrue(ModuleService::isActive('reviews'));

        $reviews = SystemModule::query()->where('code', 'reviews')->firstOrFail();
        app(ModuleService::class)->updateStatus($reviews, ModuleStatus::Disabled);

        $this->assertFalse(Cache::has(ModuleService::CACHE_KEY));
        $this->assertTrue(ModuleService::isDisabled('reviews'));
        $this->assertFalse(module_active('reviews'));
    }
}
