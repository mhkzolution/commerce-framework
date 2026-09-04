<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Controllers\Admin;

use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Inventory\Contracts\InventoryServiceInterface;
use Commerce\Orders\Contracts\OrderFulfillmentServiceInterface;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\CreateShipmentData;
use Commerce\Orders\DTO\OrderLineData;
use Commerce\Orders\Http\Requests\AdminStoreOrderRequest;
use Commerce\Orders\Http\Requests\StoreOrderShipmentRequest;
use Commerce\Orders\Http\Requests\UpdateOrderNotesRequest;
use Commerce\Orders\Http\Requests\UpdateShipmentTrackingRequest;
use Commerce\Orders\Models\Order;
use Commerce\Orders\Models\OrderEvent;
use Commerce\Orders\Services\AdminOrderLookupService;
use Commerce\Orders\Services\OrderDetailViewModelBuilder;
use Commerce\Orders\Services\OrderEventRecorder;
use Commerce\Orders\Services\OrderQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderQueryService $queryService,
        private readonly OrderServiceInterface $orderService,
        private readonly ProductQueryServiceInterface $productQueryService,
        private readonly AdminOrderLookupService $lookup,
        private readonly OrderDetailViewModelBuilder $detailBuilder,
        private readonly OrderFulfillmentServiceInterface $fulfillment,
        private readonly OrderEventRecorder $events,
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
        return view('orders::admin.create', [
            'channels' => config('orders.channels', []),
            'adminStatuses' => config('orders.admin_statuses', []),
            'defaultChannel' => config('orders.default_channel', 'web'),
            'currency' => config('orders.default_currency', 'USD'),
            'initialLines' => $this->hydrateOldLines((array) old('lines', [])),
        ]);
    }

    public function store(AdminStoreOrderRequest $request): RedirectResponse
    {
        $order = $this->orderService->create($this->mapAdminOrderData($request));

        if (
            $order->wasRecentlyCreated
            && $request->intent() !== 'draft'
            && (bool) config('inventory.reserve_on_checkout', true)
            && app()->bound(InventoryServiceInterface::class)
        ) {
            $inventory = app(InventoryServiceInterface::class);

            foreach ($order->lineItems as $line) {
                $inventory->reserve(
                    purchasableUuid: $line->purchasable_uuid,
                    quantity: $line->quantity,
                    referenceType: Order::REFERENCE_TYPE,
                    referenceId: $order->uuid,
                    reason: "Admin create {$order->order_number}",
                );
            }
        }

        $message = $request->intent() === 'draft'
            ? __('orders::admin.draft_saved')
            : __('orders::admin.order_created');

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', $message);
    }

    public function show(string $order): View
    {
        $model = $this->queryService->findByUuid($order);

        abort_if(! $model instanceof Order, 404);

        return view('orders::admin.show', [
            'detail' => $this->detailBuilder->build($model),
            'statuses' => config('orders.statuses', []),
        ]);
    }

    public function confirm(string $order): RedirectResponse
    {
        try {
            $this->orderService->confirm($order, $this->actorUuid(request()));
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', __('orders::admin.order_confirmed'));
    }

    public function complete(string $order): RedirectResponse
    {
        try {
            $this->orderService->complete($order, $this->actorUuid(request()));
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', __('orders::admin.order_completed'));
    }

    public function cancel(string $order): RedirectResponse
    {
        try {
            $this->orderService->cancel($order, $this->actorUuid(request()));
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('status', __('orders::admin.order_cancelled'));
    }

    public function updateNotes(UpdateOrderNotesRequest $request, string $order): RedirectResponse
    {
        $model = $this->queryService->findByUuid($order);
        abort_if(! $model instanceof Order, 404);

        if ($model->isCancelled()) {
            return back()->withErrors(['status' => __('orders::admin.notes_locked')]);
        }

        $meta = $model->meta ?? [];
        $meta['notes'] = $request->validated('internal_notes');
        $meta['customer_note'] = $request->validated('customer_note');

        $model->update([
            'meta' => $meta,
            'updated_by_user_uuid' => $this->actorUuid($request),
        ]);

        $this->events->record(
            $model,
            OrderEvent::TYPE_NOTES_UPDATED,
            'Notes updated',
            $this->actorUuid($request),
        );

        return redirect()
            ->route('admin.orders.show', $model)
            ->with('status', __('orders::admin.notes_saved'));
    }

    public function storeShipment(StoreOrderShipmentRequest $request, string $order): RedirectResponse
    {
        $model = $this->queryService->findByUuid($order);
        abort_if(! $model instanceof Order, 404);

        try {
            $this->fulfillment->createShipment($model, new CreateShipmentData(
                quantitiesByLineUuid: $request->quantitiesByLineUuid(),
                carrier: $request->validated('carrier'),
                trackingNumber: $request->validated('tracking_number'),
                trackingUrl: $request->validated('tracking_url'),
                notes: $request->validated('notes'),
                createdByUserUuid: $this->actorUuid($request),
            ));
        } catch (DomainException $exception) {
            $field = str_contains($exception->getMessage(), 'Quantity') ? 'items' : 'status';

            return back()->withErrors([$field => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.orders.show', $model)
            ->with('status', __('orders::admin.shipment_created'));
    }

    public function updateTracking(UpdateShipmentTrackingRequest $request, string $order, string $shipment): RedirectResponse
    {
        $model = $this->queryService->findByUuid($order);
        abort_if(! $model instanceof Order, 404);

        try {
            $this->fulfillment->updateTracking(
                $model,
                $shipment,
                $request->validated('carrier'),
                $request->validated('tracking_number'),
                $this->actorUuid($request),
            );
        } catch (EntityNotFoundException) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['tracking_number' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $model)
            ->with('status', __('orders::admin.tracking_updated'));
    }

    public function cancelShipment(string $order, string $shipment): RedirectResponse
    {
        $model = $this->queryService->findByUuid($order);
        abort_if(! $model instanceof Order, 404);

        try {
            $this->fulfillment->cancelShipment($model, $shipment, $this->actorUuid(request()));
        } catch (EntityNotFoundException) {
            abort(404);
        } catch (DomainException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.orders.show', $model)
            ->with('status', __('orders::admin.shipment_cancelled'));
    }

    private function actorUuid(Request $request): ?string
    {
        $user = $request->user();

        return is_object($user) && isset($user->uuid) ? (string) $user->uuid : null;
    }

    private function mapAdminOrderData(AdminStoreOrderRequest $request): CreateOrderData
    {
        $lines = [];
        $subtotal = 0;

        foreach ($request->validated('lines', []) as $line) {
            $quantity = (int) $line['quantity'];
            $unitPrice = $request->lineUnitPriceMinor($line);
            $lines[] = new OrderLineData(
                purchasableUuid: $line['purchasable_uuid'],
                quantity: $quantity,
                unitPrice: $unitPrice,
            );

            $variant = $this->productQueryService->findVariantByUuid($line['purchasable_uuid']);
            $catalogPrice = $variant !== null ? (int) $variant->price : 0;
            $subtotal += ($unitPrice ?? $catalogPrice) * $quantity;
        }

        $intent = $request->intent();
        $adminStatus = $intent === 'draft'
            ? 'draft'
            : (string) ($request->validated('admin_status') ?: 'pending');
        $user = $request->user();
        $createdByUserUuid = is_object($user) && isset($user->uuid) ? (string) $user->uuid : null;
        $idempotencyKey = $request->validated('idempotency_key');

        return new CreateOrderData(
            lines: $lines,
            customerEmail: $request->validated('customer_email'),
            customerName: $request->validated('customer_name'),
            customerUuid: $request->validated('customer_uuid'),
            currency: $request->validated('currency') ?: config('orders.default_currency', 'USD'),
            channel: $request->validated('channel') ?: config('orders.default_channel', 'web'),
            billingAddress: $request->billingAddress(),
            shippingAddress: $request->shippingAddress(),
            shippingTotal: $request->shippingFeeMinor(),
            discountTotal: $request->discountMinor($subtotal),
            taxTotal: $request->taxMinor(),
            meta: [
                'notes' => $request->validated('notes'),
                'customer_phone' => $request->validated('customer_phone'),
                'admin_status' => $adminStatus,
                'intent' => $intent,
                'discount' => [
                    'type' => $request->validated('discount_type') ?: 'fixed',
                    'value' => $request->validated('discount_value'),
                ],
            ],
            idempotencyKey: is_string($idempotencyKey) && $idempotencyKey !== ''
                ? $idempotencyKey
                : (string) Str::uuid(),
            createdByUserUuid: $createdByUserUuid,
            requirePurchasable: false,
        );
    }

    /**
     * @param  array<int|string, mixed>  $lines
     * @return list<array<string, mixed>>
     */
    private function hydrateOldLines(array $lines): array
    {
        $uuids = [];

        foreach ($lines as $line) {
            if (is_array($line) && is_string($line['purchasable_uuid'] ?? null)) {
                $uuids[] = $line['purchasable_uuid'];
            }
        }

        $mapped = collect($this->lookup->productsByUuids($uuids))
            ->keyBy('purchasable_uuid');

        $hydrated = [];

        foreach ($lines as $line) {
            if (! is_array($line) || ! is_string($line['purchasable_uuid'] ?? null)) {
                continue;
            }

            $item = $mapped->get($line['purchasable_uuid']);

            if (! is_array($item)) {
                continue;
            }

            $item['quantity'] = max(1, (int) ($line['quantity'] ?? 1));

            if (array_key_exists('unit_price', $line) && $line['unit_price'] !== null && $line['unit_price'] !== '') {
                $item['unit_price'] = $line['unit_price'];
            }

            $hydrated[] = $item;
        }

        return $hydrated;
    }
}
