<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Resources;

use Commerce\Product\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
final class ProductWorkspaceResource extends JsonResource
{
    /**
     * @param  array<string, mixed>  $workspaceState
     */
    public function __construct(
        $resource,
        private readonly array $workspaceState,
    ) {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'status' => $this->status,
            'type' => $this->type,
            'workspace' => $this->workspaceState,
        ];
    }
}
