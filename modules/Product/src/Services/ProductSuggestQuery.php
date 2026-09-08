<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\DTO\SuggestResult;
use Commerce\Product\Models\Product;
use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Database\Query\JoinClause;
use Normalizer;

final class ProductSuggestQuery
{
    private const LIMIT = 5;

    private const PRODUCT_CANDIDATE_LIMIT = 100;

    public function suggest(string $q, iterable $categories = []): SuggestResult
    {
        $prefix = SearchNormalizer::textNormalize($q);

        if (mb_strlen($prefix) < 2) {
            return new SuggestResult;
        }

        $products = $this->productCandidates($prefix);
        $brands = $this->brandCandidates($prefix);
        $categoryCandidates = $this->categoryCandidates($prefix, $categories);
        $completionCandidates = array_merge($products, $brands, $categoryCandidates);

        return new SuggestResult(
            completions: $this->completionHits($completionCandidates),
            products: $this->hits($products),
            brands: $this->hits($brands),
            categories: $this->hits($categoryCandidates),
        );
    }

    /**
     * @return list<array{label: string, url: string, rank: int}>
     */
    private function productCandidates(string $prefix): array
    {
        $candidates = [];
        $titlePatterns = $this->titlePrefilterPatterns($prefix);
        $products = Product::query()
            ->visibleOnStorefront()
            ->join('search_documents as suggest_documents', function (JoinClause $join): void {
                $join->on('suggest_documents.document_id', '=', 'products.uuid')
                    ->where('suggest_documents.index_name', ProductSearchIndexer::INDEX);
            })
            ->select('products.*')
            ->addSelect('suggest_documents.title as suggest_title')
            ->where(function ($query) use ($titlePatterns): void {
                foreach ($titlePatterns as $index => $pattern) {
                    $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                    $query->{$method}(
                        'suggest_documents.title like ? escape \'!\'',
                        [$pattern],
                    );
                }
            })
            ->orderByRaw('length(suggest_documents.title)')
            ->orderBy('suggest_documents.title')
            ->limit(self::PRODUCT_CANDIDATE_LIMIT)
            ->get();

        foreach ($products as $product) {
            $title = (string) $product->getAttribute('suggest_title');

            if (! $this->hasPrefix($title, $prefix)) {
                continue;
            }

            $candidates[] = [
                'label' => $title,
                'url' => route('storefront.products.show', (string) $product->slug),
                'rank' => (int) $product->id,
            ];
        }

        return $this->sortCandidates($candidates);
    }

    /**
     * @return list<string>
     */
    private function titlePrefilterPatterns(string $prefix): array
    {
        $nfdPrefix = Normalizer::normalize($prefix, Normalizer::FORM_D);
        $prefixes = [$prefix];

        if (is_string($nfdPrefix)) {
            $prefixes[] = mb_strtolower($nfdPrefix);
        }

        return array_map(
            static fn (string $candidate): string => str_replace(
                ['!', '\\', '%', '_'],
                ['!!', '!\\', '!%', '!_'],
                $candidate,
            ).'%',
            array_values(array_unique($prefixes)),
        );
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function brandCandidates(string $prefix): array
    {
        $candidates = [];

        foreach (Brand::query()->where('is_active', true)->get() as $brand) {
            $name = (string) $brand->name;
            $slug = trim((string) $brand->slug);

            if ($slug === '' || ! $this->hasPrefix($name, $prefix)) {
                continue;
            }

            $candidates[] = [
                'label' => $name,
                'url' => route('storefront.shop.index', ['brand' => $slug]),
            ];
        }

        return $this->sortCandidates($candidates);
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function categoryCandidates(string $prefix, iterable $categories): array
    {
        $candidates = [];

        foreach ($categories as $category) {
            if (! $this->hasPrefix($category->name, $prefix)) {
                continue;
            }

            $candidates[] = [
                'label' => $category->name,
                'url' => route('storefront.shop.index', ['category' => $category->slug]),
            ];
        }

        return $this->sortCandidates($candidates);
    }

    private function hasPrefix(string $label, string $prefix): bool
    {
        return str_starts_with(SearchNormalizer::textNormalize($label), $prefix);
    }

    /**
     * @param  list<array{label: string, url: string, rank?: int}>  $candidates
     * @return list<array{label: string, url: string, rank?: int}>
     */
    private function sortCandidates(array $candidates): array
    {
        usort($candidates, static function (array $left, array $right): int {
            $length = mb_strlen($left['label']) <=> mb_strlen($right['label']);

            if ($length !== 0) {
                return $length;
            }

            $natural = strnatcasecmp($left['label'], $right['label']);

            return $natural !== 0
                ? $natural
                : ($left['rank'] ?? PHP_INT_MAX) <=> ($right['rank'] ?? PHP_INT_MAX);
        });

        return $candidates;
    }

    /**
     * @param  list<array{label: string, url: string, rank?: int}>  $candidates
     * @return list<SuggestHit>
     */
    private function hits(array $candidates): array
    {
        return array_map(
            static fn (array $candidate): SuggestHit => new SuggestHit($candidate['label'], $candidate['url']),
            array_slice($candidates, 0, self::LIMIT),
        );
    }

    /**
     * @param  list<array{label: string, url: string, rank?: int}>  $candidates
     * @return list<SuggestHit>
     */
    private function completionHits(array $candidates): array
    {
        $hits = [];
        $seen = [];

        foreach ($this->sortCandidates($candidates) as $candidate) {
            $normalized = SearchNormalizer::textNormalize($candidate['label']);

            if (isset($seen[$normalized])) {
                continue;
            }

            $seen[$normalized] = true;
            $hits[] = new SuggestHit(
                $candidate['label'],
                route('storefront.shop.index', ['q' => $candidate['label']]),
            );

            if (count($hits) === self::LIMIT) {
                break;
            }
        }

        return $hits;
    }
}
