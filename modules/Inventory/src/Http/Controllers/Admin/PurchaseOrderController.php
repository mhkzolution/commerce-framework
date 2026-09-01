<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Controllers\Admin;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Inventory\Http\Requests\CancelPurchaseOrderRequest;
use Commerce\Inventory\Http\Requests\ReceivePurchaseOrderLineRequest;
use Commerce\Inventory\Http\Requests\SendPurchaseOrderEmailRequest;
use Commerce\Inventory\Http\Requests\StorePurchaseOrderRequest;
use Commerce\Inventory\Models\PurchaseOrder;
use Commerce\Inventory\Models\PurchaseOrderLine;
use Commerce\Inventory\Models\Supplier;
use Commerce\Inventory\Services\InventoryQueryService;
use Commerce\Inventory\Services\PurchaseOrderAnalyticsService;
use Commerce\Inventory\Services\PurchaseOrderEmailService;
use Commerce\Inventory\Services\PurchaseOrderExportService;
use Commerce\Inventory\Services\PurchaseOrderFailedJobService;
use Commerce\Inventory\Services\PurchaseOrderPdfService;
use Commerce\Inventory\Services\PurchaseOrderService;
use Commerce\Inventory\Support\PurchaseOrderMoney;
use Commerce\Inventory\Support\SupplierReportDateRange;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PurchaseOrderController extends Controller
{
    public function __construct(
        private readonly PurchaseOrderService $purchaseOrders,
        private readonly PurchaseOrderExportService $purchaseOrderExport,
        private readonly PurchaseOrderAnalyticsService $purchaseOrderAnalytics,
        private readonly PurchaseOrderPdfService $purchaseOrderPdf,
        private readonly PurchaseOrderEmailService $purchaseOrderEmail,
        private readonly PurchaseOrderFailedJobService $purchaseOrderFailedJobs,
        private readonly PurchaseOrderMoney $money,
        private readonly InventoryQueryService $inventoryQuery,
        private readonly AuthorizationServiceInterface $authorization,
    ) {}

    public function index(): View
    {
        $supplierId = request()->integer('supplier_id') ?: null;
        $status = request()->string('status')->toString();

        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->withCount('lines')
            ->when($supplierId, static fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($status !== '', static fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('inventory::admin.purchase-orders.index', [
            'orders' => $orders,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'filterSupplierId' => $supplierId,
            'filterStatus' => $status,
            'canManagePurchaseOrders' => $this->authorization->can(auth()->user(), 'inventory.purchase_order.manage'),
            'failedEmailCount' => $this->purchaseOrderFailedJobs->count(),
        ]);
    }

    public function export(): StreamedResponse
    {
        $supplierId = request()->integer('supplier_id') ?: null;
        $status = request()->string('status')->toString();

        return $this->purchaseOrderExport->exportCsv(
            supplierId: $supplierId,
            status: $status !== '' ? $status : null,
        );
    }

    public function analytics(): View
    {
        $range = SupplierReportDateRange::fromRequest();

        return view('inventory::admin.purchase-orders.analytics', [
            'range' => $range,
            'summary' => $this->purchaseOrderAnalytics->summary($range),
            'ordersByStatus' => $this->purchaseOrderAnalytics->ordersByStatus($range),
            'ordersSeries' => $this->purchaseOrderAnalytics->ordersSeries($range),
            'unitsSeries' => $this->purchaseOrderAnalytics->monthlyUnitsSeries($range),
            'topSuppliers' => $this->purchaseOrderAnalytics->topSuppliers($range),
            'money' => $this->money,
        ]);
    }

    public function analyticsExport(): StreamedResponse
    {
        return $this->purchaseOrderAnalytics->exportCsv(SupplierReportDateRange::fromRequest());
    }

    public function create(): View
    {
        return view('inventory::admin.purchase-orders.create', [
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'autoEmailSupplier' => (bool) config('inventory.purchase_order.auto_email_supplier', false),
            'currencies' => $this->money->activeCurrencyCodes(),
            'defaultCurrency' => $this->money->defaultCurrency(),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $supplierId = $request->validated('supplier_id');
        $supplierName = $request->validated('supplier_name');

        if ($supplierId !== null && $supplierName === null) {
            $supplierName = Supplier::query()->find($supplierId)?->name;
        }

        $order = $this->purchaseOrders->create(
            reference: $request->validated('reference'),
            lines: $request->validated('lines'),
            supplierName: $supplierName,
            expectedAt: $request->validated('expected_at'),
            notes: $request->validated('notes'),
            supplierId: $supplierId !== null ? (int) $supplierId : null,
            currency: $request->validated('currency'),
        );

        $order->load(['lines', 'supplier']);

        $email = $request->validated('supplier_email')
            ?? ($supplierId !== null ? Supplier::query()->find($supplierId)?->email : null);

        $status = 'Purchase order created.';

        if ($request->boolean('send_email') && $this->purchaseOrderEmail->sendIfPossible(
            $order,
            $email,
            $this->variantContextForOrder($order),
        )) {
            $status = 'Purchase order created and emailed to '.$email.'.';
        }

        return redirect()
            ->route('admin.inventory.purchase-orders.show', $order)
            ->with('status', $status);
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['lines', 'supplier']);

        return view('inventory::admin.purchase-orders.show', [
            'order' => $purchaseOrder,
            'canManagePurchaseOrders' => $this->authorization->can(auth()->user(), 'inventory.purchase_order.manage'),
            'variantContext' => $this->variantContextForOrder($purchaseOrder),
            'orderValueOrdered' => $purchaseOrder->lines->sum(static fn (PurchaseOrderLine $line) => $line->orderedValue()),
            'orderValueReceived' => $purchaseOrder->lines->sum(static fn (PurchaseOrderLine $line) => $line->receivedValue()),
            'money' => $this->money,
        ]);
    }

    public function print(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['lines', 'supplier']);

        return view('inventory::admin.purchase-orders.print', [
            'order' => $purchaseOrder,
            'variantContext' => $this->variantContextForOrder($purchaseOrder),
            'money' => $this->money,
        ]);
    }

    public function pdf(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['lines', 'supplier']);

        return $this->purchaseOrderPdf->download(
            $purchaseOrder,
            $this->variantContextForOrder($purchaseOrder),
        );
    }

    public function email(SendPurchaseOrderEmailRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->load(['lines', 'supplier']);

        $email = $request->validated('email') ?? $purchaseOrder->supplier?->email ?? '';

        $this->purchaseOrderEmail->send(
            $purchaseOrder,
            $email,
            $this->variantContextForOrder($purchaseOrder),
        );

        return redirect()
            ->route('admin.inventory.purchase-orders.show', $purchaseOrder)
            ->with('status', 'Purchase order emailed to '.$email.'.');
    }

    public function cancel(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->purchaseOrders->cancel($purchaseOrder);

        return redirect()
            ->route('admin.inventory.purchase-orders.show', $purchaseOrder)
            ->with('status', 'Purchase order cancelled.');
    }

    public function cancelLine(CancelPurchaseOrderRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderLine $line): RedirectResponse
    {
        abort_if($line->purchase_order_id !== $purchaseOrder->id, 404);

        $this->purchaseOrders->cancelLine($line->id);

        return redirect()
            ->route('admin.inventory.purchase-orders.show', $purchaseOrder)
            ->with('status', 'Line cancelled.');
    }

    public function receive(ReceivePurchaseOrderLineRequest $request, PurchaseOrder $purchaseOrder, PurchaseOrderLine $line): RedirectResponse
    {
        abort_if($line->purchase_order_id !== $purchaseOrder->id, 404);

        $this->purchaseOrders->receiveLine($line->id, (int) $request->validated('quantity'));

        return redirect()
            ->route('admin.inventory.purchase-orders.show', $purchaseOrder)
            ->with('status', 'Stock received.');
    }

    /**
     * @return array<string, array{variant: mixed, product_name: string|null}>
     */
    private function variantContextForOrder(PurchaseOrder $purchaseOrder): array
    {
        return $this->inventoryQuery->variantContextForItems(
            $purchaseOrder->lines->map(static fn (PurchaseOrderLine $line) => (object) [
                'purchasable_uuid' => $line->purchasable_uuid,
            ]),
        );
    }
}
