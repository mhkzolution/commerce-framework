<?php

declare(strict_types=1);

namespace Commerce\Barcode\Http\Controllers\Admin;

use Commerce\Barcode\Services\BarcodeOwnerResolver;
use Commerce\Barcode\Services\BarcodeProductSearchService;
use Commerce\Barcode\Services\BarcodeWorkspaceService;
use Commerce\Contracts\Barcode\BarcodeValueGeneratorInterface;
use Commerce\Core\Barcode\NumericSequenceGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class BarcodeController
{
    public function __construct(
        private readonly BarcodeWorkspaceService $workspaceService,
        private readonly BarcodeProductSearchService $searchService,
        private readonly BarcodeOwnerResolver $ownerResolver,
        private readonly BarcodeValueGeneratorInterface $barcodeGenerator,
    ) {}

    public function index(): View
    {
        $workspaceConfig = $this->workspaceService->config();

        return view('barcode::admin.index', [
            'workspaceConfig' => $workspaceConfig,
            'appConfig' => [
                'routes' => [
                    'search' => route('admin.barcode.search'),
                    'print' => route('admin.barcode.print.store'),
                    'generate' => route('admin.barcode.generate'),
                ],
                'generation' => [
                    'defaultStrategy' => config('barcode.generation.default_strategy', 'random'),
                ],
                'templates' => $workspaceConfig['templates'],
                'defaultTemplate' => $workspaceConfig['default_template'],
                'paperSizes' => $workspaceConfig['paper_sizes'],
                'defaultOwnerName' => $this->ownerResolver->resolveForSeller(null),
                'siteName' => $this->ownerResolver->resolveForSeller(null),
                'sellers' => $workspaceConfig['sellers'] ?? [],
                'i18n' => [
                    'clearConfirm' => __('barcode::admin.queue.clear_confirm'),
                    'page' => __('barcode::admin.preview.page', ['current' => ':current', 'total' => ':total']),
                    'showGuides' => __('barcode::admin.preview.show_guides'),
                    'hideGuides' => __('barcode::admin.preview.hide_guides'),
                    'printError' => __('barcode::admin.preview.print_error'),
                    'queueEmpty' => __('barcode::admin.queue.empty_title'),
                    'skuNotFound' => __('barcode::admin.search.not_found'),
                    'manualTitleRequired' => __('barcode::admin.manual.name_required'),
                    'manualBarcodeRequired' => __('barcode::admin.manual.barcode_required'),
                    'manualBadge' => __('barcode::admin.manual.badge'),
                    'barcodeTooLong' => __('barcode::admin.validation.barcode_too_long', ['max' => 100]),
                    'barcodeInvalidFormat' => __('barcode::admin.validation.barcode_invalid_format'),
                    'ownerRequired' => __('barcode::admin.validation.owner_required'),
                    'quantityInvalid' => __('barcode::admin.validation.quantity_invalid'),
                    'generateError' => __('barcode::admin.manual.generate_error'),
                    'manualSequentialQuantity' => __('barcode::admin.manual.sequential_quantity'),
                    'manualQuantity' => __('barcode::admin.manual.quantity'),
                    'manualSequentialNumericRequired' => __('barcode::admin.manual.sequential_numeric_required'),
                ],
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');

        if ($request->boolean('exact')) {
            $exact = $this->searchService->findBySku($query);

            return response()->json([
                'data' => $exact ? [$exact] : [],
            ]);
        }

        return response()->json([
            'data' => $this->searchService->search($query),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $strategy = (string) $request->query('strategy', config('barcode.generation.default_strategy', 'random'));

        if ($strategy === 'numeric_sequence') {
            $start = (string) $request->query('start', '');
            $count = max(1, min(10000, (int) $request->query('count', 1)));

            try {
                $barcodes = (new NumericSequenceGenerator)->generate($start, $count);
            } catch (\InvalidArgumentException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                ], 422);
            }

            return response()->json([
                'barcodes' => $barcodes,
                'strategy' => $strategy,
            ]);
        }

        return response()->json([
            'barcode' => $this->barcodeGenerator->generate($strategy),
            'strategy' => $strategy,
        ]);
    }
}
