<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Commerce\Catalog\Http\Requests\Concerns\ValidatesCollectionRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateCollectionRequest extends FormRequest
{
    use ValidatesCollectionRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_media_uuid' => ['nullable', 'uuid'],
            'type' => ['nullable', 'in:manual,automated'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2048'],
            'seo.og_image_media_uuid' => ['nullable', 'uuid'],
        ], $this->collectionRulesValidation());
    }
}
