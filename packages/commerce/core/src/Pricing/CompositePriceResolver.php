<?php

declare(strict_types=1);

namespace Commerce\Core\Pricing;

use Commerce\Contracts\Pricing\PriceQuoteInterface;
use Commerce\Contracts\Pricing\PriceResolverInterface;
use Commerce\Contracts\Pricing\PricingContextInterface;
use Commerce\Contracts\Promotion\PromotionServiceInterface;
use Commerce\Contracts\Purchasable\PurchasableInterface;
use Commerce\Product\Models\ProductVariant;

final class CompositePriceResolver implements PriceResolverInterface
{
    public function resolve(PurchasableInterface $purchasable, PricingContextInterface $context): PriceQuoteInterface
    {
        if (! $purchasable instanceof ProductVariant) {
            return new PriceQuote(0, $context->getCurrency(), ['base' => 0]);
        }

        $basePrice = (int) $purchasable->price;
        $quantity = max(1, $context->getQuantity());
        $lineSubtotal = $basePrice * $quantity;
        $discount = 0;
        $promotionCode = $context instanceof PricingContext ? $context->getCouponCode() : null;

        if ($promotionCode !== null && app()->bound(PromotionServiceInterface::class)) {
            $quote = app(PromotionServiceInterface::class)->resolve($promotionCode, $lineSubtotal);
            if ($quote !== null) {
                $discount = $quote->discount;
            }
        }

        $effectiveLineTotal = max(0, $lineSubtotal - $discount);
        $unitPrice = (int) floor($effectiveLineTotal / $quantity);

        return new PriceQuote(
            amount: $unitPrice,
            currency: $context->getCurrency(),
            breakdown: [
                'base' => $basePrice,
                'discount' => $discount,
                'promotion_code' => $promotionCode,
                'quantity' => $quantity,
            ],
        );
    }
}
