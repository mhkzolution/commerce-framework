<?php

declare(strict_types=1);

namespace Commerce\Core\Search;

use Commerce\Contracts\Search\SearchIndexInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ElasticsearchSearchIndex implements SearchIndexInterface
{
    public function index(string $index, string $id, array $document): void
    {
        $this->request('PUT', $this->documentPath($index, $id), array_merge($document, [
            'title' => $document['title'] ?? null,
            'body' => $document['body'] ?? null,
            'doc' => $document,
        ]));
    }

    public function delete(string $index, string $id): void
    {
        $this->request('DELETE', $this->documentPath($index, $id));
    }

    public function flush(string $index): void
    {
        $this->request('DELETE', $this->indexPath($index));
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(string $method, string $path, array $body = []): void
    {
        $host = rtrim((string) config('commerce.search.elasticsearch.host'), '/');
        $url = $host.'/'.ltrim($path, '/');

        try {
            $pending = Http::timeout(10);

            $response = match (strtoupper($method)) {
                'PUT' => $pending->put($url, $body),
                'DELETE' => $pending->delete($url),
                default => $pending->post($url, $body),
            };

            if (! $response->successful() && $response->status() !== 404) {
                Log::warning('Elasticsearch index request failed', [
                    'method' => $method,
                    'path' => $path,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Elasticsearch index request error', [
                'method' => $method,
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function indexPath(string $index): string
    {
        $prefix = trim((string) config('commerce.search.elasticsearch.index_prefix', 'commerce'), '/');

        return $prefix.'_'.$index;
    }

    private function documentPath(string $index, string $id): string
    {
        return $this->indexPath($index).'/_doc/'.urlencode($id);
    }
}
