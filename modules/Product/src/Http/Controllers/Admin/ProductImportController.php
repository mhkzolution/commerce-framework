<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Product\Export\WooCommerceProductExporter;
use Commerce\Product\Http\Requests\ImportProductCsvRequest;
use Commerce\Product\Import\ProductCsvImportResult;
use Commerce\Product\Import\WooCommerceProductImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ProductImportController extends Controller
{
    public function __construct(
        private readonly WooCommerceProductImporter $importer,
        private readonly WooCommerceProductExporter $exporter,
    ) {}

    public function show(): View
    {
        return view('product::admin.products.import');
    }

    public function store(ImportProductCsvRequest $request): RedirectResponse
    {
        $path = $request->file('csv')?->getRealPath();

        if ($path === false || $path === null) {
            return redirect()
                ->route('admin.products.import.show')
                ->withErrors(['csv' => 'Could not read the uploaded CSV file.']);
        }

        $result = $this->importer->importForAdmin($path);

        return redirect()
            ->route('admin.products.import.show')
            ->with('import_result', $this->serializeResult($result));
    }

    public function export(Request $request): StreamedResponse
    {
        $search = $request->string('search')->toString() ?: null;
        $status = $request->string('status')->toString() ?: null;
        $filename = 'products-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($search, $status): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            $this->exporter->writeHeaders($handle);

            $this->exporter
                ->query($search, $status)
                ->chunkById(100, function ($products) use ($handle): void {
                    $stockLevels = $this->exporter->stockLevelsFor($products);
                    $brandNames = $this->exporter->brandNamesFor($products);
                    $sellerNames = $this->exporter->sellerNamesFor($products);

                    foreach ($products as $product) {
                        foreach ($this->exporter->rowsForProduct($product, $stockLevels, $brandNames, $sellerNames) as $row) {
                            $this->exporter->writeRow($handle, $row);
                        }
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeResult(ProductCsvImportResult $result): array
    {
        return [
            'created' => $result->created,
            'updated' => $result->updated,
            'skipped' => $result->skipped,
            'duplicates' => $result->duplicates,
            'linked_images' => $result->linkedImages,
            'messages' => $result->messages,
            'duplicate_skus' => $result->duplicateSkus,
            'errors' => $result->errors,
        ];
    }
}
