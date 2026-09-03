<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Catalog\Models\Category;
use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Cms\Models\HomepageSection;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Cms\Support\HomeContentCache;
use Commerce\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesPurchasableProduct;
use Tests\TestCase;

final class StorefrontHomePageTest extends TestCase
{
    use CreatesPurchasableProduct;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_homepage_renders_marketplace_sections(): void
    {
        $media = $this->createImageMedia('hero.jpg');
        $promoMedia = $this->createImageMedia('promo.jpg');

        HeroBanner::query()->create([
            'image_media_uuid' => $media->uuid,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        PromotionBanner::query()->create([
            'title' => 'Mall campaign',
            'image_media_uuid' => $promoMedia->uuid,
            'url' => '/shop',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        FaqEntry::query()->create([
            'question' => 'How do I place an order?',
            'answer' => 'Add products to the cart and checkout.',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        Post::query()->create([
            'title' => 'Spring lookbook',
            'slug' => 'spring-lookbook',
            'excerpt' => 'New season notes.',
            'content' => '<p>Editorial notes for the new season.</p>',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $electronics = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
            'position' => 1,
        ]);
        $variant = $this->createPurchasableProduct(price: 49, stock: 4, sku: 'HOME-NEW-001');
        $variant->product->categories()->attach($electronics->id);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee(__('storefront::storefront.home_new_arrivals'))
            ->assertSee(__('storefront::storefront.home_latest_articles'))
            ->assertSee(__('storefront::storefront.home_faq'))
            ->assertSee('How do I place an order?')
            ->assertSee('Spring lookbook')
            ->assertSee($variant->product->name)
            ->assertSee('Electronics')
            ->assertSee('Mall campaign', false)
            ->assertSee('storefront-home-hero', false)
            ->assertSee(route('storefront.shop.index'), false)
            ->assertSee(route('storefront.cms.posts.index'), false)
            ->assertSee(__('storefront::storefront.home_seo_description'), false)
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('<meta property="og:type" content="website"', false)
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('{search_term_string}', false);
    }

    public function test_homepage_hides_inactive_and_future_content(): void
    {
        $media = $this->createImageMedia('later.jpg');

        HeroBanner::query()->create([
            'image_media_uuid' => $media->uuid,
            'sort_order' => 1,
            'is_active' => true,
            'starts_at' => now()->addDay(),
        ]);
        FaqEntry::query()->create([
            'question' => 'Secret support question?',
            'answer' => 'Hidden.',
            'is_active' => false,
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertDontSee('storefront-home-hero', false)
            ->assertDontSee('Secret support question?')
            ->assertDontSee('storefront-home-arrivals', false)
            ->assertDontSee('storefront-home-articles', false);
    }

    public function test_arrivals_endpoint_filters_by_category(): void
    {
        $electronics = Category::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'is_active' => true,
        ]);
        $fashion = Category::query()->create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'is_active' => true,
        ]);

        $phone = $this->createPurchasableProduct(price: 80, stock: 2, sku: 'HOME-ELEC-1');
        $dress = $this->createPurchasableProduct(price: 40, stock: 2, sku: 'HOME-FASH-1');
        $phone->product->categories()->attach($electronics->id);
        $dress->product->categories()->attach($fashion->id);

        $this->getJson(route('storefront.home.arrivals', ['category' => 'electronics']))
            ->assertOk()
            ->assertSee($phone->product->name, false)
            ->assertDontSee($dress->product->name, false);
    }

    public function test_homepage_renders_video_hero_and_promo_grid(): void
    {
        $poster = $this->createImageMedia('poster.jpg');
        $video = $this->createVideoMedia('hero.mp4');
        $promoA = $this->createImageMedia('promo-a.jpg');
        $promoB = $this->createImageMedia('promo-b.jpg');

        HeroBanner::query()->create([
            'type' => HeroBanner::TYPE_VIDEO,
            'image_media_uuid' => $poster->uuid,
            'video_media_uuid' => $video->uuid,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        PromotionBanner::query()->create([
            'title' => 'Grid one',
            'image_media_uuid' => $promoA->uuid,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        PromotionBanner::query()->create([
            'title' => 'Grid two',
            'image_media_uuid' => $promoB->uuid,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        HomepageSection::query()->create([
            'key' => HomepageSection::KEY_PROMOTIONS,
            'type' => HomepageSection::KEY_PROMOTIONS,
            'layout' => HomepageSection::LAYOUT_GRID,
            'sort_order' => 20,
            'is_active' => true,
            'settings' => ['columns' => 2],
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('autoplay', false)
            ->assertSee('type="video/mp4"', false)
            ->assertSee('storefront-home-promos--grid', false)
            ->assertSee('Grid one', false);
    }

    public function test_disabled_homepage_section_is_hidden(): void
    {
        $media = $this->createImageMedia('hidden-hero.jpg');
        HeroBanner::query()->create([
            'image_media_uuid' => $media->uuid,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        HomepageSection::query()->create([
            'key' => HomepageSection::KEY_HERO,
            'type' => HomepageSection::KEY_HERO,
            'layout' => HomepageSection::LAYOUT_FULL_WIDTH,
            'sort_order' => 10,
            'is_active' => false,
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertDontSee('storefront-home-hero', false);
    }

    public function test_homepage_content_cache_is_invalidated_when_banner_changes(): void
    {
        $media = $this->createImageMedia('cached-hero.jpg');
        $banner = HeroBanner::query()->create([
            'image_media_uuid' => $media->uuid,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertSee('storefront-home-hero', false);

        $this->assertNotEmpty(Cache::get(HomeContentCache::key('hero')));

        $banner->update(['is_active' => false]);

        $this->assertNull(Cache::get(HomeContentCache::key('hero')));

        $this->get(route('storefront.home'))
            ->assertOk()
            ->assertDontSee('storefront-home-hero', false);
    }

    private function createImageMedia(string $filename): Media
    {
        $file = UploadedFile::fake()->image($filename, 800, 400);
        $path = $file->store('media', 'public');

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'original_filename' => $filename,
            'mime_type' => 'image/jpeg',
            'size' => $file->getSize(),
            'media_type' => 'image',
        ]);
    }

    private function createVideoMedia(string $filename): Media
    {
        $file = UploadedFile::fake()->create($filename, 200, 'video/mp4');
        $path = $file->store('media', 'public');

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'original_filename' => $filename,
            'mime_type' => 'video/mp4',
            'size' => $file->getSize(),
            'media_type' => 'video',
        ]);
    }
}
