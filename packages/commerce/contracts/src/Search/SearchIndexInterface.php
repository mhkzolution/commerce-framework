<?php

declare(strict_types=1);

namespace Commerce\Contracts\Search;

interface SearchIndexInterface
{
    /**
     * @param  array<string, mixed>  $document
     */
    public function index(string $index, string $id, array $document): void;

    public function delete(string $index, string $id): void;

    public function flush(string $index): void;
}
