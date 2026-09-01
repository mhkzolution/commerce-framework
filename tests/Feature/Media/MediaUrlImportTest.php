<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Iam\Database\Seeders\IamSeeder;
use Commerce\Iam\Models\User;
use Commerce\Media\Models\Media;
use Commerce\Media\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaUrlImportTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IamSeeder::class);
        Storage::fake('public');
        config(['media.disk' => 'public']);
    }

    public function test_admin_can_import_media_from_url(): void
    {
        Http::fake([
            'https://example.com/photo.png' => Http::response(base64_decode(self::TINY_PNG), 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.import'), [
                'url' => 'https://example.com/photo.png',
            ])
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'image/png');

        $media = Media::query()->first();
        $this->assertNotNull($media);
        $this->assertSame('image/png', $media->mime_type);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_upload_service_rejects_disallowed_mime_types(): void
    {
        Http::fake([
            'https://example.com/file.exe' => Http::response('MZ', 200, [
                'Content-Type' => 'application/octet-stream',
            ]),
        ]);

        $this->expectException(DomainException::class);

        app(MediaUploadService::class)->upload('https://example.com/file.exe');
    }

    public function test_admin_can_upload_file_via_json(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 120, 120);

        $this->actingAs(User::query()->first())
            ->postJson(route('admin.media.store'), [
                'file' => $file,
            ])
            ->assertCreated()
            ->assertJsonPath('data.media_type', 'image');

        $this->assertSame(1, Media::query()->count());
    }
}
