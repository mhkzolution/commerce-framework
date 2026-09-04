<?php

declare(strict_types=1);

namespace Commerce\Pos\Http\Controllers;

use Commerce\Cart\DTO\CartLineData;
use Commerce\Contracts\Customer\CustomerQueryServiceInterface;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Models\Session;
use Commerce\Pos\Services\PosHeldSaleService;
use Commerce\Pos\Services\PosReceiptService;
use Commerce\Pos\Services\PosRegisterResolver;
use Commerce\Pos\Services\PosSaleService;
use Commerce\Pos\Services\PosSessionService;
use Commerce\Pos\Services\PosStateService;
use Commerce\Pos\Services\PosSyncService;
use Commerce\Pos\Support\PosSessionStateFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PosApiController extends Controller
{
    public function __construct(
        private readonly PosRegisterResolver $registerResolver,
        private readonly PosSaleService $saleService,
        private readonly PosSessionService $sessionService,
        private readonly PosStateService $stateService,
        private readonly PosHeldSaleService $heldSaleService,
        private readonly PosSyncService $syncService,
        private readonly PosReceiptService $receiptService,
        private readonly PosSessionStateFactory $sessionStateFactory,
    ) {}

    public function state(Request $request): JsonResponse
    {
        return $this->jsonState($this->register($request));
    }

    public function search(Request $request): JsonResponse
    {
        $this->register($request);
        $query = $request->string('q')->toString();

        return response()->json([
            'results' => $this->stateService->searchProducts($query),
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $register = $this->register($request);
        if (($session = $this->sessionOrError($register)) instanceof JsonResponse) {
            return $session;
        }

        $data = $request->validate([
            'purchasable_uuid' => ['nullable', 'uuid'],
            'sku' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $variant = null;

        if (! empty($data['purchasable_uuid'])) {
            $variant = app(ProductQueryServiceInterface::class)
                ->findVariantByUuid($data['purchasable_uuid']);
        }

        if ($variant === null && ! empty($data['sku'])) {
            $variant = $this->saleService->findVariantBySku($data['sku']);
        }

        if ($variant === null) {
            return $this->error('Product not found.', 422);
        }

        try {
            $this->saleService->cart($register)->add(new CartLineData(
                purchasableUuid: $variant->uuid,
                quantity: (int) ($data['quantity'] ?? 1),
            ));
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->jsonState($register);
    }

    public function updateItem(Request $request, string $purchasable): JsonResponse
    {
        $register = $this->register($request);
        $sessionCheck = $this->sessionOrError($register);
        if ($sessionCheck instanceof JsonResponse) {
            return $sessionCheck;
        }

        $quantity = (int) $request->validate(['quantity' => ['required', 'integer', 'min:0']])['quantity'];

        try {
            $this->saleService->cart($register)->update($purchasable, $quantity);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->jsonState($register);
    }

    public function removeItem(Request $request, string $purchasable): JsonResponse
    {
        $register = $this->register($request);
        $this->saleService->cart($register)->remove($purchasable);

        return $this->jsonState($register);
    }

    public function clearCart(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $this->saleService->cart($register)->clear();
        $this->sessionStateFactory->make($register->uuid)->clear();

        return $this->jsonState($register);
    }

    public function attachCustomer(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $data = $request->validate([
            'customer_uuid' => ['nullable', 'uuid'],
        ]);

        $state = $this->sessionStateFactory->make($register->uuid);

        if (empty($data['customer_uuid'])) {
            $state->setCustomerUuid(null);

            return $this->jsonState($register);
        }

        if (! app()->bound(CustomerQueryServiceInterface::class)) {
            return $this->error('Customer module is not available.', 422);
        }

        $customer = app(CustomerQueryServiceInterface::class)->findByUuid($data['customer_uuid']);

        if ($customer === null) {
            return $this->error('Customer not found.', 422);
        }

        $state->setCustomerUuid($data['customer_uuid']);

        return $this->jsonState($register);
    }

    public function searchCustomers(Request $request): JsonResponse
    {
        $this->register($request);
        $query = $request->string('q')->toString();

        if (! app()->bound(CustomerQueryServiceInterface::class)) {
            return response()->json(['results' => []]);
        }

        $paginator = app(CustomerQueryServiceInterface::class)->paginate(
            search: $query !== '' ? $query : null,
            perPage: 15,
        );

        return response()->json([
            'results' => collect($paginator->items())->map(static fn ($customer): array => [
                'uuid' => $customer->uuid,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ])->values(),
        ]);
    }

    public function updateNotes(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $data = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $this->sessionStateFactory->make($register->uuid)->setNotes($data['notes'] ?? '');

        return $this->jsonState($register);
    }

    public function updatePaymentMethod(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $data = $request->validate([
            'method' => ['required', 'string', 'in:cash,qr,transfer,card,gift,credit'],
        ]);
        $this->sessionStateFactory->make($register->uuid)->setPaymentMethod($data['method']);

        return $this->jsonState($register);
    }

    public function updateMixedPayments(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $data = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string', 'in:cash,qr,transfer,card,gift,credit'],
            'payments.*.amount_minor' => ['required', 'integer', 'min:0'],
        ]);

        $this->sessionStateFactory->make($register->uuid)->setMixedPayments($data['payments']);

        return $this->jsonState($register);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $data = $request->validate(['code' => ['required', 'string', 'max:100']]);

        try {
            $this->saleService->cart($register)->applyCoupon($data['code']);
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->jsonState($register);
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $this->saleService->cart($register)->removeCoupon();

        return $this->jsonState($register);
    }

    public function setLinePrice(Request $request, string $purchasable): JsonResponse
    {
        $register = $this->register($request);
        $sessionCheck = $this->sessionOrError($register);
        if ($sessionCheck instanceof JsonResponse) {
            return $sessionCheck;
        }

        $data = $request->validate([
            'unit_price_minor' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $this->saleService->cart($register)->setLinePriceOverride(
                $purchasable,
                isset($data['unit_price_minor']) ? (int) $data['unit_price_minor'] : null,
            );
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->jsonState($register);
    }

    public function sync(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $data = $request->validate([
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.id' => ['required', 'string', 'max:100'],
            'actions.*.type' => ['required', 'string', 'max:50'],
            'actions.*.payload' => ['nullable', 'array'],
        ]);

        $results = $this->syncService->process($register, $data['actions']);

        $response = $this->jsonState($register, 'synced');
        $payload = $response->getData(true);
        $payload['sync_results'] = $results;

        return response()->json($payload);
    }

    public function receiptData(Request $request, string $orderUuid): JsonResponse
    {
        $this->register($request);
        $order = Order::query()->where('uuid', $orderUuid)->firstOrFail();

        return response()->json($this->receiptService->build($order));
    }

    public function hold(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $sessionCheck = $this->sessionOrError($register);
        if ($sessionCheck instanceof JsonResponse) {
            return $sessionCheck;
        }

        $data = $request->validate(['label' => ['nullable', 'string', 'max:100']]);

        try {
            $this->heldSaleService->hold(
                $register->uuid,
                $this->saleService->cart($register),
                $this->sessionStateFactory->make($register->uuid),
                $data['label'] ?? null,
            );
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->jsonState($register);
    }

    public function resume(Request $request, string $holdId): JsonResponse
    {
        $register = $this->register($request);
        $sessionCheck = $this->sessionOrError($register);
        if ($sessionCheck instanceof JsonResponse) {
            return $sessionCheck;
        }

        try {
            $this->heldSaleService->resume(
                $register->uuid,
                $holdId,
                $this->saleService->cart($register),
                $this->sessionStateFactory->make($register->uuid),
            );
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        return $this->jsonState($register);
    }

    public function checkout(Request $request): JsonResponse
    {
        $register = $this->register($request);
        $session = $this->sessionOrError($register);
        if ($session instanceof JsonResponse) {
            return $session;
        }
        $state = $this->sessionStateFactory->make($register->uuid);

        $data = $request->validate([
            'amount_received' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'in:cash,qr,transfer,card,gift,credit,mixed'],
            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['required_with:payments', 'string', 'in:cash,qr,transfer,card,gift,credit'],
            'payments.*.amount_minor' => ['required_with:payments', 'integer', 'min:0'],
        ]);

        $mixedPayments = is_array($data['payments'] ?? null) ? $data['payments'] : $state->mixedPayments();

        $paymentMethod = ! empty($data['payment_method'])
            ? $data['payment_method']
            : $state->paymentMethod();

        if (! empty($data['payment_method'])) {
            $state->setPaymentMethod($data['payment_method']);
        }

        $customer = $this->stateService->build($register, $session, $state)['customer'];
        $customerData = is_array($customer['customer'] ?? null) ? $customer['customer'] : null;

        try {
            $order = $this->saleService->completeSale(
                register: $register,
                session: $session,
                customerName: $customerData['name'] ?? null,
                customerEmail: $customerData['email'] ?? null,
                customerUuid: $customerData['uuid'] ?? null,
                paymentMethod: $paymentMethod,
                notes: $state->notes(),
                mixedPayments: $mixedPayments,
                amountReceived: isset($data['amount_received']) ? (int) $data['amount_received'] : null,
            );
        } catch (DomainException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $state->clear();

        $response = $this->jsonState($register);
        $payload = $response->getData(true);
        $payload['receipt'] = array_merge(
            $this->receiptService->build($order),
            ['print_url' => route('pos.receipt.show', $order->uuid)],
        );

        return response()->json($payload);
    }

    private function register(Request $request): Register
    {
        return $this->registerResolver->resolve($request);
    }

    private function sessionOrError(Register $register): Session|JsonResponse
    {
        $session = $this->sessionService->activeSession($register);

        if ($session === null) {
            return $this->error('No active session. Open a session first.', 422);
        }

        return $session;
    }

    private function jsonState(Register $register, string $syncStatus = 'synced'): JsonResponse
    {
        $session = $this->sessionService->activeSession($register);
        $state = $this->sessionStateFactory->make($register->uuid);

        return response()->json(
            $this->stateService->build($register, $session, $state, $syncStatus),
        );
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
