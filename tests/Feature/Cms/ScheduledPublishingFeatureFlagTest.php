<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Carbon\CarbonImmutable;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Models\SystemFeature;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ScheduledPublishingFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-09-01 12:00:00';

    private const FUTURE_PUBLISH_AT = '2026-10-01 09:00:00';

    private const FUTURE_UNPUBLISH_AT = '2026-11-01 09:00:00';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        CarbonImmutable::setTestNow(self::NOW);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_enabled_flag_shows_scheduling_controls_on_post_and_page_editors(): void
    {
        $this->assertTrue(feature('scheduled-publishing'));
        $this->withoutVite();

        $this->actingAs($this->admin())
            ->get(route('admin.cms.posts.create'))
            ->assertOk()
            ->assertSee('name="published_at"', false)
            ->assertSee('name="unpublish_at"', false)
            ->assertSee(__('cms::admin.published_at'))
            ->assertSee(__('cms::admin.unpublish_at'))
            ->assertSee(__('cms::admin.schedule_helper'))
            ->assertSee('value="scheduled"', false);

        $this->actingAs($this->admin())
            ->get(route('admin.cms.pages.create'))
            ->assertOk()
            ->assertSee('name="published_at"', false)
            ->assertSee('name="unpublish_at"', false)
            ->assertSee(__('cms::admin.published_at'))
            ->assertSee(__('cms::admin.unpublish_at'))
            ->assertSee(__('cms::admin.schedule_helper'))
            ->assertSee('value="scheduled"', false);
    }

    public function test_disabled_flag_hides_scheduling_controls_on_post_and_page_editors(): void
    {
        $this->disableScheduledPublishing();
        $this->withoutVite();

        $this->actingAs($this->admin())
            ->get(route('admin.cms.posts.create'))
            ->assertOk()
            ->assertDontSee('name="published_at"', false)
            ->assertDontSee('name="unpublish_at"', false)
            ->assertDontSee(__('cms::admin.published_at'))
            ->assertDontSee(__('cms::admin.unpublish_at'))
            ->assertDontSee(__('cms::admin.schedule_helper'))
            ->assertDontSee('value="scheduled"', false);

        $this->actingAs($this->admin())
            ->get(route('admin.cms.pages.create'))
            ->assertOk()
            ->assertDontSee('name="published_at"', false)
            ->assertDontSee('name="unpublish_at"', false)
            ->assertDontSee(__('cms::admin.published_at'))
            ->assertDontSee(__('cms::admin.unpublish_at'))
            ->assertDontSee(__('cms::admin.schedule_helper'))
            ->assertDontSee('value="scheduled"', false);
    }

    public function test_disabled_flag_rejects_scheduled_status_on_create(): void
    {
        $this->disableScheduledPublishing();

        $this->actingAs($this->admin())
            ->from(route('admin.cms.posts.create'))
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Rejected schedule post',
                'slug' => 'rejected-schedule-post',
                'content' => 'Should not persist.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
            ])
            ->assertRedirect(route('admin.cms.posts.create'))
            ->assertInvalid('status');

        $this->assertDatabaseMissing('cms_posts', ['slug' => 'rejected-schedule-post']);

        $this->actingAs($this->admin())
            ->from(route('admin.cms.pages.create'))
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Rejected schedule page',
                'slug' => 'rejected-schedule-page',
                'content' => 'Should not persist.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
            ])
            ->assertRedirect(route('admin.cms.pages.create'))
            ->assertInvalid('status');

        $this->assertDatabaseMissing('cms_pages', ['slug' => 'rejected-schedule-page']);
    }

    public function test_disabled_flag_ignores_scheduling_fields_on_create(): void
    {
        $this->disableScheduledPublishing();

        $this->actingAs($this->admin())
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Ignored schedule post',
                'slug' => 'ignored-schedule-post',
                'content' => 'Publish immediately.',
                'status' => 'published',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
                'unpublish_at' => $this->datetimeLocal(self::FUTURE_UNPUBLISH_AT),
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'ignored-schedule-post')->first();

        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::NOW)));
        $this->assertNull($post->unpublish_at);

        $this->actingAs($this->admin())
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Ignored schedule page',
                'slug' => 'ignored-schedule-page',
                'content' => 'Publish immediately.',
                'status' => 'published',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
                'unpublish_at' => $this->datetimeLocal(self::FUTURE_UNPUBLISH_AT),
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'ignored-schedule-page')->first();

        $this->assertNotNull($page);
        $this->assertSame('published', $page->status);
        $this->assertTrue($page->published_at->equalTo(CarbonImmutable::parse(self::NOW)));
        $this->assertNull($page->unpublish_at);
    }

    public function test_disabled_flag_ignores_scheduling_fields_on_update(): void
    {
        $this->disableScheduledPublishing();

        $post = Post::query()->create([
            'title' => 'Existing post',
            'slug' => 'existing-post',
            'content' => 'Body',
            'status' => 'published',
            'published_at' => CarbonImmutable::parse('2026-08-01 09:00:00'),
            'unpublish_at' => CarbonImmutable::parse('2026-12-01 09:00:00'),
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.cms.posts.update', $post), [
                'title' => 'Existing post updated',
                'slug' => 'existing-post',
                'content' => 'Body',
                'status' => 'published',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
                'unpublish_at' => $this->datetimeLocal(self::FUTURE_UNPUBLISH_AT),
            ])
            ->assertRedirect();

        $post->refresh();

        $this->assertSame('published', $post->status);
        $this->assertSame('Existing post updated', $post->title);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse('2026-08-01 09:00:00')));
        $this->assertTrue($post->unpublish_at->equalTo(CarbonImmutable::parse('2026-12-01 09:00:00')));
    }

    public function test_disabled_flag_keeps_existing_scheduled_row_when_title_is_updated(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Hold for later',
                'slug' => 'hold-for-later',
                'content' => 'Queued.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
                'unpublish_at' => $this->datetimeLocal(self::FUTURE_UNPUBLISH_AT),
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'hold-for-later')->firstOrFail();
        $this->assertSame('scheduled', $post->status);

        $this->disableScheduledPublishing();
        $this->withoutVite();

        $this->actingAs($this->admin())
            ->get(route('admin.cms.posts.edit', $post))
            ->assertOk()
            ->assertSee('value="scheduled"', false);

        $this->actingAs($this->admin())
            ->put(route('admin.cms.posts.update', $post), [
                'title' => 'Hold for later (typo fix)',
                'slug' => 'hold-for-later',
                'content' => 'Queued.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal('2026-12-01 09:00:00'),
                'unpublish_at' => $this->datetimeLocal('2027-01-01 09:00:00'),
            ])
            ->assertRedirect();

        $post->refresh();

        $this->assertSame('scheduled', $post->status);
        $this->assertSame('Hold for later (typo fix)', $post->title);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));
        $this->assertTrue($post->unpublish_at->equalTo(CarbonImmutable::parse(self::FUTURE_UNPUBLISH_AT)));
    }

    public function test_disabling_then_re_enabling_the_flag_resumes_due_scheduler_work(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Toggle schedule post',
                'slug' => 'toggle-schedule-post',
                'content' => 'Goes live after the flag returns.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'toggle-schedule-post')->firstOrFail();
        $this->assertSame('scheduled', $post->status);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));

        $this->disableScheduledPublishing();
        CarbonImmutable::setTestNow('2026-10-02 09:00:00');

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published 0, archived 0.')
            ->assertSuccessful();

        $post->refresh();
        $this->assertSame('scheduled', $post->status);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));

        $this->enableScheduledPublishing();

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published 1, archived 0.')
            ->assertSuccessful();

        $post->refresh();
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));
    }

    private function disableScheduledPublishing(): void
    {
        $feature = SystemFeature::query()->where('code', 'scheduled-publishing')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);
        $this->assertFalse(feature('scheduled-publishing'));
    }

    private function enableScheduledPublishing(): void
    {
        $feature = SystemFeature::query()->where('code', 'scheduled-publishing')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Enabled);
        $this->assertTrue(feature('scheduled-publishing'));
    }

    private function admin(): User
    {
        return User::query()->first();
    }

    private function datetimeLocal(string $datetime): string
    {
        return CarbonImmutable::parse($datetime)->format('Y-m-d\TH:i');
    }
}
