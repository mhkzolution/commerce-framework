<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Catalog\Models\Brand;
use Commerce\Contracts\Media\MediaQueryServiceInterface;
use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\DTO\SuggestResult;
use Commerce\Product\Models\Product;
use Commerce\Product\Models\ProductMedia;
use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Database\Query\JoinClause;
use Normalizer;

final class ProductSuggestQuery
{
    private const LIMIT = 5;

    private const PRODUCT_CANDIDATE_LIMIT = 100;

    /**
     * @var array<string, list<string>>
     */
    private const CATEGORY_ALIASES = [
        'swim' => ['kids-swimwear', 'kids-swim-shirts'],
        'dress' => ['kids-dresses'],
        'toy' => ['toys'],
        'book' => ['kids-books'],
        'shoes' => ['kids-shoes'],
    ];

    public function __construct(
        private readonly ?MediaQueryServiceInterface $media = null,
    ) {}

    public function suggest(string $q, iterable $categories = []): SuggestResult
    {
        $tokens = SearchNormalizer::tokenize($q);

        if ($tokens === [] || $this->hasShortToken($tokens)) {
            return new SuggestResult;
        }

        $products = $this->productCandidates($tokens);
        $brands = $this->brandCandidates($tokens);
        $categoryCandidates = $this->categoryCandidates($tokens, $categories);
        $completionCandidates = array_merge($products, $brands, $categoryCandidates);

        return new SuggestResult(
            completions: $this->completionHits($completionCandidates),
            products: $this->hits($products),
            brands: $this->hits($brands),
            categories: $this->hits($categoryCandidates),
        );
    }

