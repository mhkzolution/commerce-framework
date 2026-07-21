<?php

declare(strict_types=1);

namespace Commerce\Currency\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/** @property object $resource */
final class CurrencyResource extends JsonResource
{
    /**
     * @param  list<object>  $items
     */
    public static function collection(array $items): array
    {
        return array_map(
            fn (object $item): array => (new self($item))->toArray(request()),
            $items,
        );
    }

    public function toArray($request): array
    {
        return [
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'symbol' => $this->resource->symbol,
            'decimal_places' => $this->resource->decimal_places,
            'rate' => round($this->resource->rate_micro / 1_000_000, 6),
            'is_base' => $this->resource->is_base,
        ];
    }
}
