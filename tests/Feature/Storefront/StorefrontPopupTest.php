<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use Commerce\Cms\Models\Popup;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class StorefrontPopupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
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

    public function test_home_renders_published_popups_as_slider(): void
    {
        $imageUuid = (string) Str::uuid();

        Popup::query()->create([
            'title' => 'Image only harvest',
            'slug' => 'sample-image-only',
            'status' => Popup::STATUS_PUBLISHED,
            'priority' => 1,
            'is_active' => true,
            'popup_type' => Popup::TYPE_IMAGE,
            'image_media_uuid' => $imageUuid,
            'closable' => true,
            'show_delay' => 1,
            'timezone' => 'Asia/Bangkok',
        ]);

        Popup::query()->create([
            'title' => 'Full content welcome',
            'slug' => 'sample-full-content',
            'status' => Popup::STATUS_PUBLISHED,
            'priority' => 2,
            'is_active' => true,
            'popup_type' => Popup::TYPE_CONTENT,
            'headline' => 'ข้าวคุณภาพส่งออกจากทุ่งนาไทย',
            'subheadline' => 'เลือกข้าวหอมมะลิจากโรงสีที่คัดสรร',
            'button_text' => 'Shop Now',
            'button_url' => '/shop',
            'image_media_uuid' => $imageUuid,
            'closable' => true,
            'show_delay' => 1,
            'timezone' => 'Asia/Bangkok',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-home-popup', false)
            ->assertSee('data-home-popup-slide', false)
            ->assertSee('data-home-popup-next', false)
            ->assertSee('ข้าวคุณภาพส่งออกจากทุ่งนาไทย', false)
            ->assertSee(__('storefront::storefront.popup_hide_seven_days'));
    }

    public function test_home_hides_draft_popups(): void
    {
        Popup::query()->create([
            'title' => 'Hidden draft',
            'slug' => 'hidden-draft',
            'status' => Popup::STATUS_DRAFT,
            'priority' => 1,
            'is_active' => true,
            'popup_type' => Popup::TYPE_CONTENT,
            'headline' => 'Should not render',
            'closable' => true,
            'timezone' => 'Asia/Bangkok',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-home-popup', false)
            ->assertDontSee('Should not render');
    }

    public function test_home_skips_image_popup_without_media(): void
    {
        Popup::query()->create([
            'title' => 'Broken image overlay',
            'slug' => 'broken-image-overlay',
            'status' => Popup::STATUS_PUBLISHED,
            'priority' => 1,
            'is_active' => true,
            'popup_type' => Popup::TYPE_IMAGE,
            'image_media_uuid' => null,
            'headline' => 'Should not render',
            'closable' => true,
            'timezone' => 'Asia/Bangkok',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('data-home-popup', false)
            ->assertDontSee('Should not render');
    }
}
