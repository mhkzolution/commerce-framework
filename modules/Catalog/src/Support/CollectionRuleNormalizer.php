<?php

declare(strict_types=1);

namespace Commerce\Catalog\Support;

final class CollectionRuleNormalizer
{
    /**
     * @param  array<string, mixed>|null  $rules
     * @return array<string, mixed>|null
     */
    public function normalize(?array $rules): ?array
    {
        if (! is_array($rules)) {
            return null;
        }

        $useGroups = filter_var($rules['use_groups'] ?? false, FILTER_VALIDATE_BOOL);

        if ($useGroups) {
            $groups = [];

            foreach ($rules['groups'] ?? [] as $group) {
                if (! is_array($group)) {
                    continue;
                }

                $normalizedGroup = $this->normalizeRuleSet($group);

                if ($normalizedGroup !== null) {
                    $groups[] = $normalizedGroup;
                }
            }

            if ($groups === []) {
                return null;
            }

            return [
                'match' => in_array($rules['match'] ?? 'any', ['all', 'any'], true) ? $rules['match'] : 'any',
                'groups' => $groups,
            ];
        }

        return $this->normalizeRuleSet($rules);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>|null
     */
    private function normalizeRuleSet(array $rules): ?array
    {
        $brandUuids = collect($rules['brand_uuids'] ?? [])
            ->filter(static fn ($value) => filled($value))
            ->map(static fn ($value) => (string) $value)
            ->values()
            ->all();

        if (filled($rules['brand_uuid'] ?? null) && ! in_array((string) $rules['brand_uuid'], $brandUuids, true)) {
            $brandUuids[] = (string) $rules['brand_uuid'];
        }

        $tagIds = collect($rules['tag_ids'] ?? [])
            ->filter(static fn ($value) => filled($value))
            ->map(static fn ($value) => (int) $value)
            ->values()
            ->all();

        if (filled($rules['tag_id'] ?? null) && ! in_array((int) $rules['tag_id'], $tagIds, true)) {
            $tagIds[] = (int) $rules['tag_id'];
        }

        $categoryIds = collect($rules['category_ids'] ?? [])
            ->filter(static fn ($value) => filled($value))
            ->map(static fn ($value) => (int) $value)
            ->values()
            ->all();

        if (filled($rules['category_id'] ?? null) && ! in_array((int) $rules['category_id'], $categoryIds, true)) {
            $categoryIds[] = (int) $rules['category_id'];
        }

        $normalized = array_filter([
            'match' => in_array($rules['match'] ?? 'all', ['all', 'any'], true) ? $rules['match'] : 'all',
            'on_sale' => filter_var($rules['on_sale'] ?? false, FILTER_VALIDATE_BOOL),
            'category_match' => $categoryIds !== [] ? (in_array($rules['category_match'] ?? 'any', ['all', 'any'], true) ? ($rules['category_match'] ?? 'any') : 'any') : null,
            'category_ids' => $categoryIds !== [] ? $categoryIds : null,
            'brand_match' => $brandUuids !== [] ? (in_array($rules['brand_match'] ?? 'any', ['all', 'any'], true) ? ($rules['brand_match'] ?? 'any') : 'any') : null,
            'brand_uuids' => $brandUuids !== [] ? $brandUuids : null,
            'tag_match' => $tagIds !== [] ? (in_array($rules['tag_match'] ?? 'any', ['all', 'any'], true) ? ($rules['tag_match'] ?? 'any') : 'any') : null,
            'tag_ids' => $tagIds !== [] ? $tagIds : null,
            'price_min' => filled($rules['price_min'] ?? null) ? (float) $rules['price_min'] : null,
            'price_max' => filled($rules['price_max'] ?? null) ? (float) $rules['price_max'] : null,
        ], static function ($value, string $key): bool {
            if (in_array($key, ['match', 'category_match', 'brand_match', 'tag_match'], true)) {
                return in_array($value, ['all', 'any'], true);
            }

            if ($key === 'on_sale') {
                return $value === true;
            }

            return $value !== null && $value !== '';
        }, ARRAY_FILTER_USE_BOTH);

        return $normalized === [] ? null : $normalized;
    }
}
