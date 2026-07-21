<?php

declare(strict_types=1);

namespace Commerce\Catalog\Services;

use Commerce\Catalog\Contracts\AttributeServiceInterface;
use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\UpdateAttributeData;
use Commerce\Catalog\Models\Attribute;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Illuminate\Support\Str;

final class AttributeService extends BaseService implements AttributeServiceInterface
{
    public function create(CreateAttributeData $data): Attribute
    {
        return Attribute::query()->create([
            'code' => Str::slug($data->code, '_'),
            'name' => $data->name,
            'type' => $data->type,
            'is_filterable' => $data->isFilterable,
            'is_required' => $data->isRequired,
            'is_visible' => $data->isVisible,
            'position' => $data->position,
            'options' => $data->options,
        ]);
    }

    public function update(string $uuid, UpdateAttributeData $data): Attribute
    {
        $attribute = $this->findOrFail($uuid);

        $attribute->update([
            'code' => Str::slug($data->code, '_'),
            'name' => $data->name,
            'type' => $data->type,
            'is_filterable' => $data->isFilterable,
            'is_required' => $data->isRequired,
            'is_visible' => $data->isVisible,
            'position' => $data->position,
            'options' => $data->options,
        ]);

        return $attribute->fresh();
    }

    public function delete(string $uuid): void
    {
        $this->findOrFail($uuid)->delete();
    }

    private function findOrFail(string $uuid): Attribute
    {
        $attribute = Attribute::query()->where('uuid', $uuid)->first();

        if ($attribute === null) {
            throw new EntityNotFoundException("Attribute [{$uuid}] not found.");
        }

        return $attribute;
    }
}
