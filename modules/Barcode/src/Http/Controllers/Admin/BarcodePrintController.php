<?php

declare(strict_types=1);

namespace Commerce\Barcode\Http\Controllers\Admin;

use Commerce\Barcode\Http\Requests\StoreBarcodePrintRequest;
use Commerce\Barcode\Models\BarcodePrintJob;
use Commerce\Barcode\Models\BarcodeTemplate;
use Commerce\Barcode\Services\BarcodePrintJobService;
use Commerce\Barcode\Services\BarcodePrintService;
use Commerce\Barcode\Services\BarcodeQueueItemNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class BarcodePrintController
{
    public function __construct(
        private readonly BarcodePrintJobService $printJobService,
        private readonly BarcodePrintService $printService,
        private readonly BarcodeQueueItemNormalizer $queueItemNormalizer,
    ) {}

    public function store(StoreBarcodePrintRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $template = isset($validated['template_id'])
            ? BarcodeTemplate::query()->find($validated['template_id'])
            : null;

        $settings = $validated['settings'];
        if ($template) {
            $settings['name'] = $template->name;
        }

        $lines = array_map(
            fn (array $line): array => $this->queueItemNormalizer->normalize($line)->toArray(),
            $validated['lines'],
        );

        $job = $this->printJobService->create(
            lines: $lines,
            settings: $settings,
            template: $template,
            userId: (int) $request->user()->id,
        );

        return response()->json([
            'job_uuid' => $job->uuid,
            'print_url' => route('admin.barcode.print.show', $job),
            'pdf_url' => route('admin.barcode.print.pdf', $job),
        ]);
    }

    public function show(BarcodePrintJob $job): View
    {
        return $this->printService->printView($job);
    }

    public function pdf(BarcodePrintJob $job): Response
    {
        return $this->printService->pdfDownload($job);
    }
}
