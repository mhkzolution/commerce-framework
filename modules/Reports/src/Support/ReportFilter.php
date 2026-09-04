<?php

declare(strict_types=1);

namespace Commerce\Reports\Support;

final class ReportFilter
{
    public function __construct(
        public readonly DashboardDateRange $range,
        public readonly ?string $channel = null,
    ) {}

    public static function fromRequest(): self
    {
        $channel = trim(request()->string('channel')->toString());

        return new self(
            range: DashboardDateRange::fromRequest(),
            channel: $channel !== '' ? $channel : null,
        );
    }

    /**
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        $query = [
            'range' => $this->range->preset,
            'from' => $this->range->from->toDateString(),
            'to' => $this->range->to->toDateString(),
        ];

        if ($this->channel !== null) {
            $query['channel'] = $this->channel;
        }

        return $query;
    }

    public function channelLabel(): string
    {
        if ($this->channel === null) {
            return (string) (config('reports.channels')[''] ?? 'All channels');
        }

        return (string) (config('reports.channels')[$this->channel] ?? $this->channel);
    }
}
