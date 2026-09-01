<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ImportProductCsvRequest extends FormRequest
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
            'csv' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'csv.required' => 'Please choose a CSV file to import.',
            'csv.mimes' => 'The import file must be a CSV.',
            'csv.max' => 'The CSV file may not be larger than 50 MB.',
        ];
    }
}
