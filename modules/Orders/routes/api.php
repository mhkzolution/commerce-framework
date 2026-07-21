<?php

declare(strict_types=1);

use Commerce\Api\Responses\ApiResponse;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Orders\Contracts\OrderServiceInterface;
use Commerce\Orders\DTO\CreateOrderData;
use Commerce\Orders\DTO\OrderLineData;
use Commerce\Orders\Http\Requests\StoreOrderRequest;
use Commerce\Orders\Http\Resources\OrderResource;
use Commerce\Orders\Services\OrderQueryService;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->middleware(['api'])->group(function (): void {
    Route::post('/orders', function (StoreOrderRequest $request, OrderServiceInterface $orderService) {
        try {
            $lines = [];
            foreach ($request->validated('lines', []) as $line) {
                $lines[] = new OrderLineData(
                    purchasableUuid: $line['purchasable_uuid'],
                    quantity: (int) $line['quantity'],
                );
            }

            $order = $orderService->create(new CreateOrderData(
                lines: $lines,
                customerEmail: $request->validated('customer_email'),
                customerName: $request->validated('customer_name'),
                customerUuid: $request->validated('customer_uuid'),
                currency: $request->validated('currency'),
                channel: $request->validated('channel'),
                billingAddress: $request->validated('billing_address'),
                shippingAddress: $request->validated('shipping_address'),
            ));

            return ApiResponse::success(new OrderResource($order), status: 201);
        } catch (DomainException|EntityNotFoundException $exception) {
            return ApiResponse::error('order.invalid', $exception->getMessage(), status: 422);
        }
    })->name('api.v1.orders.store');

    Route::get('/orders/{uuid}', function (OrderQueryService $orders, string $uuid) {
        $order = $orders->findByUuid($uuid) ?? $orders->findByOrderNumber($uuid);

        if ($order === null) {
            return ApiResponse::error('order.not_found', 'Order not found.', status: 404);
        }

        return ApiResponse::success(new OrderResource($order));
    })->name('api.v1.orders.show');
});
