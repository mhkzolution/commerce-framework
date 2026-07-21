<?php

declare(strict_types=1);

namespace Commerce\Media\Contracts;

use Commerce\Media\DTO\CreateMediaFolderData;
use Commerce\Media\DTO\UpdateMediaFolderData;
use Commerce\Media\Models\MediaFolder;

interface MediaFolderServiceInterface
{
    public function create(CreateMediaFolderData $data): MediaFolder;

    public function update(string $uuid, UpdateMediaFolderData $data): MediaFolder;

    public function delete(string $uuid): void;
}
