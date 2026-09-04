<?php

declare(strict_types=1);

namespace Commerce\WarehouseScanner\Services;

use Commerce\WarehouseScanner\Models\WarehouseScan;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ScanEventService
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        int $userId,
        string $mode,
        string $sku,
        string $action,
        ?string $variantUuid = null,
        ?int $quantity = null,
        array $meta = [],
    ): WarehouseScan {
        return WarehouseScan::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'mode' => $mode,
            'sku' => $sku,
            'variant_uuid' => $variantUuid,
            'action' => $action,
            'quantity' => $quantity,
            'meta' => array_merge($meta, [
                'device' => request()->userAgent(),
            ]),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recent(int $limit = 10): Collection
    {
        return WarehouseScan::query()
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (WarehouseScan $scan) => $this->toArray($scan));
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(WarehouseScan $scan): array
    {
        return [
            'uuid' => $scan->uuid,
            'created_at' => $scan->created_at?->toIso8601String(),
            'staff' => $scan->user?->name ?? '—',
            'mode' => $scan->mode,
            'sku' => $scan->sku,
            'action' => $scan->action,
            'quantity' => $scan->quantity,
            'device' => $scan->meta['device'] ?? null,
        ];
    }
}
