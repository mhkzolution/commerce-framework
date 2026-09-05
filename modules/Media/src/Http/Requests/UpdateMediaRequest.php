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
            'caption' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'folder_id' => ['nullable', 'integer', 'exists:media_folders,id'],
            'folder_uuid' => ['nullable', 'uuid'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:80'],
            'crop' => ['nullable', 'array'],
            'crop.preset' => ['nullable', 'string', 'max:40'],
            'crop.x' => ['nullable', 'integer', 'min:0'],
            'crop.y' => ['nullable', 'integer', 'min:0'],
            'crop.width' => ['nullable', 'integer', 'min:1'],
            'crop.height' => ['nullable', 'integer', 'min:1'],
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
