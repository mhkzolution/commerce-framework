<?php

declare(strict_types=1);

namespace Commerce\Contracts\Search;

interface SearchQueryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(string $index, string $query, array $filters = [], int $page = 1, int $perPage = 25): \Commerce\Contracts\Search\SearchResultInterface;
}
