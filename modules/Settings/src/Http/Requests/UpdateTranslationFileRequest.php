<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateTranslationFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:10'],
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
