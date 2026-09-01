<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Media\Models\MediaFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_library_page_renders_dam_shell(): void
    {
        $this->actingAs(User::query()->first())
            ->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('data-media-library', false)
            ->assertSee(__('media::admin.upload'))
            ->assertSee(__('media::admin.all_files'))
            ->assertSee(__('media::admin.search_placeholder'))
            ->assertSee(__('media::admin.select_all_loaded', ['count' => 0]));
    }

    public function test_library_json_lists_files_from_all_folders(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Products']);
        $root = $this->createMedia('root.jpg');
        $nested = $this->createMedia('product.jpg', $folder->id);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index'))
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonFragment(['uuid' => $root->uuid])
            ->assertJsonFragment(['uuid' => $nested->uuid]);
    }

    public function test_library_filters_unfiled_and_folder(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Products']);
        $root = $this->createMedia('root.jpg');
        $nested = $this->createMedia('product.jpg', $folder->id);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['folder' => 'unfiled']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $root->uuid);

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['folder' => $folder->uuid]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $nested->uuid);
    }

    public function test_library_filters_by_type_and_searches_uuid(): void
    {
        $image = $this->createMedia('hero.jpg');
        $this->createMedia('guide.pdf', mime: 'application/pdf', mediaType: 'document');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['type' => 'pdfs']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.original_filename', 'guide.pdf');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['search' => $image->uuid]))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $image->uuid);
    }

    public function test_library_searches_mime_type_and_folder_name(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'Running Shoes']);
        $this->createMedia('hero.jpg');
        $shoe = $this->createMedia('nike-air.png', $folder->id, 'image/png');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['search' => 'image/jpeg']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.original_filename', 'hero.jpg');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.index', ['search' => 'Shoes']))
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $shoe->uuid);
    }

    public function test_show_update_and_bulk_actions(): void
    {
        $folder = MediaFolder::query()->create(['name' => 'SEO']);
        $first = $this->createMedia('one.jpg');
        $second = $this->createMedia('two.jpg');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.show', $first))
            ->assertOk()
            ->assertJsonPath('data.uuid', $first->uuid);

        $this->actingAs(User::query()->first())
            ->putJson(route('admin.media.update', $first), [
                'alt_text' => 'Hero image',
                'folder_uuid' => $folder->uuid,
            ])
            ->assertOk()
            ->assertJsonPath('data.alt_text', 'Hero image')
            ->assertJsonPath('data.folder_uuid', $folder->uuid);

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.bulk-move'), [
                'uuids' => [$second->uuid],
                'folder_uuid' => $folder->uuid,
            ])
            ->assertOk()
            ->assertJsonPath('moved', 1);

        $this->assertSame($folder->id, $second->fresh()->folder_id);

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.bulk-delete'), [
                'uuids' => [$first->uuid, $second->uuid],
            ])
            ->assertOk()
            ->assertJsonPath('deleted', 2);

        $this->assertSame(0, Media::query()->count());
    }

    private function createMedia(
        string $filename,
        ?int $folderId = null,
        string $mime = 'image/jpeg',
        string $mediaType = 'image',
    ): Media {
        $file = str_ends_with($filename, '.pdf')
            ? UploadedFile::fake()->create($filename, 20, $mime)
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
            'size' => $file->getSize(),
            'media_type' => $mediaType,
            'alt_text' => null,
        ]);
    }
}
