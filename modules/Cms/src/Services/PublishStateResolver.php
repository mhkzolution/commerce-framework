<?php

declare(strict_types=1);

namespace Commerce\Cms\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Commerce\Cms\DTO\PublishState;
use Illuminate\Validation\ValidationException;

final class PublishStateResolver
{
    /**
     * @throws ValidationException
     */
    public function resolve(
        string $status,
        CarbonInterface|string|null $publishedAt,
        CarbonInterface|string|null $unpublishAt,
    ): PublishState {
        $publishedAt = $this->parseTimestamp($publishedAt);
        $unpublishAt = $this->parseTimestamp($unpublishAt);
        $now = CarbonImmutable::now();

        if ($status === 'archived') {
            return new PublishState('archived', $publishedAt, $unpublishAt);
        }

        if ($status === 'draft') {
            return new PublishState('draft', $publishedAt, $unpublishAt);
        }

        if ($unpublishAt !== null && $unpublishAt->lte($now) && in_array($status, ['published', 'scheduled'], true)) {
            return new PublishState('archived', $publishedAt, $unpublishAt);
        }

        if ($status === 'scheduled') {
            if ($publishedAt === null) {
                throw ValidationException::withMessages([
                    'published_at' => 'Published date is required for scheduled content.',
                ]);
            }

            if ($publishedAt->lte($now)) {
                throw ValidationException::withMessages([
                    'published_at' => 'Scheduled publish date must be in the future.',
                ]);
            }

            if ($unpublishAt !== null && $unpublishAt->lte($publishedAt)) {
                throw ValidationException::withMessages([
                    'unpublish_at' => 'Unpublish date must be after the publish date.',
                ]);
            }

            return new PublishState('scheduled', $publishedAt, $unpublishAt);
        }

        if ($status === 'published') {
            if ($publishedAt !== null && $publishedAt->gt($now)) {
                $status = 'scheduled';
            } elseif ($publishedAt === null) {
                $publishedAt = $now;
            }
        }

        if ($unpublishAt !== null && $publishedAt !== null && $unpublishAt->lte($publishedAt)) {
            throw ValidationException::withMessages([
                'unpublish_at' => 'Unpublish date must be after the publish date.',
            ]);
        }

        return new PublishState($status, $publishedAt, $unpublishAt);
    }

    private function parseTimestamp(CarbonInterface|string|null $value): ?CarbonInterface
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return CarbonImmutable::parse($value);
    }
}
