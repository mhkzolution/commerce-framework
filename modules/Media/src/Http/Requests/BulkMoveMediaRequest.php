<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkMoveMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('folder_uuid') === '') {
            $this->merge(['folder_uuid' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'uuids' => ['required', 'array', 'min:1', 'max:500'],
            'uuids.*' => ['required', 'uuid'],
            'folder_uuid' => ['nullable', 'uuid', 'exists:media_folders,uuid'],
        ];
    }
}
