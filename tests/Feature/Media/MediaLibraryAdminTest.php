<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaLibraryAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
    }

    public function test_media_library_index_renders(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('data-media-library', false);
    }

    public function test_media_library_index_renders_item_with_variants(): void
    {
        $media = Media::query()->create([
            'filename' => 'photo.jpg',
            'original_filename' => 'storefront-photo.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'size' => 2048,
            'disk' => 'public',
            'path' => 'media/photo.jpg',
            'width' => 800,
            'height' => 600,
        ]);

        MediaVariant::query()->create([
            'media_id' => $media->id,
            'name' => 'thumbnail',
            'path' => 'media/photo-thumb.jpg',
            'width' => 200,
            'height' => 150,
            'size' => 256,
        ]);

        $this->actingAs(User::query()->first())
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('data-media-library', false)
            ->assertSee('storefront-photo.jpg', false);
    }

    public function test_admin_can_import_media_from_url(): void
    {
        Storage::fake('public');
        Http::fake([
            'https://cdn.example.com/photo.png' => Http::response(
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII='),
                200,
                ['Content-Type' => 'image/png'],
            ),
        ]);

        $this->actingAs(User::query()->first())
            ->post(route('admin.media.import'), [
                'url' => 'https://cdn.example.com/photo.png',
            ])
            ->assertRedirect(route('admin.media.index'));

        $this->assertDatabaseHas('media', [
            'original_filename' => 'photo.png',
            'mime_type' => 'image/png',
        ]);
    }

    public function test_admin_can_bulk_delete_media(): void
    {
        $media = Media::query()->create([
            'filename' => 'bulk.jpg',
            'original_filename' => 'bulk-photo.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'size' => 1024,
            'disk' => 'public',
            'path' => 'media/bulk.jpg',
        ]);

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.bulk-delete'), [
                'uuids' => [$media->uuid],
            ])
            ->assertOk()
            ->assertJsonPath('deleted', 1);

        $this->assertSoftDeleted('media', ['uuid' => $media->uuid]);
    }
}
