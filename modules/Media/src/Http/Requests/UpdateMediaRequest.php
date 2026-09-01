<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alt_text' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'folder_uuid' => ['nullable', 'uuid'],
            'search' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('folder_uuid') === '') {
            $this->merge(['folder_uuid' => null]);
        }
    }
}
