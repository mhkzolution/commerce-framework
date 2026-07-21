<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Controllers\Admin;

use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\OrderLineData;
use Commerce\Orders\Http\Requests\StoreOrderRequest;
use Commerce\Orders\Services\OrderQueryService;
use Commerce\Product\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderQueryService $queryService,
        private readonly OrderServiceInterface $orderService,
    ) {}

    public function index(Request $request): View
    {
        return view('orders::admin.index', [
            'orders' => $this->queryService->paginate(
                search: $request->string('search')->toString() ?: null,
                status: $request->string('status')->toString() ?: null,
            ),
            'statuses' => config('orders.statuses', []),
        ]);
    }

    public function create(): View
    {
        $variants = ProductVariant::query()
            ->with('product')
            ->orderBy('sku')
            ->get();

        $customers = [];
        if (app()->bound(CustomerQueryServiceInterface::class)) {
            $customers = app(CustomerQueryServiceInterface::class)
                ->paginate(status: 'active', perPage: 500)
                ->items();
        }

        return view('orders::admin.create', [
            'variants' => $variants,
            'customers' => $customers,
        ]);
    }

    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orderService->create($this->mapOrderData($request));

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order created.');
    }

    public function show(string $order): View
    {
        $model = $this->queryService->findByUuid($order);

        abort_if($model === null, 404);

        return view('orders::admin.show', [
            'order' => $model,
            'statuses' => config('orders.statuses', []),
        ]);
    }

    public function confirm(string $order): RedirectResponse
    {
        $this->orderService->confirm($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order confirmed and stock deducted.');
    }

    public function complete(string $order): RedirectResponse
    {
        $this->orderService->complete($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order marked as completed.');
    }

    public function cancel(string $order): RedirectResponse
    {
        $this->orderService->cancel($order);

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', 'Order cancelled.');
    }

    private function mapOrderData(StoreOrderRequest $request): CreateOrderData
    {
        $lines = [];
        foreach ($request->validated('lines', []) as $line) {
            if (empty($line['purchasable_uuid'])) {
                continue;
            }

            $lines[] = new OrderLineData(
                purchasableUuid: $line['purchasable_uuid'],
                quantity: (int) $line['quantity'],
            );
        }

        return new CreateOrderData(
            lines: $lines,
            customerEmail: $request->validated('customer_email'),
            customerName: $request->validated('customer_name'),
            customerUuid: $request->validated('customer_uuid'),
            currency: $request->validated('currency'),
            channel: $request->validated('channel') ?? 'admin',
            billingAddress: $request->validated('billing_address'),
            shippingAddress: $request->validated('shipping_address'),
        );
    }
}
