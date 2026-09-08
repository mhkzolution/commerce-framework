<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\Services\ProductSuggestQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class SuggestController extends Controller
{
    public function __construct(
        private readonly ProductSuggestQuery $suggestions,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $result = $this->suggestions->suggest(is_string($query) ? $query : '');

        return response()->json([
            'completions' => $this->serializeHits($result->completions),
            'products' => $this->serializeHits($result->products),
            'brands' => $this->serializeHits($result->brands),
            'categories' => $this->serializeHits($result->categories),
        ]);
    }

    /**
     * @param  list<SuggestHit>  $hits
     * @return list<array{label: string, url: string}>
     */
    private function serializeHits(array $hits): array
    {
        return array_map(
            static fn (SuggestHit $hit): array => [
                'label' => $hit->label,
                'url' => $hit->url,
            ],
            $hits,
        );
    }
}
