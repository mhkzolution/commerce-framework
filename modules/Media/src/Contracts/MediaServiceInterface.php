<?php

declare(strict_types=1);

namespace Commerce\Media\Contracts;

use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Models\Media;

interface MediaServiceInterface
{
    public function update(string $uuid, UpdateMediaData $data): Media;

    public function delete(string $uuid): void;

    /**
     * @param  list<string>  $uuids
     */
    public function deleteMany(array $uuids): int;

    /**
     * @param  list<string>  $uuids
     */
    public function moveMany(array $uuids, ?int $folderId): int;
}
