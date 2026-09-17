<?php

declare(strict_types=1);

namespace Commerce\Documents\Services;

use Commerce\Core\Base\BaseQueryService;
use Commerce\Documents\Enums\DocumentStatus;
use Commerce\Documents\Enums\DocumentType;
use Commerce\Documents\Models\Document;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class DocumentQueryService extends BaseQueryService
{
    public function findByUuid(string $uuid): ?Document
    {
        return Document::query()->where('uuid', $uuid)->first();
    }

    /**
     * @return LengthAwarePaginator<int, Document>
     */
    public function paginate(?string $search = null, ?string $type = null, ?string $status = null, int $perPage = 25): LengthAwarePaginator
    {
        $typeEnum = DocumentType::tryFrom((string) $type);
        $statusEnum = DocumentStatus::tryFrom((string) $status);
        $number = $this->numberPrefix($search);

        return Document::query()
            ->with('customer')
            ->when($typeEnum instanceof DocumentType, static fn ($query) => $query->where('type', $typeEnum->value))
            ->when($statusEnum instanceof DocumentStatus, static fn ($query) => $query->where('status', $statusEnum->value))
            ->when($number !== null, static function ($query) use ($number): void {
                // Prefix match on documents.number (indexed). Do not search payload JSON —
                // buyer/order lookup needs denormalized indexed columns in a later version.
                $query->where('number', 'like', $number.'%');
            })
            ->latest('issued_at')
            ->latest('id')
            ->paginate($perPage);
    }

    private function numberPrefix(?string $search): ?string
    {
        $term = strtoupper(trim((string) $search));

        if ($term === '') {
            return null;
        }

        return str_replace(['%', '_'], '', $term);
    }
}
