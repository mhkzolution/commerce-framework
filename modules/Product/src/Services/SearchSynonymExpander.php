<?php

declare(strict_types=1);

namespace Commerce\Product\Services;

use Commerce\Product\Models\SearchSynonym;
use Commerce\Product\Support\SearchNormalizer;

final class SearchSynonymExpander
{
    /**
     * @var array<string, string>
     */
    private array $synonyms;

    public function __construct()
    {
        $this->synonyms = SearchSynonym::query()
            ->pluck('to_term', 'from_term')
            ->all();
    }

    /**
     * @param  list<string>  $tokens
     * @return list<string>
     */
    public function expand(array $tokens): array
    {
        $expanded = [];

        foreach ($tokens as $token) {
            $normalized = SearchNormalizer::textNormalize($token);

            if (isset($this->synonyms[$normalized])) {
                array_push($expanded, ...SearchNormalizer::tokenize($this->synonyms[$normalized]));

                continue;
            }

            $expanded[] = $normalized;
        }

        return $expanded;
    }
}
