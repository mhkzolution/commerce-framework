<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Resources;

use Commerce\Catalog\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attribute */
final class AttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'is_filterable' => $this->is_filterable,
            'is_required' => $this->is_required,
            'is_visible' => $this->is_visible,
            'position' => $this->position,
            'options' => $this->options,
        ];
    }
}
