<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CmsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Commerce\Iam\Database\Seeders\IamSeeder::class);
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
}
