<?php

declare(strict_types=1);

namespace Tests\Feature\Cms;

use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Cms\Models\HomepageSection;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class HomeContentAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_admin_can_create_and_update_hero_banner(): void
    {
        $admin = User::query()->first();
        $imageUuid = (string) Str::uuid();

        $this->actingAs($admin)
            ->post(route('admin.cms.hero-banners.store'), [
                'image_media_uuid' => $imageUuid,
                'sort_order' => 2,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.cms.hero-banners.index'));

        $banner = HeroBanner::query()->first();
        $this->assertNotNull($banner);
        $this->assertSame($imageUuid, $banner->image_media_uuid);
        $this->assertTrue($banner->is_active);
        $this->assertSame(2, $banner->sort_order);

        $this->actingAs($admin)
            ->put(route('admin.cms.hero-banners.update', $banner), [
                'image_media_uuid' => $imageUuid,
                'sort_order' => 5,
                'is_active' => '0',
                'intent' => 'continue',
            ])
            ->assertRedirect(route('admin.cms.hero-banners.edit', $banner));

        $this->assertFalse($banner->fresh()?->is_active);
        $this->assertSame(5, $banner->fresh()?->sort_order);
        $this->assertSame('image', $banner->fresh()?->type);

        $this->actingAs($admin)
            ->get(route('admin.cms.hero-banners.create'))
            ->assertOk()
            ->assertSee('data-file-attach', false)
            ->assertSee('data-layout="banner"', false)
            ->assertSee('data-attach-dropzone', false)
            ->assertSee(__('cms::admin.back_to_hero_banners'))
            ->assertSee(__('cms::admin.save_and_continue'));

        $this->actingAs($admin)
            ->get(route('admin.cms.hero-banners.index'))
            ->assertOk()
            ->assertSee(__('cms::admin.hero_banners'))
            ->assertSee(__('cms::admin.thumbnail'));

        $this->actingAs($admin)
            ->get(route('admin.cms.hero-banners.edit', $banner))
            ->assertOk()
            ->assertSee('data-file-attach', false)
            ->assertSee(__('cms::admin.back_to_hero_banners'));
    }

    public function test_admin_can_create_promotion_banner_and_faq(): void
    {
        $admin = User::query()->first();
        $imageUuid = (string) Str::uuid();

        $this->actingAs($admin)
            ->post(route('admin.cms.promotion-banners.store'), [
                'title' => 'Summer sale',
                'image_media_uuid' => $imageUuid,
                'url' => '/shop',
                'open_in_new_tab' => '1',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.cms.promotion-banners.index'));

        $promo = PromotionBanner::query()->where('title', 'Summer sale')->first();
        $this->assertNotNull($promo);
        $this->assertSame('/shop', $promo->url);
        $this->assertTrue($promo->open_in_new_tab);

        $this->actingAs($admin)
            ->post(route('admin.cms.faq-entries.store'), [
                'question' => 'How do I place an order?',
                'answer' => 'Add items to your cart and checkout.',
                'sort_order' => 1,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.cms.faq-entries.index'));

        $this->assertDatabaseHas('cms_faq_entries', [
            'question' => 'How do I place an order?',
            'is_active' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.cms.promotion-banners.index'))
            ->assertOk()
            ->assertSee('Summer sale')
            ->assertSee(__('cms::admin.thumbnail'));

        $this->actingAs($admin)
            ->get(route('admin.cms.promotion-banners.create'))
            ->assertOk()
            ->assertSee('data-file-attach', false)
            ->assertSee(__('cms::admin.back_to_promotion_banners'));

        $this->actingAs($admin)
            ->get(route('admin.cms.faq-entries.create'))
            ->assertOk()
            ->assertSee(__('cms::admin.back_to_faq_entries'))
            ->assertSee(__('cms::admin.save_and_continue'));

        $this->actingAs($admin)
            ->get(route('admin.cms.faq-entries.index'))
            ->assertOk()
            ->assertSee('How do I place an order?');
    }

    public function test_scheduled_and_inactive_banners_are_not_currently_visible(): void
    {
        $imageUuid = (string) Str::uuid();

        HeroBanner::query()->create([
            'image_media_uuid' => $imageUuid,
            'sort_order' => 1,
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);

        HeroBanner::query()->create([
            'image_media_uuid' => $imageUuid,
            'sort_order' => 2,
            'is_active' => false,
            'starts_at' => now()->subDay(),
        ]);

        $live = HeroBanner::query()->create([
            'image_media_uuid' => $imageUuid,
            'sort_order' => 3,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $visible = HeroBanner::query()->currentlyVisible()->pluck('uuid');

        $this->assertTrue($visible->contains($live->uuid));
        $this->assertCount(1, $visible);
    }

    public function test_inactive_faq_is_hidden_from_visible_query(): void
    {
        FaqEntry::query()->create([
            'question' => 'Visible question?',
            'answer' => 'Yes.',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        FaqEntry::query()->create([
            'question' => 'Hidden question?',
            'answer' => 'No.',
            'sort_order' => 2,
            'is_active' => false,
        ]);

        $visible = FaqEntry::query()->currentlyVisible()->pluck('question');

        $this->assertTrue($visible->contains('Visible question?'));
        $this->assertFalse($visible->contains('Hidden question?'));
    }

    public function test_admin_can_create_video_hero_banner(): void
    {
        $admin = User::query()->first();
        $imageUuid = (string) Str::uuid();
        $videoUuid = (string) Str::uuid();

        $this->actingAs($admin)
            ->post(route('admin.cms.hero-banners.store'), [
                'type' => 'video',
                'image_media_uuid' => $imageUuid,
                'video_media_uuid' => $videoUuid,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.cms.hero-banners.index'));

        $banner = HeroBanner::query()->first();
        $this->assertNotNull($banner);
        $this->assertSame('video', $banner->type);
        $this->assertSame($videoUuid, $banner->video_media_uuid);
    }

    public function test_admin_can_update_homepage_section_layout_and_visibility(): void
    {
        $admin = User::query()->first();

        $this->actingAs($admin)
            ->get(route('admin.cms.homepage.edit'))
            ->assertOk()
            ->assertSee(__('cms::admin.homepage'));

        $sections = HomepageSection::query()->orderBy('sort_order')->get();
        $this->assertCount(6, $sections);

        $payload = [
            'sections' => $sections->values()->map(static function (HomepageSection $section): array {
                $row = [
                    'uuid' => $section->uuid,
                    'layout' => $section->key === HomepageSection::KEY_PROMOTIONS
                        ? HomepageSection::LAYOUT_GRID
                        : $section->layout,
                    'sort_order' => $section->sort_order,
                    'is_active' => $section->key === HomepageSection::KEY_FAQ ? '0' : '1',
                ];

                if ($section->key === HomepageSection::KEY_PROMOTIONS) {
                    $row['columns'] = 2;
                }

                return $row;
            })->all(),
        ];

        $this->actingAs($admin)
            ->put(route('admin.cms.homepage.update'), $payload)
            ->assertRedirect(route('admin.cms.homepage.edit'));

        $promotions = HomepageSection::query()->where('key', HomepageSection::KEY_PROMOTIONS)->first();
        $faq = HomepageSection::query()->where('key', HomepageSection::KEY_FAQ)->first();

        $this->assertSame(HomepageSection::LAYOUT_GRID, $promotions?->layout);
        $this->assertFalse((bool) $faq?->is_active);
    }
}
