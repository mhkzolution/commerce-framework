<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Contracts\Media\MediaUploadServiceInterface;
use Commerce\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ImagePipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_upload_generates_webp_thumbnail_card_and_detail_variants(): void
    {
        $media = $this->uploadJpeg(2000, 1200);

        $names = $media->variants()->pluck('name')->sort()->values()->all();
        $this->assertSame(['card', 'detail', 'thumbnail'], $names);

        $thumbnail = $media->variants()->where('name', 'thumbnail')->first();
        $card = $media->variants()->where('name', 'card')->first();
        $detail = $media->variants()->where('name', 'detail')->first();

        $this->assertNotNull($thumbnail);
        $this->assertNotNull($card);
        $this->assertNotNull($detail);
        $this->assertSame(300, $thumbnail->width);
        $this->assertSame(800, $card->width);
        $this->assertSame(1600, $detail->width);
        $this->assertStringEndsWith('.webp', $thumbnail->path);
        $this->assertStringEndsWith('.webp', $card->path);
        $this->assertStringEndsWith('.webp', $detail->path);

        Storage::disk('public')->assertExists($thumbnail->path);
        Storage::disk('public')->assertExists($card->path);
        Storage::disk('public')->assertExists($detail->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_aliases_resolve_medium_to_card_and_large_to_detail(): void
    {
        $media = $this->uploadJpeg(1800, 1200);
        $query = app(MediaQueryServiceInterface::class);

        $this->assertSame(
            $query->getUrl($media->uuid, 'card'),
            $query->getUrl($media->uuid, 'medium'),
        );
        $this->assertSame(
            $query->getUrl($media->uuid, 'detail'),
            $query->getUrl($media->uuid, 'large'),
        );
        $this->assertNotSame(
            $query->getUrl($media->uuid, 'thumbnail'),
            $query->getUrl($media->uuid, 'card'),
        );
    }

    public function test_srcset_lists_variant_widths(): void
    {
        $media = $this->uploadJpeg(2000, 1000);
        $srcset = app(MediaQueryServiceInterface::class)->getSrcset($media->uuid);

        $this->assertNotNull($srcset);
        $this->assertStringContainsString('300w', $srcset);
        $this->assertStringContainsString('800w', $srcset);
        $this->assertStringContainsString('1600w', $srcset);
        $this->assertStringContainsString('.webp', $srcset);
    }

    public function test_keep_original_false_promotes_detail_to_master_and_deletes_upload(): void
    {
        config(['media.keep_original' => false]);

        $media = $this->uploadJpeg(2000, 1200);

        Storage::disk('public')->assertMissing('media/'.$media->uuid.'.jpg');
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertSame(1600, $media->width);

        $detail = $media->variants()->where('name', 'detail')->first();
        $this->assertNotNull($detail);
        $this->assertSame($detail->path, $media->path);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_does_not_upscale_small_images(): void
    {
        $media = $this->uploadJpeg(400, 300);

        $card = $media->variants()->where('name', 'card')->first();
        $detail = $media->variants()->where('name', 'detail')->first();

        $this->assertNotNull($card);
        $this->assertNotNull($detail);
        $this->assertSame(400, $card->width);
        $this->assertSame(400, $detail->width);
    }

    public function test_skips_svg_and_non_images(): void
    {
        $svg = app(MediaUploadServiceInterface::class)->upload(
            UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>'),
        );
        $pdf = app(MediaUploadServiceInterface::class)->upload(
            UploadedFile::fake()->create('guide.pdf', 20, 'application/pdf'),
        );

        $this->assertSame(0, $svg->variants()->count());
        $this->assertSame(0, $pdf->variants()->count());
    }

    public function test_regenerate_command_creates_missing_variants(): void
    {
        $media = $this->uploadJpeg(1200, 800);
        $media->variants()->delete();

        $this->artisan('media:generate-variants')->assertSuccessful();

        $media->refresh();
        $this->assertSame(3, $media->variants()->count());
    }

    public function test_responsive_image_component_renders_srcset(): void
    {
        $media = $this->uploadJpeg(1600, 900);

        $html = view('components.storefront.media.img', [
            'uuid' => $media->uuid,
            'variant' => 'card',
            'alt' => 'Harbor mug',
            'sizes' => '(min-width: 64rem) 25vw, 50vw',
        ])->render();

        $this->assertStringContainsString('srcset=', $html);
        $this->assertStringContainsString('sizes="(min-width: 64rem) 25vw, 50vw"', $html);
        $this->assertStringContainsString('Harbor mug', $html);
        $this->assertStringContainsString('.webp', $html);
    }

    private function uploadJpeg(int $width, int $height): Media
    {
        $media = app(MediaUploadServiceInterface::class)->upload(
            UploadedFile::fake()->image('photo.jpg', $width, $height),
        );

        $this->assertInstanceOf(Media::class, $media);

        return $media->fresh(['variants']);
    }
}
