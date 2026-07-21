<?php

declare(strict_types=1);

namespace Commerce\Core\Search;

use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Contracts\Search\SearchResultInterface;
use Commerce\Core\Models\SearchDocument;

final class DatabaseSearchQuery implements SearchQueryInterface
{
    public function search(string $index, string $query, array $filters = [], int $page = 1, int $perPage = 25): SearchResultInterface
    {
        $builder = SearchDocument::query()->where('index_name', $index);

        if ($query !== '') {
            $builder->where(function ($inner) use ($query): void {
                $inner->where('title', 'like', "%{$query}%")
                    ->orWhere('body', 'like', "%{$query}%");
            });
        }

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $builder->whereJsonContains("payload->{$field}", $value);
        }

        $paginator = $builder->orderBy('title')->paginate($perPage, ['*'], 'page', $page);

        $hits = array_map(
            static fn (SearchDocument $document): array => $document->payload ?? [
                'id' => $document->document_id,
                'title' => $document->title,
                'body' => $document->body,
            ],
            $paginator->items(),
        );

        return new SearchResult(
            hits: $hits,
            total: $paginator->total(),
            page: $paginator->currentPage(),
            perPage: $paginator->perPage(),
        );
    }
}
