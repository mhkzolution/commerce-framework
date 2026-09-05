<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class CmsEditorQaTest extends TestCase
{
    use RefreshDatabase;

    private const TIPTAP_HTML = <<<'HTML'
<h2>Heading</h2>
<p>Paragraph with <strong>Bold</strong> and <em>Italic</em> and <a target="_blank" rel="noopener noreferrer" href="https://example.com">Link</a>.</p>
<p><img src="/media/hero.jpg" alt="Library hero"></p>
<blockquote><p>A quoted line.</p></blockquote>
<pre><code>const ready = true;</code></pre>
<table><tbody><tr><th colspan="1" rowspan="1"><p>A</p></th><th colspan="1" rowspan="1"><p>B</p></th></tr><tr><td colspan="1" rowspan="1"><p>1</p></td><td colspan="1" rowspan="1"><p>2</p></td></tr></tbody></table>
HTML;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        $this->withoutVite();
    }

    public function test_post_content_round_trips_through_save_and_edit(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Editor QA Post',
                'slug' => 'editor-qa-post',
                'excerpt' => 'Round trip.',
                'content' => self::TIPTAP_HTML,
                'status' => 'published',
                'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'editor-qa-post')->first();
        $this->assertNotNull($post);

        foreach (['<h2>Heading</h2>', '<strong>Bold</strong>', '<em>Italic</em>', 'href="https://example.com"', 'src="/media/hero.jpg"', '<blockquote>', '<pre>', '<table>', '<tbody>', '<th', '<td'] as $fragment) {
            $this->assertStringContainsString($fragment, (string) $post->content);
        }

        $edit = $this->actingAs($admin)
            ->get(route('admin.cms.posts.edit', $post))
            ->assertOk()
            ->assertSee('data-cms-editor', false)
            ->assertSee('data-media-picker-url="'.route('admin.media.picker').'"', false);

        $edit->assertSee(e('<table>'), false)
            ->assertSee(e('<strong>Bold</strong>'), false)
            ->assertSee('/media/hero.jpg', false);
    }

    public function test_page_content_round_trips_through_save_and_edit(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.pages.store'), [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => self::TIPTAP_HTML,
                'status' => 'published',
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'about-us')->first();
        $this->assertNotNull($page);
        $this->assertStringContainsString('<h2>Heading</h2>', (string) $page->content);
        $this->assertStringContainsString('src="/media/hero.jpg"', (string) $page->content);
        $this->assertStringContainsString('<table>', (string) $page->content);

        $this->actingAs($admin)
            ->get(route('admin.cms.pages.edit', $page))
            ->assertOk()
            ->assertSee('data-cms-editor', false)
            ->assertSee(e('<table>'), false)
            ->assertSee('/media/hero.jpg', false);

        $this->get(route('storefront.cms.pages.show', 'about-us'))
            ->assertOk()
            ->assertSee('About Us')
            ->assertSee('Heading', false);
    }

    public function test_editor_form_uses_commerce_framework_media_picker_route(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->getJson(route('admin.media.picker', ['images_only' => 1]))
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);

        $this->actingAs($admin)
            ->get(route('admin.cms.posts.create'))
            ->assertOk()
            ->assertSee(route('admin.media.picker'), false);
    }

    public function test_sanitizer_strips_script_and_onerror_on_post_and_page_save(): void
    {
        $admin = User::query()->first();
        $dirty = '<p>Hello</p><script>alert(1)</script><img src="x" onerror="alert(1)">';

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Dirty post',
                'slug' => 'dirty-post',
                'content' => $dirty,
                'status' => 'draft',
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'dirty-post')->first();
        $this->assertNotNull($post);
        $this->assertStringNotContainsString('<script>', (string) $post->content);
        $this->assertStringNotContainsString('onerror', (string) $post->content);
        $this->assertStringContainsString('<p>Hello</p>', (string) $post->content);

        $this->actingAs($admin)
            ->post(route('admin.cms.pages.store'), [
                'title' => 'Dirty page',
                'slug' => 'dirty-page',
                'content' => $dirty,
                'status' => 'draft',
            ])
            ->assertRedirect();

        $page = Page::query()->where('slug', 'about-us')->first()
            ?? Page::query()->where('slug', 'dirty-page')->first();
        $this->assertNotNull($page);
        $this->assertStringNotContainsString('<script>', (string) $page->content);
        $this->assertStringNotContainsString('onerror', (string) $page->content);
    }

    public function test_published_editor_post_still_emits_canonical_og_and_json_ld(): void
    {
        $mediaUuid = '22222222-2222-2222-2222-222222222222';

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
                    return $uuid === $this->uuid ? 'https://cdn.example.test/qa-og.jpg' : null;
                }

                public function getSrcset(string $uuid): ?string
                {
                    return null;
                }

                public function findByUuids(array $uuids): array
                {
                    return [];
                }

                public function preload(array $uuids): void {}
            },
        );

        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'SEO still works',
                'slug' => 'seo-still-works',
                'excerpt' => 'Editor must not break SEO.',
                'content' => self::TIPTAP_HTML,
                'status' => 'published',
                'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'featured_image_media_uuid' => $mediaUuid,
            ])
            ->assertRedirect();

        $this->get(route('storefront.cms.posts.show', 'seo-still-works'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('storefront.cms.posts.show', 'seo-still-works').'">', false)
            ->assertSee('<meta property="og:image" content="https://cdn.example.test/qa-og.jpg">', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('BlogPosting', false)
            ->assertSee('Heading', false)
            ->assertSee('Library hero', false);
    }

    public function test_draft_preview_stays_noindex_without_blog_posting(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Draft editor post',
                'slug' => 'draft-editor-post',
                'content' => self::TIPTAP_HTML,
                'status' => 'draft',
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'draft-editor-post')->first();
        $this->assertNotNull($post);

        $this->get(route('storefront.cms.posts.show', 'draft-editor-post'))
            ->assertNotFound();

        $signed = URL::temporarySignedRoute(
            'storefront.cms.posts.preview',
            now()->addHour(),
            ['post' => $post->uuid],
        );

        $this->actingAs($admin)
            ->get($signed)
            ->assertOk()
            ->assertSee('Draft editor post')
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false)
            ->assertDontSee('BlogPosting', false)
            ->assertDontSee('rel="canonical"', false);
    }
}
