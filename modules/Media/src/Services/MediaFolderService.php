<?php

declare(strict_types=1);

namespace Commerce\Media\Services;

use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Media\Contracts\MediaFolderServiceInterface;
use Commerce\Media\DTO\CreateMediaFolderData;
use Commerce\Media\DTO\UpdateMediaFolderData;
use Commerce\Media\Models\MediaFolder;

final class MediaFolderService extends BaseService implements MediaFolderServiceInterface
{
    public function create(CreateMediaFolderData $data): MediaFolder
    {
        return MediaFolder::query()->create([
            'name' => $data->name,
            'parent_id' => $this->resolveParentId($data->parentUuid),
        ]);
    }

    public function update(string $uuid, UpdateMediaFolderData $data): MediaFolder
    {
        $folder = $this->findOrFail($uuid);
        $parentId = $this->resolveParentId($data->parentUuid);

        if ($parentId === $folder->id) {
            throw new DomainException('A folder cannot be its own parent.');
        }

        if ($parentId !== null && $this->isDescendant($folder->id, $parentId)) {
            throw new DomainException('Cannot move a folder into its own descendant.');
        }

        $folder->update([
            'name' => $data->name,
            'parent_id' => $parentId,
        ]);

        return $folder->fresh();
    }

    public function delete(string $uuid): void
    {
        $folder = $this->findOrFail($uuid);

        if ($folder->children()->exists()) {
            throw new DomainException('Cannot delete a folder that has subfolders.');
        }

        if ($folder->media()->exists()) {
            throw new DomainException('Cannot delete a folder that contains media.');
        }

        $folder->delete();
    }

    private function findOrFail(string $uuid): MediaFolder
    {
        $folder = MediaFolder::query()->where('uuid', $uuid)->first();

        if ($folder === null) {
            throw new EntityNotFoundException("Media folder [{$uuid}] not found.");
        }

        return $folder;
    }

    private function resolveParentId(?string $parentUuid): ?int
    {
        if ($parentUuid === null || $parentUuid === '') {
            return null;
        }

        return MediaFolder::query()->where('uuid', $parentUuid)->value('id');
    }

    private function isDescendant(int $folderId, int $candidateParentId): bool
    {
        $current = MediaFolder::query()->find($candidateParentId);

        while ($current !== null) {
            if ($current->id === $folderId) {
                return true;
            }

            $current = $current->parent;
        }

        return false;
    }
}