    /**
     * @param  list<string>  $tokens
     */
    private function hasShortToken(array $tokens): bool
    {
        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $tokens
     * @return list<array{label: string, url: string, rank: int, imageUrl?: ?string}>
     */
    private function productCandidates(array $tokens): array
    {
        $candidates = [];
        $titlePatternGroups = $this->titlePrefilterPatterns($tokens);
        $products = Product::query()
            ->visibleOnStorefront()
            ->with('media')
            ->join('search_documents as suggest_documents', function (JoinClause $join): void {
                $join->on('suggest_documents.document_id', '=', 'products.uuid')
                    ->where('suggest_documents.index_name', ProductSearchIndexer::INDEX);
            })
            ->select('products.*')
            ->addSelect('suggest_documents.title as suggest_title')
            ->where(function ($query) use ($titlePatternGroups): void {
                foreach ($titlePatternGroups as $patterns) {
                    $query->where(function ($query) use ($patterns): void {
                        foreach ($patterns as $index => $pattern) {
                            $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                            $query->{$method}(
                                'suggest_documents.title like ? escape \'!\'',
                                [$pattern],
                            );
                        }
                    });
                }
            })
            ->orderByRaw('length(suggest_documents.title)')
            ->orderBy('suggest_documents.title')
            ->limit(self::PRODUCT_CANDIDATE_LIMIT)
            ->get();

        foreach ($products as $product) {
            $title = (string) $product->getAttribute('suggest_title');

            if (! $this->matchesName($title, $tokens)) {
                continue;
            }

            $candidates[] = [
                'label' => $title,
                'url' => route('storefront.products.show', (string) $product->slug),
                'rank' => (int) $product->id,
                'imageUrl' => $this->productImageUrl($product),
            ];
        }

        return $this->sortCandidates($candidates);
    }

    /**
     * @param  list<string>  $tokens
     * @return list<list<string>>
     */
    private function titlePrefilterPatterns(array $tokens): array
    {
        $groups = [];

        foreach ($tokens as $token) {
            $patterns = [];

            foreach ($this->unicodeForms($token) as $form) {
                $escaped = str_replace(
                    ['!', '\\', '%', '_'],
                    ['!!', '!\\', '!%', '!_'],
                    $form,
                );
                $patterns[] = '%'.$escaped.'%';
            }

            $groups[] = array_values(array_unique($patterns));
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    private function unicodeForms(string $token): array
    {
        $forms = [$token];
        $nfd = Normalizer::normalize($token, Normalizer::FORM_D);

        if (is_string($nfd)) {
            $forms[] = mb_strtolower($nfd);
        }

        return array_values(array_unique($forms));
    }

    /**
     * @param  list<string>  $tokens
     * @return list<array{label: string, url: string}>
     */
    private function brandCandidates(array $tokens): array
    {
        $candidates = [];

        foreach (Brand::query()->where('is_active', true)->get() as $brand) {
            $name = (string) $brand->name;
            $slug = trim((string) $brand->slug);

            if ($slug === '' || ! $this->matchesName($name, $tokens)) {
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
     * @param  list<string>  $tokens
     * @return list<array{label: string, url: string}>
     */
    private function categoryCandidates(array $tokens, iterable $categories): array
    {
        $candidates = [];

        foreach ($this->flattenCategories($categories) as $category) {
            if (! $this->matchesCategory($category, $tokens)) {
                continue;
            }

            $candidates[] = [
                'label' => $category->name,
                'url' => route('storefront.shop.index', ['category' => $category->slug]),
            ];
        }

        return $this->sortCandidates($candidates);
    }

    /**
     * @param  iterable<mixed>  $categories
     * @return list<object>
     */
    private function flattenCategories(iterable $categories): array
    {
        $flat = [];

        foreach ($categories as $category) {
            $flat[] = $category;

            if (is_object($category) && isset($category->children) && is_iterable($category->children)) {
                foreach ($this->flattenCategories($category->children) as $child) {
                    $flat[] = $child;
                }
            }
        }

        return $flat;
    }

    /**
     * @param  list<string>  $queryTokens
     */
    private function matchesCategory(object $category, array $queryTokens): bool
    {
        $name = SearchNormalizer::textNormalize((string) $category->name);
        $slug = (string) $category->slug;

        foreach ($queryTokens as $queryToken) {
            if (! $this->tokenMatchesCategory($queryToken, $name, $slug)) {
                return false;
            }
        }

        return true;
    }

    private function tokenMatchesCategory(string $queryToken, string $normalizedName, string $slug): bool
    {
        if (mb_strpos($normalizedName, $queryToken) !== false) {
            return true;
        }

        foreach (self::CATEGORY_ALIASES as $alias => $slugs) {
            if (! in_array($slug, $slugs, true)) {
                continue;
            }

            if ($queryToken === $alias || str_starts_with($alias, $queryToken)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $queryTokens
     */
    private function matchesName(string $label, array $queryTokens): bool
    {
        $nameTokens = SearchNormalizer::tokenize($label);

        foreach ($queryTokens as $queryToken) {
            $hit = false;

            foreach ($nameTokens as $nameToken) {
                if (str_starts_with($nameToken, $queryToken)) {
                    $hit = true;
                    break;
                }
            }

            if (! $hit) {
                return false;
            }
        }

        return true;
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
     * @param  list<array{label: string, url: string, rank?: int, imageUrl?: ?string}>  $candidates
     * @return list<SuggestHit>
     */
    private function hits(array $candidates): array
    {
        return array_map(
            static fn (array $candidate): SuggestHit => new SuggestHit(
                $candidate['label'],
                $candidate['url'],
                $candidate['imageUrl'] ?? null,
            ),
            array_slice($candidates, 0, self::LIMIT),
        );
    }

    private function productImageUrl(Product $product): ?string
    {
        if ($this->media === null) {
            return null;
        }

        $mediaRows = $product->relationLoaded('media')
            ? $product->media
            : $product->media()->get();

        $ordered = $mediaRows
            ->sortBy(static function (ProductMedia $row): string {
                $priority = $row->is_primary ? '0' : '1';

                return $priority.'-'.str_pad((string) (int) $row->position, 6, '0', STR_PAD_LEFT);
            })
            ->values();

        foreach ($ordered as $row) {
            $uuid = is_string($row->media_uuid) ? $row->media_uuid : null;
            if ($uuid === null || $uuid === '') {
                continue;
            }

            $url = $this->media->getUrl($uuid, 'card')
                ?? $this->media->getUrl($uuid, 'medium')
                ?? $this->media->getUrl($uuid);

            if (is_string($url) && $url !== '') {
                return $url;
            }
        }

        return null;
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
