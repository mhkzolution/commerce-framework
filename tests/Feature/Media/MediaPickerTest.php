<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class MediaPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_picker_returns_uploaded_images(): void
    {
        $media = $this->createMedia('picker-test.jpg');

        $this->actingAs(User::query()->first())
            ->getJson(route('admin.media.picker', ['images_only' => 1]))
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $media->uuid)
            ->assertJsonPath('meta.total', 1);
    }

    private function createMedia(string $filename): Media
    {
        $file = UploadedFile::fake()->image($filename, 120, 120);
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
            'alt_text' => null,
        ]);
    }
}
