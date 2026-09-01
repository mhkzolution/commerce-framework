<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Media\Services\MediaUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RegisterExistingFileTest extends TestCase
{
    use RefreshDatabase;

    private const TINY_JPEG = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAALCAABAAEBAREA/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEABj8Cf//EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAT8hf//Z';

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('wordpress_uploads');
    }

    public function test_it_registers_existing_valid_image_file(): void
    {
        $path = '2021/03/sample.jpg';
        Storage::disk('wordpress_uploads')->put($path, base64_decode(self::TINY_JPEG));

        $media = app(MediaUploadService::class)->registerExistingFile('wordpress_uploads', $path);

        $this->assertNotNull($media);
        $this->assertSame('image/jpeg', $media->mime_type);
    }

    public function test_it_returns_null_for_corrupt_image_file(): void
    {
        $path = '2023/01/corrupt.jpeg';
        Storage::disk('wordpress_uploads')->put($path, ' ');

        $media = app(MediaUploadService::class)->registerExistingFile('wordpress_uploads', $path);

        $this->assertNull($media);
    }
}
