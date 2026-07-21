<?php

declare(strict_types=1);

namespace Commerce\Core\Search;

use Commerce\Contracts\Search\SearchResultInterface;

final class SearchResult implements SearchResultInterface
{
    /**
     * @param  list<array<string, mixed>>  $hits
     */
    public function __construct(
        private readonly array $hits,
        private readonly int $total,
        private readonly int $page,
        private readonly int $perPage,
    ) {}

    public function getHits(): array
    {
        return $this->hits;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }
}
