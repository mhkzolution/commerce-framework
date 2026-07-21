<?php

declare(strict_types=1);

namespace Commerce\Contracts\Search;

interface SearchResultInterface
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getHits(): array;

    public function getTotal(): int;

    public function getPage(): int;

    public function getPerPage(): int;
}
