<?php

declare(strict_types=1);

namespace Commerce\Media\Contracts;

use Commerce\Media\DTO\UpdateMediaData;
use Commerce\Media\Models\Media;

interface MediaServiceInterface
{
    public function update(string $uuid, UpdateMediaData $data): Media;

    public function delete(string $uuid, bool $force = false): void;

    /**
     * @param  list<string>  $uuids
     */
    public function deleteMany(array $uuids, bool $force = false): int;

    /**
     * @param  list<string>  $uuids
     */
    public function moveMany(array $uuids, ?int $folderId): int;

    /**
     * @param  list<string>  $uuids
     * @param  list<string>  $tags
     */
    public function tagMany(array $uuids, array $tags): int;

    /**
     * @param  list<string>  $uuids
     */
    public function regenerateMany(array $uuids): int;
}
