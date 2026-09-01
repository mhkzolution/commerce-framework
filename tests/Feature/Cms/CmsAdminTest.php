<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CmsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_create_published_page(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.pages.store'), [
                'title' => 'About Us',
                'slug' => 'about-us',
                'content' => 'We sell great products.',
                'status' => 'published',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('cms_pages', [
            'title' => 'About Us',
            'slug' => 'about-us',
            'status' => 'published',
        ]);
    }

    public function test_published_page_is_visible_on_storefront(): void
    {
        Page::query()->create([
            'title' => 'Shipping Policy',
            'slug' => 'shipping-policy',
            'content' => 'Free shipping over $50.',
            'status' => 'published',
        ]);

        $this->get(route('storefront.cms.pages.show', 'shipping-policy'))
            ->assertOk()
            ->assertSee('Shipping Policy')
            ->assertSee('Free shipping over $50.');
    }

    public function test_published_post_appears_on_blog(): void
    {
        Post::query()->create([
            'title' => 'Launch Day',
            'slug' => 'launch-day',
            'excerpt' => 'We are live.',
            'content' => 'Full announcement here.',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $this->get(route('storefront.cms.posts.index'))
            ->assertOk()
            ->assertSee('Launch Day');

        $this->get(route('storefront.cms.posts.show', 'launch-day'))
            ->assertOk()
            ->assertSee('Full announcement here.');
    }

    public function test_post_and_page_forms_mount_the_editor_platform(): void
    {
        $admin = User::query()->first();
        $this->withoutVite();

        $this->actingAs($admin)
            ->get(route('admin.cms.posts.create'))
            ->assertOk()
            ->assertSee('data-cms-editor', false)
            ->assertSee('data-cms-editor-toolbar', false)
            ->assertSee('data-cms-editor-inspector', false);

        $this->actingAs($admin)
            ->get(route('admin.cms.pages.create'))
            ->assertOk()
            ->assertSee('data-cms-editor', false);
    }

    public function test_html_content_is_sanitized_on_save(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->post(route('admin.cms.posts.store'), [
                'title' => 'Unsafe',
                'slug' => 'unsafe',
                'content' => '<p onclick="alert(1)">Safe</p><script>alert(1)</script>',
                'status' => 'draft',
            ])
            ->assertRedirect();

        $post = Post::query()->where('slug', 'unsafe')->first();
        $this->assertNotNull($post);
        $this->assertSame('<p>Safe</p>', $post->content);
        $this->assertStringNotContainsString('<script>', (string) $post->content);
    }
}
