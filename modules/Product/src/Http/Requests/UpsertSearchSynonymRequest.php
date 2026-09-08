<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertSearchSynonymRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $fromTermRule = Rule::unique('product_search_synonyms', 'from_term');
        $synonymId = $this->route('search_synonym');

        if ($synonymId !== null) {
            $fromTermRule->ignore($synonymId);
        }

        return [
            'from_term' => ['required', 'string', 'max:255', $fromTermRule],
            'to_term' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (['from_term', 'to_term'] as $term) {
            $value = $this->input($term);

            if (is_string($value)) {
                $normalized[$term] = SearchNormalizer::textNormalize($value);
            }
        }

        $this->merge($normalized);
    }
}
