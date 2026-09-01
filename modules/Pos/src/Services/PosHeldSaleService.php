<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Pos\Support\PosSessionState;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

final class PosHeldSaleService
{
    public function __construct(
        private readonly Session $session,
    ) {}

    /**
     * @return list<array{id: string, label: string, created_at: string, item_count: int}>
     */
    public function list(string $registerUuid): array
    {
        return array_values(array_map(
            static fn (array $hold): array => [
                'id' => $hold['id'],
                'label' => $hold['label'],
                'created_at' => $hold['created_at'],
                'item_count' => (int) ($hold['item_count'] ?? 0),
            ],
            $this->holds($registerUuid),
        ));
    }

    public function hold(
        string $registerUuid,
        CartServiceInterface $cart,
        PosSessionState $state,
        ?string $label = null,
    ): string {
        $cartData = $cart->get();

        if ($cartData->lines === []) {
            throw new DomainException('Cannot hold an empty cart.');
        }

        $cartExport = $cart instanceof PosCartService
            ? array_merge($cart->storage()->export(), ['lines' => $cart->rawLines()])
            : ['lines' => array_map(
                static fn ($line): array => [
                    'purchasable_uuid' => $line->purchasableUuid,
                    'quantity' => $line->quantity,
                ],
                $cartData->lines,
            )];

        $id = 'hold-'.Str::lower(Str::random(8));
        $holds = $this->holds($registerUuid);

        $holds[$id] = [
            'id' => $id,
            'label' => $label ?: 'Held sale '.now()->format('H:i'),
            'created_at' => now()->toIso8601String(),
            'item_count' => $cartData->itemCount,
            'cart_export' => $cartExport,
            'state' => $state->toArray(),
        ];

        $this->session->put($this->key($registerUuid), $holds);
        $cart->clear();
        $state->clear();

        return $id;
    }

    public function resume(
        string $registerUuid,
        string $holdId,
        CartServiceInterface $cart,
        PosSessionState $state,
    ): void {
        $holds = $this->holds($registerUuid);
        $hold = $holds[$holdId] ?? null;

        if ($hold === null) {
            throw new DomainException('Held sale not found.');
        }

        $current = $cart->get();

        if ($current->lines !== []) {
            throw new DomainException('Clear the current cart before resuming a held sale.');
        }

        if ($cart instanceof PosCartService && isset($hold['cart_export']) && is_array($hold['cart_export'])) {
            $export = $hold['cart_export'];
            $cart->restoreRawLines(
                $export['lines'] ?? [],
                $export['coupon_code'] ?? null,
            );
        } elseif (isset($hold['cart_lines'])) {
            foreach ($hold['cart_lines'] as $line) {
                $cart->add(new CartLineData(
                    purchasableUuid: $line['purchasable_uuid'],
                    quantity: (int) $line['quantity'],
                ));
            }
        }

        if (isset($hold['state']) && is_array($hold['state'])) {
            $state->replace($hold['state']);
        }

        unset($holds[$holdId]);
        $this->session->put($this->key($registerUuid), $holds);
    }

    /** @return array<string, array<string, mixed>> */
    private function holds(string $registerUuid): array
    {
        $holds = $this->session->get($this->key($registerUuid), []);

        return is_array($holds) ? $holds : [];
    }

    private function key(string $registerUuid): string
    {
        return 'commerce.pos.held_sales.'.$registerUuid;
    }
}
