<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Catalog\Models\Brand;
use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\DTO\SuggestResult;
use Commerce\Product\Models\Product;
use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Database\Query\JoinClause;

final class ProductSuggestQuery
{
    private const LIMIT = 5;

    private const PRODUCT_CANDIDATE_LIMIT = 100;

    public function __construct(
        private readonly HomepageNavigationQuery $homepageNavigation,
    ) {}

    public function suggest(string $q): SuggestResult
    {
        $prefix = SearchNormalizer::textNormalize($q);

        if (mb_strlen($prefix) < 2) {
            return new SuggestResult;
        }

        $products = $this->productCandidates($prefix);
        $brands = $this->brandCandidates($prefix);
        $categories = $this->categoryCandidates($prefix);
        $completionCandidates = array_merge($products, $brands, $categories);

        return new SuggestResult(
            completions: $this->completionHits($completionCandidates),
            products: $this->hits($products),
            brands: $this->hits($brands),
            categories: $this->hits($categories),
        );
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    private function productCandidates(string $prefix): array
    {
        $candidates = [];
        $products = Product::query()
            ->visibleOnStorefront()
            ->join('search_documents as suggest_documents', function (JoinClause $join): void {
                $join->on('suggest_documents.document_id', '=', 'products.uuid')
                    ->where('suggest_documents.index_name', ProductSearchIndexer::INDEX);
            })
            ->select('products.*')
            ->addSelect('suggest_documents.title as suggest_title')
            ->where('suggest_documents.title', 'like', $prefix.'%')
            ->orderBy('products.id')
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
            ];
        }

        return $this->sortCandidates($candidates);
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
    private function categoryCandidates(string $prefix): array
    {
        $candidates = [];

        foreach ($this->homepageNavigation->shopFilterOptions() as $category) {
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
     * @param  list<array{label: string, url: string}>  $candidates
     * @return list<array{label: string, url: string}>
     */
    private function sortCandidates(array $candidates): array
    {
        usort($candidates, static function (array $left, array $right): int {
            $length = mb_strlen($left['label']) <=> mb_strlen($right['label']);

            return $length !== 0 ? $length : strnatcasecmp($left['label'], $right['label']);
        });

        return $candidates;
    }

    /**
     * @param  list<array{label: string, url: string}>  $candidates
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
     * @param  list<array{label: string, url: string}>  $candidates
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
