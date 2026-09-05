<?php

declare(strict_types=1);

namespace Commerce\Cart\Services;

use Commerce\Cart\Contracts\CartServiceInterface;
use Commerce\Cart\DTO\CartLineData;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;

final class StorefrontReorderService
{
    public function __construct(
        private readonly CartServiceInterface $cartService,
    ) {}

    /**
     * @return array{added: int, skipped: list<string>, message: string}
     */
    public function reorder(object $order): array
    {
        $added = 0;
        $skipped = [];
        $lines = $order->lineItems ?? [];

        foreach ($lines as $line) {
            $purchasableUuid = is_string($line->purchasable_uuid ?? null) ? $line->purchasable_uuid : null;
            $quantity = (int) ($line->quantity ?? 0);
            $name = is_string($line->name ?? null) && $line->name !== '' ? $line->name : __('storefront::storefront.product');

            if ($purchasableUuid === null || $quantity <= 0) {
                $skipped[] = $name;

                continue;
            }

            try {
                $this->cartService->add(new CartLineData(
                    purchasableUuid: $purchasableUuid,
                    quantity: $quantity,
                ));
                $added++;
            } catch (DomainException|EntityNotFoundException) {
                $skipped[] = $name;
            }
        }

        return [
            'added' => $added,
            'skipped' => $skipped,
            'message' => $this->message($added, $skipped),
        ];
    }

    /**
     * @param  list<string>  $skipped
     */
    private function message(int $added, array $skipped): string
    {
        if ($added === 0 && $skipped === []) {
            return __('storefront::storefront.reorder_empty');
        }

        if ($skipped === []) {
            return __('storefront::storefront.reorder_complete');
        }

        if ($added === 0) {
            return __('storefront::storefront.reorder_unavailable');
        }

        return __('storefront::storefront.reorder_partial');
    }
}
