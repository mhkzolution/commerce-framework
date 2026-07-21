<?php

declare(strict_types=1);

namespace Commerce\Support\DTO;

abstract readonly class DataTransferObject
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
