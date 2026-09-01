<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Models\BarcodeTemplate;
use Illuminate\Support\Collection;

final class BarcodePrintJobService
{
    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $settings
     */
    public function create(
        array $lines,
        array $settings,
        ?BarcodeTemplate $template,
        int $userId,
    ): BarcodePrintJob {
        $labelCount = array_sum(array_map(
            static fn (array $line): int => max(1, (int) ($line['quantity'] ?? 1)),
            $lines,
        ));

        return BarcodePrintJob::query()->create([
            'barcode_template_id' => $template?->id,
            'printed_by_user_id' => $userId,
            'label_count' => $labelCount,
            'paper_size' => config("barcode.paper_sizes.{$settings['paper_size']}.label")
                ?? strtoupper((string) ($settings['paper_size'] ?? '')),
            'template_name' => (string) ($settings['name'] ?? $template?->name ?? 'Custom'),
            'status' => 'completed',
            'settings' => $settings,
            'payload' => ['lines' => $lines],
            'printed_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, BarcodePrintJob>
     */
    public function listRecent(int $limit = 50): Collection
    {
        return BarcodePrintJob::query()
            ->with(['printedBy', 'template'])
            ->orderByDesc('printed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{
     *     lines: list<array<string, mixed>>,
     *     settings: array<string, mixed>
     * }
     */
    public function reprintPayload(BarcodePrintJob $job): array
    {
        return [
            'lines' => $job->payload['lines'] ?? [],
            'settings' => $job->settings ?? [],
        ];
    }
}
