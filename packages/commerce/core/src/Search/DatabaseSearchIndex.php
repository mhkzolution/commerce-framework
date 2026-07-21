<?php

declare(strict_types=1);

namespace Commerce\Core\Search;

use Commerce\Contracts\Search\SearchIndexInterface;
use Commerce\Core\Models\SearchDocument;

final class DatabaseSearchIndex implements SearchIndexInterface
{
    public function index(string $index, string $id, array $document): void
    {
        SearchDocument::query()->updateOrCreate(
            ['index_name' => $index, 'document_id' => $id],
            [
                'title' => isset($document['title']) ? (string) $document['title'] : null,
                'body' => isset($document['body']) ? (string) $document['body'] : null,
                'payload' => $document,
            ],
        );
    }

    public function delete(string $index, string $id): void
    {
        SearchDocument::query()
            ->where('index_name', $index)
            ->where('document_id', $id)
            ->delete();
    }

    public function flush(string $index): void
    {
        SearchDocument::query()->where('index_name', $index)->delete();
    }
}
