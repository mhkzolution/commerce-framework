<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Controllers\Admin;

use Commerce\Inventory\Http\Requests\StoreSupplierRequest;
use Commerce\Inventory\Http\Requests\UpdateSupplierRequest;
use Commerce\Inventory\Models\Supplier;
use Commerce\Inventory\Services\SupplierReportService;
use Commerce\Inventory\Support\PurchaseOrderMoney;
use Commerce\Inventory\Support\SupplierReportDateRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SupplierController extends Controller
{
    public function __construct(
        private readonly SupplierReportService $supplierReports,
        private readonly PurchaseOrderMoney $money,
    ) {}

    public function index(): View
    {
        return view('inventory::admin.suppliers.index', [
            'suppliers' => Supplier::query()->orderBy('name')->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('inventory::admin.suppliers.create');
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        Supplier::query()->create($request->validated());

        return redirect()->route('admin.inventory.suppliers.index')->with('status', 'Supplier created.');
    }

    public function show(Supplier $supplier): View
    {
        $range = SupplierReportDateRange::fromRequest();

        return view('inventory::admin.suppliers.show', [
            'supplier' => $supplier,
            'range' => $range,
            'report' => $this->supplierReports->summary($supplier, $range),
            'monthlyOrderSeries' => $this->supplierReports->monthlyOrderSeries($supplier, $range),
            'monthlyUnitsSeries' => $this->supplierReports->monthlyUnitsSeries($supplier, $range),
            'money' => $this->money,
        ]);
    }

    public function export(Supplier $supplier): StreamedResponse
    {
        return $this->supplierReports->exportCsv($supplier, SupplierReportDateRange::fromRequest());
    }

    public function edit(Supplier $supplier): View
    {
        return view('inventory::admin.suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()->route('admin.inventory.suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()->route('admin.inventory.suppliers.index')->with('status', 'Supplier deleted.');
    }
}
