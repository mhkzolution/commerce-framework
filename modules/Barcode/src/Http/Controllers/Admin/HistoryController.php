<?php

declare(strict_types=1);

namespace Commerce\Barcode\Http\Controllers\Admin;

use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Services\BarcodePrintJobService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class HistoryController
{
    public function __construct(
        private readonly BarcodePrintJobService $printJobService,
    ) {}

    public function index(): View
    {
        $jobs = $this->printJobService->listRecent();

        return view('barcode::admin.history.index', [
            'jobs' => $jobs->map(static function (BarcodePrintJob $job): array {
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'printed_at' => $job->printed_at?->format('Y-m-d H:i') ?? '—',
                    'printed_by' => $job->printedBy?->name ?? '—',
                    'label_count' => $job->label_count,
                    'template' => $job->template_name ?? '—',
                    'paper_size' => $job->paper_size ?? '—',
                    'status' => $job->status,
                ];
            }),
        ]);
    }

    public function show(BarcodePrintJob $job): View
    {
        return view('barcode::admin.history.show', [
            'job' => $job->load(['printedBy', 'template']),
        ]);
    }

    public function reprint(BarcodePrintJob $job): JsonResponse
    {
        return response()->json($this->printJobService->reprintPayload($job));
    }
}
