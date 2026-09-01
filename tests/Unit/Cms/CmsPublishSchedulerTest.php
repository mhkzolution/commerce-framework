<?php

declare(strict_types=1);

namespace Tests\Unit\Cms;

use Carbon\CarbonImmutable;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Services\CmsPublishScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CmsPublishSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private CmsPublishScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-01 12:00:00');
        $this->scheduler = new CmsPublishScheduler;
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_publishes_due_scheduled_post_without_changing_published_at(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $post = $this->createPost('scheduled', $publishedAt);

        $result = $this->scheduler->run();

        $post->refresh();

        $this->assertSame(['published' => 1, 'archived' => 0], $result);
        $this->assertSame('published', $post->status);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
        $this->assertNull($post->unpublish_at);
    }

    public function test_archives_expired_published_post_without_nulling_published_at(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $unpublishAt = CarbonImmutable::parse('2026-08-15 09:00:00');
        $post = $this->createPost('published', $publishedAt, $unpublishAt);

        $result = $this->scheduler->run();

        $post->refresh();

        $this->assertSame(['published' => 0, 'archived' => 1], $result);
        $this->assertSame('archived', $post->status);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
        $this->assertTrue($post->unpublish_at->equalTo($unpublishAt));
    }

    public function test_same_run_archives_scheduled_post_when_both_timestamps_are_due(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $unpublishAt = CarbonImmutable::parse('2026-08-15 09:00:00');
        $post = $this->createPost('scheduled', $publishedAt, $unpublishAt);

        $result = $this->scheduler->run();

        $post->refresh();

        $this->assertSame(['published' => 1, 'archived' => 1], $result);
        $this->assertSame('archived', $post->status);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
        $this->assertTrue($post->unpublish_at->equalTo($unpublishAt));
    }

    public function test_second_run_does_not_change_post_counts_or_statuses(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $unpublishAt = CarbonImmutable::parse('2026-08-15 09:00:00');
        $post = $this->createPost('scheduled', $publishedAt, $unpublishAt);

        $this->scheduler->run();
        $result = $this->scheduler->run();

        $post->refresh();

        $this->assertSame(['published' => 0, 'archived' => 0], $result);
        $this->assertSame('archived', $post->status);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
    }

    public function test_publishes_due_scheduled_page_without_changing_published_at(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $page = $this->createPage('scheduled', $publishedAt);

        $result = $this->scheduler->run();

        $page->refresh();

        $this->assertSame(['published' => 1, 'archived' => 0], $result);
        $this->assertSame('published', $page->status);
        $this->assertTrue($page->published_at->equalTo($publishedAt));
        $this->assertNull($page->unpublish_at);
    }

    public function test_archives_expired_published_page_without_nulling_published_at(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $unpublishAt = CarbonImmutable::parse('2026-08-15 09:00:00');
        $page = $this->createPage('published', $publishedAt, $unpublishAt);

        $result = $this->scheduler->run();

        $page->refresh();

        $this->assertSame(['published' => 0, 'archived' => 1], $result);
        $this->assertSame('archived', $page->status);
        $this->assertTrue($page->published_at->equalTo($publishedAt));
        $this->assertTrue($page->unpublish_at->equalTo($unpublishAt));
    }

    public function test_same_run_archives_scheduled_page_when_both_timestamps_are_due(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $unpublishAt = CarbonImmutable::parse('2026-08-15 09:00:00');
        $page = $this->createPage('scheduled', $publishedAt, $unpublishAt);

        $result = $this->scheduler->run();

        $page->refresh();

        $this->assertSame(['published' => 1, 'archived' => 1], $result);
        $this->assertSame('archived', $page->status);
        $this->assertTrue($page->published_at->equalTo($publishedAt));
        $this->assertTrue($page->unpublish_at->equalTo($unpublishAt));
    }

    public function test_second_run_does_not_change_page_counts_or_statuses(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-08-01 09:00:00');
        $unpublishAt = CarbonImmutable::parse('2026-08-15 09:00:00');
        $page = $this->createPage('scheduled', $publishedAt, $unpublishAt);

        $this->scheduler->run();
        $result = $this->scheduler->run();

        $page->refresh();

        $this->assertSame(['published' => 0, 'archived' => 0], $result);
        $this->assertSame('archived', $page->status);
        $this->assertTrue($page->published_at->equalTo($publishedAt));
    }

    public function test_future_scheduled_post_is_left_scheduled(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-10-01 09:00:00');
        $post = $this->createPost('scheduled', $publishedAt);

        $result = $this->scheduler->run();

        $post->refresh();

        $this->assertSame(['published' => 0, 'archived' => 0], $result);
        $this->assertSame('scheduled', $post->status);
        $this->assertTrue($post->published_at->equalTo($publishedAt));
    }

    public function test_future_scheduled_page_is_left_scheduled(): void
    {
        $publishedAt = CarbonImmutable::parse('2026-10-01 09:00:00');
        $page = $this->createPage('scheduled', $publishedAt);

        $result = $this->scheduler->run();

        $page->refresh();

        $this->assertSame(['published' => 0, 'archived' => 0], $result);
        $this->assertSame('scheduled', $page->status);
        $this->assertTrue($page->published_at->equalTo($publishedAt));
    }

    private function createPost(
        string $status,
        CarbonImmutable $publishedAt,
        ?CarbonImmutable $unpublishAt = null,
    ): Post {
        return Post::query()->create([
            'title' => 'Scheduler post',
            'slug' => 'scheduler-post-'.uniqid(),
            'excerpt' => 'Excerpt',
            'content' => 'Body',
            'status' => $status,
            'published_at' => $publishedAt,
            'unpublish_at' => $unpublishAt,
        ]);
    }

    private function createPage(
        string $status,
        CarbonImmutable $publishedAt,
        ?CarbonImmutable $unpublishAt = null,
    ): Page {
        return Page::query()->create([
            'title' => 'Scheduler page',
            'slug' => 'scheduler-page-'.uniqid(),
            'content' => 'Body',
            'status' => $status,
            'published_at' => $publishedAt,
            'unpublish_at' => $unpublishAt,
        ]);
    }
}
