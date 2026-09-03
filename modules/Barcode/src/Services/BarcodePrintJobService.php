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
     */
    public function create(
        array $lines,
        BarcodeTemplate $template,
        int $userId,
    ): BarcodePrintJob {
        $settings = $template->toSettingsArray();

        $labelCount = array_sum(array_map(
            static fn (array $line): int => max(1, (int) ($line['quantity'] ?? 1)),
            $lines,
        ));

        return BarcodePrintJob::query()->create([
            'barcode_template_id' => $template->id,
            'printed_by_user_id' => $userId,
            'label_count' => $labelCount,
            'paper_size' => (string) ($settings['paper_size_label'] ?? $settings['paper_size'] ?? $template->paper_size),
            'template_name' => (string) ($settings['name'] ?? $template->name),
            'status' => 'queued',
            'settings' => $settings,
            'payload' => ['lines' => $lines],
            'printed_at' => null,
        ]);
    }

    public function markPrinted(BarcodePrintJob $job): void
    {
        $job->forceFill([
            'status' => 'printed',
            'printed_at' => $job->printed_at ?? now(),
        ])->save();
    }

    public function markFailed(BarcodePrintJob $job): void
    {
        $job->forceFill([
            'status' => 'failed',
        ])->save();
    }

    /**
     * @return Collection<int, BarcodePrintJob>
     */
    public function listRecent(int $limit = 50): Collection
    {
        return BarcodePrintJob::query()
            ->with(['printedBy'])
            ->orderByDesc('printed_at')
            ->limit($limit)
            ->get();
    }
}
