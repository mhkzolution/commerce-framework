<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Controllers;

use Commerce\Cart\Services\HomepageNavigationQuery;
use Commerce\Product\DTO\SuggestHit;
use Commerce\Product\Services\ProductSuggestQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class SuggestController extends Controller
{
    public function __construct(
        private readonly ProductSuggestQuery $suggestions,
        private readonly HomepageNavigationQuery $navigation,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $result = $this->suggestions->suggest(
            is_string($query) ? $query : '',
            $this->navigation->shopFilterOptions(),
        );

        return response()->json([
            'completions' => $this->serializeHits($result->completions),
            'products' => $this->serializeHits($result->products, includeImage: true),
            'brands' => $this->serializeHits($result->brands),
            'categories' => $this->serializeHits($result->categories),
        ]);
    }

    /**
     * @param  list<SuggestHit>  $hits
     * @return list<array{label: string, url: string, image_url?: ?string}>
     */
    private function serializeHits(array $hits, bool $includeImage = false): array
    {
        return array_map(
            static function (SuggestHit $hit) use ($includeImage): array {
                $payload = [
                    'label' => $hit->label,
                    'url' => $hit->url,
                ];

                if ($includeImage) {
                    $payload['image_url'] = $hit->imageUrl;
                }

                return $payload;
            },
            $hits,
        );
    }
}
