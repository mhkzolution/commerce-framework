<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxSize = (int) config('media.max_upload_size', 10240);

        return [
            'file' => ['required', 'file', 'max:' . $maxSize],
            'folder_uuid' => ['nullable', 'uuid'],
        ];
    }
}
