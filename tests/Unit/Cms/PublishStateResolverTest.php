<?php

declare(strict_types=1);

namespace Tests\Unit\Cms;

use Carbon\CarbonImmutable;
use Commerce\Cms\Services\PublishStateResolver;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Features\FeatureService;
use Commerce\Core\Models\SystemFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PublishStateResolverTest extends TestCase
{
    use RefreshDatabase;

    private PublishStateResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-01 12:00:00');
        $this->resolver = new PublishStateResolver;
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_published_with_null_date_sets_published_at_to_now(): void
    {
        $state = $this->resolver->resolve('published', null, null);

        $this->assertSame('published', $state->status);
        $this->assertNotNull($state->publishedAt);
        $this->assertTrue($state->publishedAt->equalTo(CarbonImmutable::parse('2026-09-01 12:00:00')));
        $this->assertNull($state->unpublishAt);
    }

    public function test_published_with_future_date_becomes_scheduled_keeping_datetime(): void
    {
        $publishedAt = '2026-10-01 09:00:00';

        $state = $this->resolver->resolve('published', $publishedAt, null);

        $this->assertSame('scheduled', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertFalse($state->publishedAt?->equalTo(CarbonImmutable::now()));
        $this->assertNull($state->unpublishAt);
    }

    public function test_published_with_past_unpublish_archives_and_keeps_published_at(): void
    {
        $publishedAt = '2026-08-01 09:00:00';
        $unpublishAt = '2026-08-15 09:00:00';

        $state = $this->resolver->resolve('published', $publishedAt, $unpublishAt);

        $this->assertSame('archived', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    public function test_published_future_publish_and_past_unpublish_archives(): void
    {
        $publishedAt = '2026-10-01 09:00:00';
        $unpublishAt = '2026-08-15 09:00:00';

        $state = $this->resolver->resolve('published', $publishedAt, $unpublishAt);

        $this->assertSame('archived', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    public function test_scheduled_future_publish_and_expired_unpublish_archives(): void
    {
        $publishedAt = '2026-10-01';
        $unpublishAt = '2026-09-01';

        $state = $this->resolver->resolve('scheduled', $publishedAt, $unpublishAt);

        $this->assertSame('archived', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    public function test_scheduled_with_null_date_fails_validation_on_published_at(): void
    {
        try {
            $this->resolver->resolve('scheduled', null, null);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('published_at', $exception->errors());
            $this->assertSame(
                ['Published date is required for scheduled content.'],
                $exception->errors()['published_at'],
            );
        }
    }

    public function test_scheduled_with_past_publish_fails_validation(): void
    {
        try {
            $this->resolver->resolve('scheduled', '2026-08-01 09:00:00', null);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('published_at', $exception->errors());
            $this->assertSame(
                ['Scheduled publish date must be in the future.'],
                $exception->errors()['published_at'],
            );
        }
    }

    public function test_scheduled_future_unpublish_not_after_publish_fails_validation(): void
    {
        try {
            $this->resolver->resolve('scheduled', '2026-10-01 09:00:00', '2026-10-01 09:00:00');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('unpublish_at', $exception->errors());
            $this->assertSame(
                ['Unpublish date must be after the publish date.'],
                $exception->errors()['unpublish_at'],
            );
        }
    }

    public function test_draft_keeps_submitted_timestamps(): void
    {
        $publishedAt = '2026-10-01 09:00:00';
        $unpublishAt = '2026-11-01 09:00:00';

        $state = $this->resolver->resolve('draft', $publishedAt, $unpublishAt);

        $this->assertSame('draft', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    public function test_archived_keeps_published_at(): void
    {
        $publishedAt = '2026-08-01 09:00:00';
        $unpublishAt = '2026-08-15 09:00:00';

        $state = $this->resolver->resolve('archived', $publishedAt, $unpublishAt);

        $this->assertSame('archived', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    public function test_empty_string_timestamps_are_treated_as_null(): void
    {
        $state = $this->resolver->resolve('published', '', '');

        $this->assertSame('published', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse('2026-09-01 12:00:00')));
        $this->assertNull($state->unpublishAt);
    }

    public function test_published_with_past_date_stays_published(): void
    {
        $publishedAt = '2026-08-01 09:00:00';

        $state = $this->resolver->resolve('published', $publishedAt, null);

        $this->assertSame('published', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
    }

    public function test_published_future_unpublish_must_be_after_publish_date(): void
    {
        try {
            $this->resolver->resolve('published', '2026-10-01 09:00:00', '2026-09-15 09:00:00');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('unpublish_at', $exception->errors());
            $this->assertSame(
                ['Unpublish date must be after the publish date.'],
                $exception->errors()['unpublish_at'],
            );
        }
    }

    public function test_disabled_flag_keeps_published_with_future_date_instead_of_scheduling(): void
    {
        $this->disableScheduledPublishing();

        $publishedAt = '2026-10-01 09:00:00';
        $state = $this->resolver->resolve('published', $publishedAt, null);

        $this->assertSame('published', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertNull($state->unpublishAt);
    }

    public function test_disabled_flag_does_not_archive_from_expired_unpublish_at(): void
    {
        $this->disableScheduledPublishing();

        $publishedAt = '2026-08-01 09:00:00';
        $unpublishAt = '2026-08-15 09:00:00';
        $state = $this->resolver->resolve('published', $publishedAt, $unpublishAt);

        $this->assertSame('published', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    public function test_disabled_flag_keeps_existing_scheduled_status_and_timestamps(): void
    {
        $this->disableScheduledPublishing();

        $publishedAt = '2026-10-01 09:00:00';
        $unpublishAt = '2026-11-01 09:00:00';
        $state = $this->resolver->resolve('scheduled', $publishedAt, $unpublishAt);

        $this->assertSame('scheduled', $state->status);
        $this->assertTrue($state->publishedAt?->equalTo(CarbonImmutable::parse($publishedAt)));
        $this->assertTrue($state->unpublishAt?->equalTo(CarbonImmutable::parse($unpublishAt)));
    }

    private function disableScheduledPublishing(): void
    {
        $feature = SystemFeature::query()->where('code', 'scheduled-publishing')->firstOrFail();
        app(FeatureService::class)->updateStatus($feature, FeatureStatus::Disabled);
    }
}
