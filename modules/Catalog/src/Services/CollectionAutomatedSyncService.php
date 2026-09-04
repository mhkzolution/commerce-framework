<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\Collection;
use Commerce\Product\Models\Product;
use Commerce\Product\Support\ProductPrice;
use Illuminate\Database\Eloquent\Builder;

final class CollectionAutomatedSyncService
{
    public function sync(Collection $collection): void
    {
        if ($collection->type !== Collection::TYPE_AUTOMATED) {
            return;
        }

        $productIds = $this->matchingProductIds($collection->rules ?? []);
        $collection->products()->sync($productIds);
    }

    public function syncAllAutomated(): void
    {
        Collection::query()
            ->where('type', Collection::TYPE_AUTOMATED)
            ->each(fn (Collection $collection) => $this->sync($collection));
    }

    public function syncForProduct(Product $product): void
    {
        Collection::query()
            ->where('type', Collection::TYPE_AUTOMATED)
            ->each(fn (Collection $collection) => $this->sync($collection));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function matchingProductIds(array $rules): array
    {
        if (! empty($rules['groups']) && is_array($rules['groups'])) {
            return $this->matchingProductIdsFromGroups($rules);
        }

        return $this->matchingRuleSetProductIds($rules);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function matchingProductIdsFromGroups(array $rules): array
    {
        $groupResults = [];

        foreach ($rules['groups'] as $group) {
            if (! is_array($group)) {
                continue;
            }

            $groupResults[] = $this->matchingRuleSetProductIds($group);
        }

        if ($groupResults === []) {
            return [];
        }

        if (($rules['match'] ?? 'any') === 'all') {
            $intersection = $groupResults[0];

            for ($index = 1, $count = count($groupResults); $index < $count; $index++) {
                $intersection = array_values(array_intersect($intersection, $groupResults[$index]));
            }

            return $intersection;
        }

        return array_values(array_unique(array_merge(...$groupResults)));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function matchingRuleSetProductIds(array $rules): array
    {
        if (! $this->hasActiveRules($rules)) {
            return [];
        }

        $match = $rules['match'] ?? 'all';

        if ($match === 'any') {
            $ids = [];

            foreach ($this->ruleSlices($rules) as $slice) {
                $ids = array_merge($ids, $this->queryProductIds($slice));
            }

            return array_values(array_unique($ids));
        }

        return $this->queryProductIds($rules);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<array<string, mixed>>
     */
    private function ruleSlices(array $rules): array
    {
        $slices = [];

        if (($rules['on_sale'] ?? false) === true) {
            $slices[] = ['on_sale' => true];
        }

        if ($this->categoryIds($rules) !== []) {
            $slices[] = [
                'category_ids' => $this->categoryIds($rules),
                'category_match' => $rules['category_match'] ?? 'any',
            ];
        }

        $brandUuids = $this->brandUuids($rules);

        if ($brandUuids !== []) {
            $slices[] = [
                'brand_uuids' => $brandUuids,
                'brand_match' => $rules['brand_match'] ?? 'any',
            ];
        }

        $tagIds = $this->tagIds($rules);

        if ($tagIds !== []) {
            $slices[] = [
                'tag_ids' => $tagIds,
                'tag_match' => $rules['tag_match'] ?? 'any',
            ];
        }

        if (isset($rules['price_min']) || isset($rules['price_max'])) {
            $slices[] = array_filter([
                'price_min' => isset($rules['price_min']) ? (float) $rules['price_min'] : null,
                'price_max' => isset($rules['price_max']) ? (float) $rules['price_max'] : null,
            ], static fn ($value) => $value !== null);
        }

        return $slices;
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function queryProductIds(array $rules): array
    {
        $query = Product::query()->visibleOnStorefront();

        if (($rules['on_sale'] ?? false) === true) {
            $query->whereHas('variants', static function (Builder $variantQuery): void {
                $variantQuery
                    ->whereNotNull('compare_at_price')
                    ->whereColumn('compare_at_price', '>', 'price');
            });
        }

        $categoryIds = $this->categoryIds($rules);

        if ($categoryIds !== []) {
            $categoryMatch = $rules['category_match'] ?? 'any';

            if ($categoryMatch === 'all') {
                foreach ($categoryIds as $categoryId) {
                    $query->whereHas('categories', static function (Builder $categoryQuery) use ($categoryId): void {
                        $categoryQuery->where('categories.id', $categoryId);
                    });
                }
            } else {
                $query->whereHas('categories', static function (Builder $categoryQuery) use ($categoryIds): void {
                    $categoryQuery->whereIn('categories.id', $categoryIds);
                });
            }
        }

        $brandUuids = $this->brandUuids($rules);

        if ($brandUuids !== []) {
            $brandMatch = $rules['brand_match'] ?? 'any';

            if ($brandMatch === 'all' && count($brandUuids) > 1) {
                $query->whereRaw('1 = 0');
            } elseif ($brandMatch === 'all') {
                $query->where('brand_uuid', $brandUuids[0]);
            } else {
                $query->whereIn('brand_uuid', $brandUuids);
            }
        }

        $tagIds = $this->tagIds($rules);

        if ($tagIds !== []) {
            $tagMatch = $rules['tag_match'] ?? 'any';

            if ($tagMatch === 'all') {
                foreach ($tagIds as $tagId) {
                    $query->whereHas('tags', static function (Builder $tagQuery) use ($tagId): void {
                        $tagQuery->where('tags.id', $tagId);
                    });
                }
            } else {
                $query->whereHas('tags', static function (Builder $tagQuery) use ($tagIds): void {
                    $tagQuery->whereIn('tags.id', $tagIds);
                });
            }
        }

        if (isset($rules['price_min']) || isset($rules['price_max'])) {
            $priceMin = isset($rules['price_min']) ? ProductPrice::toMinorUnits($rules['price_min']) : null;
            $priceMax = isset($rules['price_max']) ? ProductPrice::toMinorUnits($rules['price_max']) : null;

            $query->whereHas('variants', static function (Builder $variantQuery) use ($priceMin, $priceMax): void {
                if ($priceMin !== null) {
                    $variantQuery->where('price', '>=', $priceMin);
                }

                if ($priceMax !== null) {
                    $variantQuery->where('price', '<=', $priceMax);
                }
            });
        }

        return $query->pluck('id')->map(static fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $rules
     */
    private function hasActiveRules(array $rules): bool
    {
        return ($rules['on_sale'] ?? false) === true
            || $this->categoryIds($rules) !== []
            || $this->brandUuids($rules) !== []
            || $this->tagIds($rules) !== []
            || isset($rules['price_min'])
            || isset($rules['price_max']);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function categoryIds(array $rules): array
    {
        $ids = [];

        if (! empty($rules['category_id'])) {
            $ids[] = (int) $rules['category_id'];
        }

        foreach ($rules['category_ids'] ?? [] as $id) {
            if (filled($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<string>
     */
    private function brandUuids(array $rules): array
    {
        $uuids = [];

        if (! empty($rules['brand_uuid'])) {
            $uuids[] = (string) $rules['brand_uuid'];
        }

        foreach ($rules['brand_uuids'] ?? [] as $uuid) {
            if (filled($uuid)) {
                $uuids[] = (string) $uuid;
            }
        }

        return array_values(array_unique($uuids));
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return list<int>
     */
    private function tagIds(array $rules): array
    {
        $ids = [];

        if (! empty($rules['tag_id'])) {
            $ids[] = (int) $rules['tag_id'];
        }

        foreach ($rules['tag_ids'] ?? [] as $id) {
            if (filled($id)) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
