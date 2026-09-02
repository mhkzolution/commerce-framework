<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Carbon\CarbonImmutable;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Services\PageService;
use Commerce\Cms\Services\StorefrontBlogService;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class CmsScheduledPublishingTest extends TestCase
{
    use RefreshDatabase;

    private const NOW = '2026-09-01 12:00:00';

    private const FUTURE_PUBLISH_AT = '2026-10-01 09:00:00';

    private const DUE_PUBLISH_AT = '2026-08-01 09:00:00';

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

    public function test_creating_published_post_without_dates_persists_published_and_sets_published_at(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Live now post',
                'slug' => 'live-now-post',
                'content' => 'Published immediately.',
                'status' => 'published',
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'live-now-post')->first();

        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::NOW)));
        $this->assertNull($post->unpublish_at);
    }

    public function test_creating_published_post_with_future_published_at_persists_scheduled_keeping_datetime(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Future post',
                'slug' => 'future-post',
                'content' => 'Goes live later.',
                'status' => 'published',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'future-post')->first();

        $this->assertNotNull($post);
        $this->assertSame('scheduled', $post->status);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));
        $this->assertNull($post->unpublish_at);
    }

    public function test_creating_scheduled_post_without_date_fails_validation(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.cms.posts.create'))
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Missing date post',
                'slug' => 'missing-date-post',
                'content' => 'Needs a publish date.',
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('admin.cms.posts.create'))
            ->assertInvalid('published_at')
            ->assertSessionHasErrors('published_at');

        $this->assertDatabaseMissing('cms_posts', ['slug' => 'missing-date-post']);
    }

    public function test_creating_scheduled_post_with_future_published_at_and_null_unpublish_persists_scheduled(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Explicit schedule post',
                'slug' => 'explicit-schedule-post',
                'content' => 'Scheduled via status.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
                'unpublish_at' => null,
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'explicit-schedule-post')->first();

        $this->assertNotNull($post);
        $this->assertSame('scheduled', $post->status);
        $this->assertTrue($post->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));
        $this->assertNull($post->unpublish_at);
    }

    public function test_creating_published_page_without_dates_persists_published_and_sets_published_at(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Live now page',
                'slug' => 'live-now-page',
                'content' => 'Published immediately.',
                'status' => 'published',
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'live-now-page')->first();

        $this->assertNotNull($page);
        $this->assertSame('published', $page->status);
        $this->assertNotNull($page->published_at);
        $this->assertTrue($page->published_at->equalTo(CarbonImmutable::parse(self::NOW)));
        $this->assertNull($page->unpublish_at);
    }

    public function test_creating_published_page_with_future_published_at_persists_scheduled_keeping_datetime(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Future page',
                'slug' => 'future-page',
                'content' => 'Goes live later.',
                'status' => 'published',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'future-page')->first();

        $this->assertNotNull($page);
        $this->assertSame('scheduled', $page->status);
        $this->assertTrue($page->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));
        $this->assertNull($page->unpublish_at);
    }

    public function test_creating_scheduled_page_without_date_fails_validation(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.cms.pages.create'))
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Missing date page',
                'slug' => 'missing-date-page',
                'content' => 'Needs a publish date.',
                'status' => 'scheduled',
            ])
            ->assertRedirect(route('admin.cms.pages.create'))
            ->assertInvalid('published_at')
            ->assertSessionHasErrors('published_at');

        $this->assertDatabaseMissing('cms_pages', ['slug' => 'missing-date-page']);
    }

    public function test_creating_scheduled_page_with_future_published_at_and_null_unpublish_persists_scheduled(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Explicit schedule page',
                'slug' => 'explicit-schedule-page',
                'content' => 'Scheduled via status.',
                'status' => 'scheduled',
                'published_at' => $this->datetimeLocal(self::FUTURE_PUBLISH_AT),
                'unpublish_at' => null,
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'explicit-schedule-page')->first();

        $this->assertNotNull($page);
        $this->assertSame('scheduled', $page->status);
        $this->assertTrue($page->published_at->equalTo(CarbonImmutable::parse(self::FUTURE_PUBLISH_AT)));
        $this->assertNull($page->unpublish_at);
    }

    public function test_migration_remaps_published_future_published_at_to_scheduled_and_keeps_null_published_at(): void
    {
        $future = CarbonImmutable::parse(self::FUTURE_PUBLISH_AT);

        $futurePost = Post::query()->create([
            'title' => 'Remap future post',
            'slug' => 'remap-future-post',
            'content' => 'Published with a future date.',
            'status' => 'published',
            'published_at' => $future,
        ]);
        $nullPost = Post::query()->create([
            'title' => 'Remap null post',
            'slug' => 'remap-null-post',
            'content' => 'Published with no date.',
            'status' => 'published',
            'published_at' => null,
        ]);
        $futurePage = Page::query()->create([
            'title' => 'Remap future page',
            'slug' => 'remap-future-page',
            'content' => 'Published with a future date.',
            'status' => 'published',
            'published_at' => $future,
        ]);
        $nullPage = Page::query()->create([
            'title' => 'Remap null page',
            'slug' => 'remap-null-page',
            'content' => 'Published with no date.',
            'status' => 'published',
            'published_at' => null,
        ]);

        $migration = require base_path('modules/Cms/database/migrations/2026_09_01_200000_add_cms_scheduled_publishing_columns.php');
        $migration->up();

        $this->assertSame('scheduled', $futurePost->fresh()->status);
        $this->assertTrue($futurePost->fresh()->published_at->equalTo($future));
        $this->assertSame('published', $nullPost->fresh()->status);
        $this->assertNull($nullPost->fresh()->published_at);

        $this->assertSame('scheduled', $futurePage->fresh()->status);
        $this->assertTrue($futurePage->fresh()->published_at->equalTo($future));
        $this->assertSame('published', $nullPage->fresh()->status);
        $this->assertNull($nullPage->fresh()->published_at);
    }

    public function test_scheduled_post_and_page_are_hidden_on_storefront_and_excluded_from_sitemap(): void
    {
        Post::query()->create([
            'title' => 'Scheduled launch post',
            'slug' => 'scheduled-launch-post',
            'content' => 'Not live yet.',
            'status' => 'scheduled',
            'published_at' => CarbonImmutable::parse(self::FUTURE_PUBLISH_AT),
        ]);

        Page::query()->create([
            'title' => 'Scheduled launch page',
            'slug' => 'scheduled-launch-page',
            'content' => 'Not live yet.',
            'status' => 'scheduled',
            'published_at' => CarbonImmutable::parse(self::FUTURE_PUBLISH_AT),
        ]);

        $this->get(route('storefront.cms.posts.show', 'scheduled-launch-post'))
            ->assertNotFound();

        $this->get(route('storefront.cms.pages.show', 'scheduled-launch-page'))
            ->assertNotFound();

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertDontSee('scheduled-launch-post', false)
            ->assertDontSee('scheduled-launch-page', false);
    }

    public function test_publish_scheduled_command_makes_due_content_visible_on_storefront(): void
    {
        Post::query()->create([
            'title' => 'Due scheduled post',
            'slug' => 'due-scheduled-post',
            'content' => 'Post is now live.',
            'status' => 'scheduled',
            'published_at' => CarbonImmutable::parse(self::DUE_PUBLISH_AT),
        ]);

        Page::query()->create([
            'title' => 'Due scheduled page',
            'slug' => 'due-scheduled-page',
            'content' => 'Page is now live.',
            'status' => 'scheduled',
            'published_at' => CarbonImmutable::parse(self::DUE_PUBLISH_AT),
        ]);

        $this->get(route('storefront.cms.posts.show', 'due-scheduled-post'))->assertNotFound();
        $this->get(route('storefront.cms.pages.show', 'due-scheduled-page'))->assertNotFound();

        $this->artisan('cms:publish-scheduled')
            ->expectsOutput('Published 2, archived 0.')
            ->assertSuccessful();

        $this->assertTrue($this->publishedPostExists('due-scheduled-post'));
        $this->assertNotNull(app(PageService::class)->findPublishedBySlug('due-scheduled-page'));

        $this->get(route('storefront.cms.pages.show', 'due-scheduled-page'))
            ->assertOk()
            ->assertSee('Page is now live.');

        $this->assertDatabaseHas('cms_posts', [
            'slug' => 'due-scheduled-post',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('cms_pages', [
            'slug' => 'due-scheduled-page',
            'status' => 'published',
        ]);
    }

    public function test_scheduled_post_is_hidden_on_storefront_but_visible_via_signed_preview(): void
    {
        $post = Post::query()->create([
            'title' => 'Preview scheduled',
            'slug' => 'preview-scheduled',
            'content' => 'Scheduled body.',
            'status' => 'scheduled',
            'published_at' => CarbonImmutable::parse(self::FUTURE_PUBLISH_AT),
        ]);

        $this->get(route('storefront.cms.posts.show', 'preview-scheduled'))
            ->assertNotFound();

        $this->get(route('storefront.cms.posts.preview', $post))
            ->assertForbidden();

        $this->assertFalse($this->publishedPostExists('preview-scheduled'));

        $signed = URL::temporarySignedRoute(
            'storefront.cms.posts.preview',
            now()->addHour(),
            ['post' => $post->uuid],
        );

        $this->assertStringContainsString('signature=', $signed);
    }

    public function test_direct_created_published_post_with_future_published_at_is_visible_on_storefront(): void
    {
        Post::query()->create([
            'title' => 'Future dated live post',
            'slug' => 'future-dated-live-post',
            'content' => 'Visible because status is published.',
            'status' => 'published',
            'published_at' => CarbonImmutable::parse(self::FUTURE_PUBLISH_AT),
        ]);

        $this->assertTrue($this->publishedPostExists('future-dated-live-post'));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('storefront.cms.posts.show', 'future-dated-live-post'), false);
    }

    public function test_direct_created_published_page_with_future_published_at_is_visible_on_storefront(): void
    {
        Page::query()->create([
            'title' => 'Future dated live page',
            'slug' => 'future-dated-live-page',
            'content' => 'Visible because status is published.',
            'status' => 'published',
            'published_at' => CarbonImmutable::parse(self::FUTURE_PUBLISH_AT),
        ]);

        $this->get(route('storefront.cms.pages.show', 'future-dated-live-page'))
            ->assertOk()
            ->assertSee('Visible because status is published.');

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('storefront.cms.pages.show', 'future-dated-live-page'), false);
    }

    private function admin(): User
    {
        return User::query()->first();
    }

    private function publishedPostExists(string $slug): bool
    {
        return app(StorefrontBlogService::class)
            ->publishedQuery()
            ->where('slug', $slug)
            ->exists();
    }

    private function datetimeLocal(string $datetime): string
    {
        return CarbonImmutable::parse($datetime)->format('Y-m-d\TH:i');
    }
}
