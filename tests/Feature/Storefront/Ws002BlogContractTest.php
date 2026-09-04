<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cms\Models\Post;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class Ws002BlogContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->withoutVite();
    }

    public function test_archive_uses_page_container_header_and_storefront_search(): void
    {
        $html = $this->get(route('storefront.cms.posts.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-blog-main', $html);
        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringContainsString('storefront-site-header', $html);
        $this->assertStringContainsString('name="search"', $html);
        $this->assertStringContainsString('storefront-blog-search', $html);
        $this->assertStringNotContainsString('x-admin.search-input', $html);
        $this->assertStringNotContainsString('storefront-blog-shell', $html);
    }

    public function test_article_uses_narrow_page_container_and_shared_header(): void
    {
        Post::query()->create([
            'title' => 'Header Review Notes',
            'slug' => 'header-review-notes',
            'excerpt' => 'Reading column.',
            'content' => '<h2>One</h2><p>Body copy for the article.</p>',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $html = $this->get(route('storefront.cms.posts.show', 'header-review-notes'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-blog-main', $html);
        $this->assertStringContainsString('storefront-page-container', $html);
        $this->assertStringContainsString('storefront-page-container--narrow', $html);
        $this->assertStringContainsString('storefront-site-header', $html);
        $this->assertStringNotContainsString('storefront-blog-shell', $html);
    }

    public function test_archive_search_is_get_and_pagination_uses_storefront_view(): void
    {
        Post::query()->create([
            'title' => 'Harbor Blog Guide',
            'slug' => 'harbor-blog-guide',
            'excerpt' => 'Find this via search.',
            'content' => 'Harbor body.',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('storefront.cms.posts.index', ['search' => 'Harbor']))
            ->assertOk()
            ->assertSee('Harbor Blog Guide')
            ->assertDontSee('x-admin.search-input', false);

        for ($i = 1; $i <= 13; $i++) {
            Post::query()->create([
                'title' => 'Paged Insight '.$i,
                'slug' => 'paged-insight-'.$i,
                'excerpt' => 'Page filler.',
                'content' => 'Body '.$i,
                'status' => 'published',
                'published_at' => now()->subMinutes($i),
            ]);
        }

        $html = $this->get(route('storefront.cms.posts.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('storefront-pagination', $html);
    }
}
