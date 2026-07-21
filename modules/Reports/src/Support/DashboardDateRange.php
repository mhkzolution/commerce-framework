<?php

declare(strict_types=1);

namespace Commerce\Reports\Support;

use Illuminate\Support\Carbon;

final class DashboardDateRange
{
    public function __construct(
        public Carbon $from,
        public Carbon $to,
        public string $preset,
    ) {}

    public static function fromRequest(): self
    {
        $preset = (string) request()->string('range', '30d');
        $to = Carbon::today()->endOfDay();

        $from = match ($preset) {
            '7d' => $to->copy()->subDays(6)->startOfDay(),
            '90d' => $to->copy()->subDays(89)->startOfDay(),
            'custom' => self::parseDate(request()->string('from')->toString(), $to->copy()->subDays(29)->startOfDay()),
            default => $to->copy()->subDays(29)->startOfDay(),
        };

        if ($preset === 'custom' && request()->filled('to')) {
            $to = self::parseDate(request()->string('to')->toString(), $to)->endOfDay();
        }

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return new self($from, $to, $preset);
    }

    private static function parseDate(string $value, Carbon $fallback): Carbon
    {
        if ($value === '') {
            return $fallback;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }
}
