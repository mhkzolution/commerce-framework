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

        $mimes = implode(',', config('media.allowed_mimes', []));

        return [
            'file' => array_values(array_filter([
                'required',
                'file',
                'max:'.$maxSize,
                $mimes !== '' ? 'mimetypes:'.$mimes : null,
            ])),
            'folder_uuid' => ['nullable', 'uuid'],
        ];
    }
}
