<?php

declare(strict_types=1);

namespace Tests\Feature\Media;

use Commerce\Core\Models\Tenant;
use Commerce\Core\Tenant\TenantContext;
use Commerce\Media\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class MediaTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config([
            'media.disk' => 'public',
            'commerce.tenant.enabled' => true,
        ]);
    }

    public function test_media_is_scoped_to_current_tenant(): void
    {
        $tenantA = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'status' => 'active',
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);

        $context->set($tenantA);
        Media::query()->create([
            'filename' => 'a.png',
            'original_filename' => 'tenant-a.png',
            'mime_type' => 'image/png',
            'media_type' => 'image',
            'size' => 100,
            'disk' => 'public',
            'path' => 'media/a.png',
        ]);

        $context->set($tenantB);
        Media::query()->create([
            'filename' => 'b.png',
            'original_filename' => 'tenant-b.png',
            'mime_type' => 'image/png',
            'media_type' => 'image',
            'size' => 100,
            'disk' => 'public',
            'path' => 'media/b.png',
        ]);

        $context->set($tenantA);
        $this->assertSame(1, Media::query()->count());
        $this->assertSame('tenant-a.png', Media::query()->first()?->original_filename);

        $context->set($tenantB);
        $this->assertSame(1, Media::query()->count());
        $this->assertSame('tenant-b.png', Media::query()->first()?->original_filename);
    }
}
