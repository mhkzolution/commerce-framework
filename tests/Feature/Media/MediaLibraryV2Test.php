<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaFolder;
use Commerce\Media\Models\MediaTag;
use Commerce\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MediaLibraryV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_library_shows_insights_and_v2_shell(): void
    {
        $this->createMedia('hero.jpg', size: 2048);
        $this->createMedia('guide.pdf', mime: 'application/pdf', mediaType: 'document', size: 4096);

        $html = $this->actingAs(User::query()->first())
            ->get(route('admin.media.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-media-insights', $html);
        $this->assertStringContainsString('data-view-toggle', $html);
        $this->assertStringContainsString('data-media-list', $html);
        $this->assertStringContainsString(__('media::admin.total_assets'), $html);

        $json = $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index'))
            ->assertOk()
            ->assertJsonPath('meta.insights.total', 2)
            ->assertJsonPath('meta.insights.images', 1)
            ->assertJsonPath('meta.insights.documents', 1)
            ->json();

        $this->assertGreaterThan(0, $json['meta']['insights']['storage_bytes']);
    }

    public function test_search_includes_tags_caption_and_filters_by_size(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Campaign']);
        $banner = $this->createMedia('summer-sale.jpg', $folder->id, size: 200000);
        $banner->update(['caption' => 'Homepage hero', 'alt_text' => 'Sale banner']);
        $banner->tags()->attach(MediaTag::query()->where('slug', 'banners')->value('id'));
        $this->createMedia('tiny.jpg', size: 80);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['search' => 'Banners']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $banner->uuid);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['search' => 'Homepage hero']))
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $banner->uuid);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['size' => 'small']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.original_filename', 'tiny.jpg');
    }

    public function test_list_sorts_by_name_and_type_filter_documents(): void
    {
        $this->createMedia('zeta.jpg');
        $this->createMedia('alpha.jpg');
        $this->createMedia('manual.pdf', mime: 'application/pdf', mediaType: 'document');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertOk()
            ->assertJsonPath('data.0.original_filename', 'alpha.jpg');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['type' => 'documents']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.original_filename', 'manual.pdf');
    }

    public function test_can_update_alt_caption_tags_and_show_usage(): void
    {
        $media = $this->createMedia('mug.jpg');
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Coffee Mug',
            'slug' => 'coffee-mug',
            'status' => 'draft',
            'visibility' => 'public',
        ]);
        $product->media()->create([
            'media_uuid' => $media->uuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->actingAs(User::query()->first())
            ->putJson(route('admin.media.update', $media), [
                'alt_text' => 'White ceramic mug',
                'caption' => 'Hero mug',
                'description' => 'Used on the homepage',
                'tags' => ['Products', 'Homepage'],
            ])
            ->assertOk()
            ->assertJsonPath('data.alt_text', 'White ceramic mug')
            ->assertJsonPath('data.caption', 'Hero mug');

        $this->assertDatabaseHas('media', [
            'uuid' => $media->uuid,
            'caption' => 'Hero mug',
        ]);

        $show = $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.show', $media))
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($show['usage']);
        $this->assertSame('Product', $show['usage'][0]['label']);
        $this->assertSame('Coffee Mug', $show['usage'][0]['title']);
    }

    public function test_delete_warns_when_media_is_used_and_replace_keeps_uuid(): void
    {
        $media = $this->createMedia('used.jpg');
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Used Product',
            'slug' => 'used-product',
            'status' => 'draft',
            'visibility' => 'public',
        ]);
        $product->media()->create([
            'media_uuid' => $media->uuid,
            'position' => 0,
            'is_primary' => true,
        ]);

        $this->actingAs(User::query()->first())
            ->deleteJson(route('admin.media.destroy', $media))
            ->assertStatus(409)
            ->assertJsonPath('message', 'This file is in use and cannot be deleted.');

        $this->assertDatabaseHas('media', ['uuid' => $media->uuid, 'deleted_at' => null]);

        $replacement = UploadedFile::fake()->image('new-hero.jpg', 400, 400);
        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.replace', $media), [
                'file' => $replacement,
            ])
            ->assertOk()
            ->assertJsonPath('data.uuid', $media->uuid)
            ->assertJsonPath('data.original_filename', 'new-hero.jpg');

        $this->assertSame($media->uuid, $media->fresh()->uuid);
    }

    public function test_bulk_tag_and_picker_supports_folders_and_recent(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Homepage']);
        $one = $this->createMedia('one.jpg', $folder->id);
        $two = $this->createMedia('two.jpg');

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.bulk-tag'), [
                'uuids' => [$one->uuid, $two->uuid],
                'tags' => ['Seasonal'],
            ])
            ->assertOk()
            ->assertJsonPath('tagged', 2);

        $this->assertTrue($one->fresh()->tags->contains('slug', 'seasonal'));

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.picker', ['folder' => $folder->uuid, 'images_only' => 1]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $one->uuid)
            ->assertJsonFragment(['name' => 'Homepage']);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.picker', ['recent' => 1]))
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    private function createMedia(
        string $filename,
        ?int $folderId = null,
        string $mime = 'image/jpeg',
        string $mediaType = 'image',
        int $size = 1200,
    ): Media {
        $file = str_ends_with($filename, '.pdf')
            ? UploadedFile::fake()->create($filename, (int) max(1, $size / 1024), $mime)
            : UploadedFile::fake()->image($filename, 120, 120);
        $path = $file->store('media', 'public');

        return Media::query()->create([
            'uuid' => (string) Str::uuid(),
            'folder_id' => $folderId,
            'disk' => 'public',
            'path' => $path,
            'filename' => basename($path),
            'original_filename' => $filename,
            'mime_type' => $mime,
            'size' => $size,
            'media_type' => $mediaType,
            'width' => $mediaType === 'image' ? 120 : null,
            'height' => $mediaType === 'image' ? 120 : null,
        ]);
    }
}
