<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Models\AttributeValue;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AttributeValueService extends BaseService
{
    public function __construct(
        private readonly ProductSearchIndexer $productSearchIndexer,
    ) {}

    public function allocateCode(int $attributeId, string $label, ?int $exceptId = null): string
    {
        $base = Str::slug($label, '_');
        if ($base === '') {
            $base = 'value';
        }

        $existing = AttributeValue::query()
            ->where('attribute_id', $attributeId)
            ->when($exceptId !== null, static fn ($query) => $query->where('id', '!=', $exceptId))
            ->pluck('code')
            ->all();

        $taken = array_fill_keys($existing, true);
        $code = $base;
        $suffix = 2;

        while (isset($taken[$code])) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    public function update(string $uuid, string $label, ?int $position = null): AttributeValue
    {
        $value = AttributeValue::query()->where('uuid', $uuid)->first();

        if ($value === null) {
            throw new EntityNotFoundException("Attribute value [{$uuid}] not found.");
        }

        $labelChanged = $value->label !== $label;
        $payload = ['label' => $label];

        if ($position !== null) {
            $payload['position'] = $position;
        }

        $value->update($payload);

        if ($labelChanged) {
            $productIds = DB::table('product_attribute_values')
                ->where('attribute_value_id', $value->id)
                ->distinct()
                ->pluck('product_id');

            foreach (Product::query()->whereKey($productIds)->get() as $product) {
                $this->productSearchIndexer->index($product);
            }
        }

        return $value->fresh();
    }
}
