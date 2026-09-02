<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Requests;

use Commerce\Cms\Support\ScheduledPublishing;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

trait ConstrainsScheduledPublishing
{
    protected function prepareForValidation(): void
    {
        if (ScheduledPublishing::enabled()) {
            return;
        }

        $existing = $this->existingPublishable();

        $this->merge([
            'published_at' => $this->timestampForRequest($existing?->published_at),
            'unpublish_at' => $this->timestampForRequest($existing?->unpublish_at),
        ]);
    }

    /**
     * @return list<string>
     */
    protected function allowedStatuses(): array
    {
        $current = $this->existingPublishable()?->getAttribute('status');

        return ScheduledPublishing::allowedStatuses(is_string($current) ? $current : null);
    }

    protected function existingPublishable(): ?Model
    {
        return null;
    }

    private function timestampForRequest(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return is_string($value) ? $value : null;
    }
}
