<?php

declare(strict_types=1);

namespace Commerce\Contracts\Media;

interface MediaUploadServiceInterface
{
    /**
     * @param  resource|\Illuminate\Http\UploadedFile|string  $file
     */
    public function upload(mixed $file, ?string $folderUuid = null): object;
}
