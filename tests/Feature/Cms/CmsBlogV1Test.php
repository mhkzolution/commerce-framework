<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Commerce\Cms\Models\Category;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\Tag;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Seo\SeoServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class CmsBlogV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_create_post_with_taxonomy_author_featured_and_seo(): void
    {
        $admin = User::query()->first();

        $category = Category::query()->create([
            'name' => 'Guides',
            'slug' => 'guides',
            'is_active' => true,
        ]);

        $tag = Tag::query()->create([
            'name' => 'Shipping',
            'slug' => 'shipping',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'How we ship',
                'slug' => 'how-we-ship',
                'excerpt' => 'A short intro.',
                'content' => '<h2>Packing</h2><p>We pack with care and ship worldwide every weekday.</p>',
                'status' => 'published',
                'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'category_id' => $category->id,
                'tag_ids' => [$tag->id],
                'author_uuid' => $admin->uuid,
                'is_featured' => '1',
                'seo' => [
                    'meta_title' => 'How we ship | SEO',
                    'meta_description' => 'Learn about our shipping process.',
                ],
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'how-we-ship')->first();

        $this->assertNotNull($post);
        $this->assertTrue($post->is_featured);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame($admin->uuid, $post->author_uuid);
        $this->assertTrue($post->tags->contains('id', $tag->id));
        $this->assertDatabaseHas('seo_entries', [
            'entity_type' => Post::SEO_ENTITY_TYPE,
            'entity_uuid' => $post->uuid,
            'meta_title' => 'How we ship | SEO',
        ]);
    }

    public function test_published_featured_post_appears_on_blog_index_with_reading_time(): void
    {
        $admin = User::query()->first();
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);

        Post::query()->create([
            'title' => 'Launch Day',
            'slug' => 'launch-day',
            'excerpt' => 'We are live.',
            'content' => str_repeat('word ', 400),
            'status' => 'published',
            'published_at' => now()->subHour(),
            'is_featured' => true,
            'category_id' => $category->id,
            'author_uuid' => $admin->uuid,
        ]);

        $this->get(route('storefront.cms.posts.index'))
            ->assertOk()
            ->assertSee('Launch Day')
            ->assertSee('News')
            ->assertSee('min read', false);
    }

    public function test_storefront_post_renders_seo_meta_and_blog_posting_json_ld(): void
    {
        $admin = User::query()->first();
        $category = Category::query()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);
        $tag = Tag::query()->create(['name' => 'Tips', 'slug' => 'tips']);

        $post = Post::query()->create([
            'title' => 'Packing tips',
            'slug' => 'packing-tips',
            'excerpt' => 'How to pack.',
            'content' => '<h2>Boxes</h2><p>Use double-wall cartons for fragile goods.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'category_id' => $category->id,
            'author_uuid' => $admin->uuid,
        ]);
        $post->tags()->attach($tag->id);

        app(SeoServiceInterface::class)->setForEntity(Post::SEO_ENTITY_TYPE, $post->uuid, [
            'meta_title' => 'Packing tips SEO',
            'meta_description' => 'Pack fragile items safely.',
        ]);

        $this->get(route('storefront.cms.posts.show', 'packing-tips'))
            ->assertOk()
            ->assertSee('Packing tips SEO', false)
            ->assertSee('Pack fragile items safely.', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('BlogPosting', false)
            ->assertSee('Guides')
            ->assertSee($admin->name)
            ->assertSee('min read', false);
    }

    public function test_category_tag_and_author_archives_are_available(): void
    {
        $admin = User::query()->first();
        $category = Category::query()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);
        $tag = Tag::query()->create(['name' => 'Tips', 'slug' => 'tips']);

        $post = Post::query()->create([
            'title' => 'Archive post',
            'slug' => 'archive-post',
            'content' => 'Hello world content for archives.',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'category_id' => $category->id,
            'author_uuid' => $admin->uuid,
        ]);
        $post->tags()->attach($tag->id);

        $this->get(route('storefront.cms.categories.show', 'guides'))
            ->assertOk()
            ->assertSee('Archive post')
            ->assertSee('CollectionPage', false);

        $this->get(route('storefront.cms.tags.show', 'tips'))
            ->assertOk()
            ->assertSee('Archive post');

        $this->get(route('storefront.cms.authors.show', $admin->uuid))
            ->assertOk()
            ->assertSee('Archive post')
            ->assertSee('ProfilePage', false);
    }

    public function test_draft_post_is_hidden_on_storefront_but_visible_via_signed_preview(): void
    {
        $admin = User::query()->first();

        $post = Post::query()->create([
            'title' => 'Secret draft',
            'slug' => 'secret-draft',
            'content' => 'Not ready yet.',
            'status' => 'draft',
        ]);

        $this->get(route('storefront.cms.posts.show', 'secret-draft'))
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
            ->assertSee('Secret draft')
            ->assertSee('Not ready yet.')
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertDontSee('rel="canonical"', false);
    }

    public function test_sitemap_includes_published_blog_urls_and_excludes_drafts(): void
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);

        Post::query()->create([
            'title' => 'Live post',
            'slug' => 'live-post',
            'content' => 'Published body.',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'category_id' => $category->id,
        ]);

        Post::query()->create([
            'title' => 'Hidden draft',
            'slug' => 'hidden-draft',
            'content' => 'Draft body.',
            'status' => 'draft',
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('storefront.cms.posts.index'), false)
            ->assertSee(route('storefront.cms.posts.show', 'live-post'), false)
            ->assertSee(route('storefront.cms.categories.show', 'news'), false)
            ->assertDontSee('hidden-draft', false);
    }

    public function test_admin_can_manage_categories_and_tags(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.categories.store'), [
                'name' => 'Journal',
                'slug' => 'journal',
                'is_active' => '1',
                'seo' => ['meta_title' => 'Journal category'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_categories', [
            'name' => 'Journal',
            'slug' => 'journal',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.cms.tags.store'), [
                'name' => 'Matchday',
                'slug' => 'matchday',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_tags', [
            'name' => 'Matchday',
            'slug' => 'matchday',
        ]);
    }

    public function test_duplicate_post_slugs_are_suffixed(): void
    {
        $admin = User::query()->first();
        $payload = [
            'title' => 'My Post',
            'slug' => 'my-post',
            'content' => 'Body',
            'status' => 'published',
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ];

        $this->actingAs($admin)->post(route('admin.cms.posts.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.cms.posts.store'), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.cms.posts.store'), $payload)->assertRedirect();

        $this->assertSame(
            ['my-post', 'my-post-2', 'my-post-3'],
            Post::query()->where('title', 'My Post')->orderBy('id')->pluck('slug')->all(),
        );
    }

    public function test_changing_post_slug_creates_a_301_redirect(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'SEO guide',
                'slug' => 'old-seo-guide',
                'content' => 'Guide body.',
                'status' => 'published',
                'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'old-seo-guide')->first();
        $this->assertNotNull($post);

        $this->actingAs($admin)
            ->put(route('admin.cms.posts.update', $post), [
                'title' => 'SEO guide',
                'slug' => 'ultimate-seo-guide',
                'content' => 'Guide body.',
                'status' => 'published',
                'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_posts', [
            'uuid' => $post->uuid,
            'slug' => 'ultimate-seo-guide',
        ]);

        $this->get('/blog/old-seo-guide')
            ->assertRedirect('/blog/ultimate-seo-guide')
            ->assertStatus(301);

        $this->get('/blog/ultimate-seo-guide')
            ->assertOk()
            ->assertSee('SEO guide');
    }

    public function test_published_post_emits_canonical_and_featured_og_image(): void
    {
        $mediaUuid = '11111111-1111-1111-1111-111111111111';

        $this->app->instance(
            MediaQueryServiceInterface::class,
            new class($mediaUuid) implements MediaQueryServiceInterface
            {
                public function __construct(private readonly string $uuid) {}

                public function findByUuid(string $uuid): ?object
                {
                    return null;
                }

                public function getUrl(string $uuid, ?string $variant = null): ?string
                {
                    return $uuid === $this->uuid ? 'https://cdn.example.test/og-featured.jpg' : null;
                }

                public function findByUuids(array $uuids): array
                {
                    return [];
                }

                public function preload(array $uuids): void {}
            },
        );

        Post::query()->create([
            'title' => 'OG post',
            'slug' => 'og-post',
            'excerpt' => 'Share this.',
            'content' => 'Open graph body.',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'featured_image_media_uuid' => $mediaUuid,
        ]);

        $this->get(route('storefront.cms.posts.show', 'og-post'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('storefront.cms.posts.show', 'og-post').'">', false)
            ->assertSee('<meta property="og:image" content="https://cdn.example.test/og-featured.jpg">', false);
    }

    public function test_blog_archive_is_editorial_and_omits_sidebar(): void
    {
        $admin = User::query()->first();
        $category = Category::query()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);

        Post::query()->create([
            'title' => 'Hero insight',
            'slug' => 'hero-insight',
            'excerpt' => 'A featured story.',
            'content' => str_repeat('word ', 200),
            'status' => 'published',
            'published_at' => now()->subHour(),
            'is_featured' => true,
            'category_id' => $category->id,
            'author_uuid' => $admin->uuid,
        ]);

        $this->get(route('storefront.cms.posts.index'))
            ->assertOk()
            ->assertSee('Insights, guides, tutorials, announcements, and updates.', false)
            ->assertSee('Read Article', false)
            ->assertSee('Latest', false)
            ->assertSee('Popular', false)
            ->assertSee('storefront-blog-shell', false)
            ->assertDontSee('Stay informed', false)
            ->assertDontSee('Recent posts', false);
    }

    public function test_single_post_uses_reading_column_share_and_related_articles(): void
    {
        $admin = User::query()->first();
        $category = Category::query()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);

        Post::query()->create([
            'title' => 'Primary article',
            'slug' => 'primary-article',
            'excerpt' => 'Editorial excerpt.',
            'content' => '<h2>One</h2><p>Body copy for the primary article.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'category_id' => $category->id,
            'author_uuid' => $admin->uuid,
        ]);

        Post::query()->create([
            'title' => 'Related story',
            'slug' => 'related-story',
            'excerpt' => 'Another guide.',
            'content' => 'Related body.',
            'status' => 'published',
            'published_at' => now()->subHours(2),
            'category_id' => $category->id,
        ]);

        $this->get(route('storefront.cms.posts.show', 'primary-article'))
            ->assertOk()
            ->assertSee('Home', false)
            ->assertSee('Blog', false)
            ->assertSee('Guides')
            ->assertSee('Share this article', false)
            ->assertSee('Facebook', false)
            ->assertSee('<span>X</span>', false)
            ->assertSee('LinkedIn', false)
            ->assertSee('Copy Link', false)
            ->assertSee('Related Articles', false)
            ->assertSee('Related story')
            ->assertSee('Browse More Articles', false)
            ->assertDontSee('storefront::storefront.share', false)
            ->assertDontSee('Stay informed', false)
            ->assertDontSee('Recent posts', false);
    }

    public function test_popular_sort_keeps_remaining_featured_posts_ahead_of_regular_posts(): void
    {
        $category = Category::query()->create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);

        Post::query()->create([
            'title' => 'Older featured',
            'slug' => 'older-featured',
            'content' => 'Older featured body.',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'is_featured' => true,
            'category_id' => $category->id,
        ]);

        Post::query()->create([
            'title' => 'Newest featured',
            'slug' => 'newest-featured',
            'content' => 'Newest featured body.',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'is_featured' => true,
            'category_id' => $category->id,
        ]);

        Post::query()->create([
            'title' => 'Regular update',
            'slug' => 'regular-update',
            'content' => 'Regular body.',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'category_id' => $category->id,
        ]);

        $popular = $this->get(route('storefront.cms.posts.index', ['sort' => 'popular']))
            ->assertOk()
            ->assertSee('Newest featured')
            ->assertSee('Older featured')
            ->assertSee('Regular update');

        $this->assertTrue(
            strpos($popular->getContent(), 'Older featured') < strpos($popular->getContent(), 'Regular update'),
        );
    }

    public function test_post_editor_uses_full_width_writing_workspace(): void
    {
        $admin = User::query()->first();
        $this->withoutVite();

        $this->actingAs($admin)
            ->get(route('admin.cms.posts.create'))
            ->assertOk()
            ->assertSee('cms-workspace-form', false)
            ->assertSee('cms-writing-title', false)
            ->assertSee('data-cms-editor-inspector', false)
            ->assertSee('data-media-upload-url="'.route('admin.media.store').'"', false)
            ->assertDontSee('max-w-6xl', false);
    }

    public function test_html_article_body_is_rendered_instead_of_escaped(): void
    {
        Post::query()->create([
            'title' => 'Rendered HTML',
            'slug' => 'rendered-html',
            'excerpt' => 'A visual story.',
            'content' => '<p>Lead paragraph.</p><img src="/media/hero.jpg" alt="Hero photo"><ul><li>First item</li></ul><blockquote><p>A quote</p></blockquote>',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('storefront.cms.posts.show', 'rendered-html'))
            ->assertOk()
            ->assertSee('<p>Lead paragraph.</p>', false)
            ->assertSee('<img src="/media/hero.jpg" alt="Hero photo">', false)
            ->assertSee('<ul>', false)
            ->assertSee('<blockquote>', false)
            ->assertDontSee('&lt;p&gt;', false)
            ->assertDontSee('&lt;img', false)
            ->assertSee('Browse More Articles', false);
    }

    public function test_related_articles_fall_back_from_category_to_tags_then_latest(): void
    {
        $guides = Category::query()->create(['name' => 'Guides', 'slug' => 'guides', 'is_active' => true]);
        $news = Category::query()->create(['name' => 'News', 'slug' => 'news', 'is_active' => true]);
        $tips = Tag::query()->create(['name' => 'Tips', 'slug' => 'tips']);

        $primary = Post::query()->create([
            'title' => 'Primary guide',
            'slug' => 'primary-guide',
            'content' => '<p>Primary body.</p>',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'category_id' => $guides->id,
        ]);
        $primary->tags()->attach($tips->id);

        $sharedTag = Post::query()->create([
            'title' => 'Tagged news',
            'slug' => 'tagged-news',
            'content' => '<p>News body.</p>',
            'status' => 'published',
            'published_at' => now()->subHours(3),
            'category_id' => $news->id,
        ]);
        $sharedTag->tags()->attach($tips->id);

        Post::query()->create([
            'title' => 'Latest unrelated',
            'slug' => 'latest-unrelated',
            'content' => '<p>Other body.</p>',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('storefront.cms.posts.show', 'primary-guide'))
            ->assertOk()
            ->assertSee('Related Articles', false)
            ->assertSee('Tagged news')
            ->assertSee('Latest unrelated');
    }
}
