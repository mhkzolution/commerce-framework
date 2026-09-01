<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'logo_media_uuid' => ['nullable', 'uuid'],
            'is_active' => ['nullable', 'boolean'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2048'],
            'seo.og_image_media_uuid' => ['nullable', 'uuid'],
        ];
    }
}
