<?php

declare(strict_types=1);

namespace Commerce\Promotion\Services;

use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Promotion\Models\Promotion;

final class PromotionApplicationService extends BaseService implements PromotionServiceInterface
{
    public function resolve(?string $code, int $subtotal): ?object
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $promotion = Promotion::query()
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))])
            ->first();

        if ($promotion === null || ! $promotion->isAvailableFor($subtotal)) {
            return null;
        }

        $discount = $promotion->calculateDiscount($subtotal);
        if ($discount <= 0) {
            return null;
        }

        return (object) [
            'uuid' => $promotion->uuid,
            'code' => $promotion->code,
            'name' => $promotion->name,
            'discount' => $discount,
        ];
    }

    public function redeem(string $uuid): void
    {
        $promotion = Promotion::query()->where('uuid', $uuid)->first();
        if ($promotion === null) {
            throw new DomainException('Promotion not found.');
        }

        if ($promotion->max_uses !== null && $promotion->used_count >= $promotion->max_uses) {
            throw new DomainException('Promotion usage limit reached.');
        }

        $promotion->increment('used_count');
    }
}
