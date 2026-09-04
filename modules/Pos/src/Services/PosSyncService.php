<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Cart\DTO\CartLineData;
use Commerce\Contracts\Product\ProductQueryServiceInterface;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Pos\Models\Register;
use Commerce\Pos\Support\PosSessionState;
use Commerce\Pos\Support\PosSessionStateFactory;

final class PosSyncService
{
    public function __construct(
        private readonly PosSaleService $saleService,
        private readonly PosSessionStateFactory $sessionStateFactory,
    ) {}

    /**
     * @param  list<array{id: string, type: string, payload?: array<string, mixed>}>  $actions
     * @return list<array{id: string, status: string, message?: string}>
     */
    public function process(Register $register, array $actions): array
    {
        $results = [];
        $cart = $this->saleService->cart($register);
        $state = $this->sessionStateFactory->make($register->uuid);

        foreach ($actions as $action) {
            $id = (string) ($action['id'] ?? '');
            $type = (string) ($action['type'] ?? '');
            $payload = is_array($action['payload'] ?? null) ? $action['payload'] : [];

            try {
                $this->dispatch($cart, $state, $type, $payload);
                $results[] = ['id' => $id, 'status' => 'ok'];
            } catch (DomainException $exception) {
                $results[] = ['id' => $id, 'status' => 'error', 'message' => $exception->getMessage()];
            }
        }

        return $results;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(PosCartService $cart, PosSessionState $state, string $type, array $payload): void
    {
        match ($type) {
            'add_item' => $this->addItem($cart, $payload),
            'update_item' => $cart->update(
                (string) $payload['purchasable_uuid'],
                (int) ($payload['quantity'] ?? 1),
            ),
            'remove_item' => $cart->remove((string) $payload['purchasable_uuid']),
            'clear_cart' => $cart->clear(),
            'apply_coupon' => $cart->applyCoupon((string) ($payload['code'] ?? '')),
            'remove_coupon' => $cart->removeCoupon(),
            'set_line_price' => $cart->setLinePriceOverride(
                (string) $payload['purchasable_uuid'],
                array_key_exists('unit_price_minor', $payload) ? (int) $payload['unit_price_minor'] : null,
            ),
            'attach_customer' => $state->setCustomerUuid(
                isset($payload['customer_uuid']) && $payload['customer_uuid'] !== ''
                    ? (string) $payload['customer_uuid']
                    : null,
            ),
            'update_notes' => $state->setNotes((string) ($payload['notes'] ?? '')),
            'set_mixed_payments' => $state->setMixedPayments(
                is_array($payload['payments'] ?? null) ? $payload['payments'] : [],
            ),
            default => throw new DomainException("Unsupported sync action [{$type}]."),
        };
    }

    /** @param array<string, mixed> $payload */
    private function addItem(PosCartService $cart, array $payload): void
    {
        $uuid = $payload['purchasable_uuid'] ?? null;
        $sku = $payload['sku'] ?? null;
        $variant = null;

        if (is_string($uuid) && $uuid !== '') {
            $variant = app(ProductQueryServiceInterface::class)->findVariantByUuid($uuid);
        }

        if ($variant === null && is_string($sku) && $sku !== '') {
            $variant = $this->saleService->findVariantBySku($sku);
        }

        if ($variant === null) {
            throw new DomainException('Product not found.');
        }

        $cart->add(new CartLineData(
            purchasableUuid: $variant->uuid,
            quantity: (int) ($payload['quantity'] ?? 1),
        ));
    }
}
