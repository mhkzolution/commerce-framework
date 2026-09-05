<?php

declare(strict_types=1);

namespace Commerce\Media\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class BulkRegenerateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuids' => ['required', 'array', 'min:1', 'max:100'],
            'uuids.*' => ['required', 'uuid'],
        ];
    }
}
