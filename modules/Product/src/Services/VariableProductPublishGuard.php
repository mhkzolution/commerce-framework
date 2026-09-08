<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductAttributeValue;
use Illuminate\Validation\ValidationException;

final class VariableProductPublishGuard
{
    public function assertCanPublish(Product $product): void
    {
        if ($product->type !== 'variable') {
            return;
        }

        $axisIds = $product->productAttributes()
            ->where('used_for_variations', true)
            ->orderBy('position')
            ->pluck('attribute_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($axisIds === []) {
            return;
        }

        if (! $this->hasCompleteIdentity($product, $axisIds)) {
            throw ValidationException::withMessages([
                'status' => 'A variable product cannot be published until at least one variant has a complete variation identity.',
            ]);
        }
    }

    /**
     * @param  list<int>  $axisIds
     */
    private function hasCompleteIdentity(Product $product, array $axisIds): bool
    {
        $variants = $product->variants()->get();
        if ($variants->isEmpty()) {
            return false;
        }

        $values = ProductAttributeValue::query()
            ->where('product_id', $product->id)
            ->whereIn('product_variant_id', $variants->modelKeys())
            ->whereIn('attribute_id', $axisIds)
            ->whereNotNull('attribute_value_id')
            ->get()
            ->groupBy('product_variant_id');

        foreach ($variants as $variant) {
            $byAxis = ($values->get($variant->id) ?? collect())
                ->keyBy('attribute_id');

            $complete = true;
            foreach ($axisIds as $axisId) {
                if ($byAxis->get($axisId)?->attribute_value_id === null) {
                    $complete = false;
                    break;
                }
            }

            if ($complete) {
                return true;
            }
        }

        return false;
    }
}
