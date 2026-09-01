<?php

declare(strict_types=1);

namespace Commerce\Contracts\Media;

use Illuminate\Http\UploadedFile;

interface MediaUploadServiceInterface
{
    /**
     * @param  resource|UploadedFile|string  $file
     */
    public function upload(mixed $file, ?string $folderUuid = null): object;

    public function registerExistingFile(string $disk, string $path, ?string $sourceUrl = null): ?object;
}
