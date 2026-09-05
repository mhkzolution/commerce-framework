<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cart\DTO\HomepageBrandingData;
use Commerce\Cart\DTO\HomepageNavigationData;
use Commerce\Cart\Services\StorefrontHomePageService;
use Commerce\Catalog\Models\Category as CatalogCategory;
use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Models\HeroBanner;
use Commerce\Cms\Models\Post;
use Commerce\Cms\Models\PromotionBanner;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Storefront\ProductCardData;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Product\Contracts\ProductServiceInterface;
use Commerce\Product\DTO\CreateProductData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class StorefrontHomePageTest extends TestCase
{
    use RefreshDatabase;

    private const PRODUCT_NAME = 'Harbor Arrival Mug';

    private const PROMO_TITLE = 'Summer landing sale';

    private const FAQ_QUESTION = 'How do I place a homepage order?';

    private const ARTICLE_TITLE = 'Packing for the season';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        config(['app.name' => 'Harbor App']);
        $this->bindMediaUrls();
        $this->seedHomepageContent();
    }

    public function test_home_returns_ok_and_exposes_arrivals_endpoint(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-storefront-home', false)
            ->assertSee(route('storefront.home.arrivals'), false);
    }

    public function test_home_renders_expected_sections(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('storefront-home-hero', false)
            ->assertSee('storefront-home-promos', false)
            ->assertSee('storefront-home-categories', false)
            ->assertSee('storefront-home-arrivals', false)
            ->assertSee('storefront-home-articles', false)
            ->assertSee('storefront-home-faq', false)
            ->assertSee(self::PROMO_TITLE)
            ->assertSee('Mugs')
            ->assertSee(self::PRODUCT_NAME)
            ->assertSee(self::ARTICLE_TITLE)
            ->assertSee(self::FAQ_QUESTION)
            ->assertSee('data-category="mugs"', false);
    }

    public function test_home_page_service_exposes_homepage_dtos(): void
    {
        $payload = app(StorefrontHomePageService::class)->build();

        $this->assertInstanceOf(HomepageBrandingData::class, $payload['branding']);
        $this->assertSame('Harbor App', $payload['branding']->name);

        $this->assertNotEmpty($payload['arrivalProducts']);
        foreach ($payload['arrivalProducts'] as $card) {
            $this->assertInstanceOf(ProductCardData::class, $card);
        }

        $this->assertNotEmpty($payload['arrivalCategories']);
        foreach ($payload['arrivalCategories'] as $tab) {
            $this->assertInstanceOf(HomepageNavigationData::class, $tab);
        }

        $this->assertSame(self::PRODUCT_NAME, $payload['arrivalProducts'][0]->name);
        $this->assertTrue($payload['arrivalProducts'][0]->inStock);
        $this->assertSame('mugs', $payload['arrivalCategories'][0]->slug);
    }

    public function test_home_arrivals_returns_json_html_contract(): void
    {
        $response = $this->getJson(route('storefront.home.arrivals'));

        $response
            ->assertOk()
            ->assertJsonStructure(['html']);

        $html = (string) $response->json('html');
        $this->assertStringContainsString(self::PRODUCT_NAME, $html);
        $this->assertStringContainsString('storefront-product-card', $html);
        $this->assertStringContainsString('12.50', $html);
    }

    public function test_home_arrivals_filters_by_category_slug(): void
    {
        $response = $this->getJson(route('storefront.home.arrivals', ['category' => 'mugs']));

        $response
            ->assertOk()
            ->assertJsonStructure(['html']);

        $this->assertStringContainsString(self::PRODUCT_NAME, (string) $response->json('html'));

        $empty = $this->getJson(route('storefront.home.arrivals', ['category' => 'missing-tab']));
        $empty->assertOk()->assertJsonStructure(['html']);
        $this->assertStringNotContainsString(self::PRODUCT_NAME, (string) $empty->json('html'));
    }

    public function test_home_json_ld_uses_branding_fallback_name(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebPage"', false)
            ->assertSee('"@type":"SearchAction"', false)
            ->assertSee('twitter:card', false)
            ->assertSee('Harbor App');
    }

    private function bindMediaUrls(): void
    {
        $this->app->instance(MediaQueryServiceInterface::class, new class implements MediaQueryServiceInterface
        {
            public function findByUuid(string $uuid): ?object
            {
                return null;
            }

            public function findByUuids(array $uuids): array
            {
                return [];
            }

            public function getUrl(string $uuid, ?string $variant = null): ?string
            {
                return 'https://cdn.test/'.$uuid.'.jpg';
            }

            public function getSrcset(string $uuid): ?string
            {
                return 'https://cdn.test/'.$uuid.'.jpg 800w';
            }
        });
    }

    private function seedHomepageContent(): void
    {
        $heroUuid = (string) Str::uuid();
        $promoUuid = (string) Str::uuid();

        HeroBanner::query()->create([
            'type' => HeroBanner::TYPE_IMAGE,
            'image_media_uuid' => $heroUuid,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        PromotionBanner::query()->create([
            'title' => self::PROMO_TITLE,
            'image_media_uuid' => $promoUuid,
            'url' => '/shop',
            'open_in_new_tab' => false,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        FaqEntry::query()->create([
            'question' => self::FAQ_QUESTION,
            'answer' => 'Add items to the cart and continue to checkout.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        Post::query()->create([
            'title' => self::ARTICLE_TITLE,
            'slug' => 'packing-for-the-season',
            'excerpt' => 'How we pack seasonal orders.',
            'content' => 'Packing notes for storefront home.',
            'status' => 'published',
            'published_at' => now()->subHour(),
        ]);

        $category = CatalogCategory::query()->create([
            'name' => 'Mugs',
            'slug' => 'mugs',
            'is_active' => true,
            'position' => 1,
        ]);

        $product = app(ProductServiceInterface::class)->create(new CreateProductData(
            name: self::PRODUCT_NAME,
            status: 'published',
            visibility: 'public',
            sku: 'MUG-HOME-1',
            price: 1250,
            categoryIds: [$category->id],
        ));

        $variant = $product->defaultVariant();
        $this->assertNotNull($variant);
        app(InventoryServiceInterface::class)->receive($variant->uuid, 8);
    }
}
