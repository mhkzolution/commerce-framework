<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Core\Models\SearchDocument;
use Commerce\Product\Support\SearchNormalizer;

final class ProductDiscoveryQuery
{
    public function __construct(
        private readonly SearchSynonymExpander $expander,
    ) {}

    /**
     * @return list<string>
     */
    public function candidateUuids(string $q): array
    {
        if (trim($q) === '') {
            return [];
        }

        $exactSku = SearchNormalizer::skuNormalize($q);
        $tokens = $this->expander->expand(SearchNormalizer::tokenize($q));
        $matches = [];

        foreach (SearchDocument::query()->where('index_name', ProductSearchIndexer::INDEX)->get() as $document) {
            $payload = is_array($document->payload) ? $document->payload : [];
            $skus = $this->strings($payload['skus'] ?? []);
            $isExactSku = collect($skus)->contains(
                static fn (string $sku): bool => SearchNormalizer::skuNormalize($sku) === $exactSku,
            );
            $fields = $this->fieldTokens((string) $document->title, (string) $document->body, $payload, $skus);

            if (! $isExactSku && ! $this->matchesEveryToken($tokens, $fields)) {
                continue;
            }

            $matches[] = [
                'uuid' => (string) $document->document_id,
                'rank' => $isExactSku ? 1 : $this->highestMatchedFieldRank($tokens, $fields),
                'title' => (string) $document->title,
            ];
        }

        usort($matches, static function (array $left, array $right): int {
            return [$left['rank'], $left['title']] <=> [$right['rank'], $right['title']];
        });

        return array_values(array_column($matches, 'uuid'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $skus
     * @return array<int, list<string>>
     */
    private function fieldTokens(string $title, string $body, array $payload, array $skus): array
    {
        $categoryTokens = [];

        foreach ($this->strings($payload['category_names'] ?? []) as $categoryName) {
            array_push($categoryTokens, ...SearchNormalizer::tokenize($categoryName));
        }

        $attributeTokens = [];

        foreach ($this->arrays($payload['attributes'] ?? []) as $attribute) {
            if (isset($attribute['code']) && is_string($attribute['code'])) {
                $attributeTokens[] = SearchNormalizer::textNormalize($attribute['code']);
            }

            if (isset($attribute['label']) && is_string($attribute['label'])) {
                array_push($attributeTokens, ...SearchNormalizer::tokenize($attribute['label']));
            }
        }

        return [
            2 => SearchNormalizer::tokenize($title),
            3 => isset($payload['brand_name']) && is_string($payload['brand_name'])
                ? SearchNormalizer::tokenize($payload['brand_name'])
                : [],
            4 => $categoryTokens,
            5 => $attributeTokens,
            6 => array_merge(
                SearchNormalizer::tokenize($body),
                array_map(
                    static fn (string $sku): string => SearchNormalizer::textNormalize($sku),
                    $skus,
                ),
            ),
        ];
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<int, list<string>>  $fields
     */
    private function matchesEveryToken(array $tokens, array $fields): bool
    {
        $allFieldTokens = array_merge(...array_values($fields));

        foreach ($tokens as $token) {
            if (! in_array($token, $allFieldTokens, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $tokens
     * @param  array<int, list<string>>  $fields
     */
    private function highestMatchedFieldRank(array $tokens, array $fields): int
    {
        foreach ($fields as $rank => $fieldTokens) {
            if (array_intersect($tokens, $fieldTokens) !== []) {
                return $rank;
            }
        }

        return PHP_INT_MAX;
    }

    /**
     * @return list<string>
     */
    private function strings(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, 'is_string'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function arrays(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter($values, 'is_array'));
    }
}
