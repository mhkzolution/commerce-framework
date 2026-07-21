<?php

declare(strict_types=1);

namespace Commerce\Contracts\Promotion;

interface PromotionServiceInterface
{
    /**
     * @return object{uuid: string, code: string, name: string, discount: int}|null
     */
    public function resolve(?string $code, int $subtotal): ?object;

    public function redeem(string $uuid): void;
}
