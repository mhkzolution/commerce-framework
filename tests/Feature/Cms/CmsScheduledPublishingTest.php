<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Carbon\CarbonImmutable;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
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

    public function test_creating_scheduled_post_without_date_returns_422(): void
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

    public function test_creating_scheduled_page_without_date_returns_422(): void
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

        $this->get(route('storefront.cms.posts.show', 'due-scheduled-post'))
            ->assertOk()
            ->assertSee('Post is now live.');

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
        $admin = $this->admin();

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

        $signed = URL::temporarySignedRoute(
            'storefront.cms.posts.preview',
            now()->addHour(),
            ['post' => $post->uuid],
        );

        $this->actingAs($admin)
            ->get($signed)
            ->assertOk()
            ->assertSee('Preview scheduled')
            ->assertSee('Scheduled body.')
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertDontSee('rel="canonical"', false);
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

        $this->get(route('storefront.cms.posts.show', 'future-dated-live-post'))
            ->assertOk()
            ->assertSee('Visible because status is published.');

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

    private function datetimeLocal(string $datetime): string
    {
        return CarbonImmutable::parse($datetime)->format('Y-m-d\TH:i');
    }
}
