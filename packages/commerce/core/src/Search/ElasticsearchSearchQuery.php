<?php

declare(strict_types=1);

namespace Commerce\Core\Search;

use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Contracts\Search\SearchResultInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ElasticsearchSearchQuery implements SearchQueryInterface
{
    public function __construct(private readonly DatabaseSearchQuery $fallback) {}

    public function search(string $index, string $query, array $filters = [], int $page = 1, int $perPage = 25): SearchResultInterface
    {
        $host = rtrim((string) config('commerce.search.elasticsearch.host'), '/');

        if ($host === '') {
            return $this->fallback->search($index, $query, $filters, $page, $perPage);
        }

        $prefix = trim((string) config('commerce.search.elasticsearch.index_prefix', 'commerce'), '/');
        $from = max(0, ($page - 1) * $perPage);

        $must = [];

        if ($query !== '') {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['title^3', 'body', 'doc.*'],
                ],
            ];
        }

        foreach ($filters as $field => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $must[] = ['term' => ['doc.'.$field => $value]];
        }

        $body = [
            'from' => $from,
            'size' => $perPage,
            'query' => $must === []
                ? ['match_all' => (object) []]
                : ['bool' => ['must' => $must]],
        ];

        try {
            $response = Http::timeout(10)
                ->post("{$host}/{$prefix}_{$index}/_search", $body);

            if (! $response->successful()) {
                return $this->fallback->search($index, $query, $filters, $page, $perPage);
            }

            $payload = $response->json();
            $hits = [];

            foreach ($payload['hits']['hits'] ?? [] as $hit) {
                $source = $hit['_source']['doc'] ?? $hit['_source'] ?? [];
                $source['id'] = $source['id'] ?? ($hit['_id'] ?? null);
                $hits[] = $source;
            }

            return new SearchResult(
                hits: $hits,
                total: (int) ($payload['hits']['total']['value'] ?? count($hits)),
                page: $page,
                perPage: $perPage,
            );
        } catch (\Throwable $exception) {
            Log::warning('Elasticsearch search failed, using database fallback', [
                'index' => $index,
                'error' => $exception->getMessage(),
            ]);

            return $this->fallback->search($index, $query, $filters, $page, $perPage);
        }
    }
}
