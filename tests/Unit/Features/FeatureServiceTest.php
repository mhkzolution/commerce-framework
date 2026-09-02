<?php

declare(strict_types=1);

namespace Tests\Unit\Features;

use Commerce\Core\Database\Seeders\SystemFeatureSeeder;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Models\SystemFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FeatureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_features_are_enabled_by_default(): void
    {
        $this->assertSame(
            ['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'],
            SystemFeature::query()->orderBy('sort_order')->orderBy('name')->pluck('code')->all(),
        );

        foreach (['scheduled-publishing', 'advanced-seo', 'ai-writer', 'review-monitor'] as $code) {
            $row = SystemFeature::query()->where('code', $code)->first();
            $this->assertNotNull($row);
            $this->assertSame(FeatureStatus::Enabled, $row->status);
            $this->assertFalse($row->is_core);
        }
    }

    public function test_seeder_does_not_overwrite_existing_status(): void
    {
        $feature = SystemFeature::query()->where('code', 'scheduled-publishing')->firstOrFail();
        $feature->update([
            'name' => 'Stale name',
            'description' => 'Stale description',
            'status' => FeatureStatus::Disabled,
        ]);

        $this->seed(SystemFeatureSeeder::class);

        $feature->refresh();

        $this->assertSame(FeatureStatus::Disabled, $feature->status);
        $this->assertSame('Scheduled Publishing', $feature->name);
        $this->assertSame('Schedule CMS content for future publish', $feature->description);
    }
}
