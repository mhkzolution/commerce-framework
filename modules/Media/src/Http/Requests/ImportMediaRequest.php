<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
            'folder_uuid' => ['nullable', 'uuid'],
        ];
    }
}
