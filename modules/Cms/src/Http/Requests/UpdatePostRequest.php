<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdatePostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_keys(config('cms.statuses', [])))],
            'published_at' => ['nullable', 'date'],
            'unpublish_at' => ['nullable', 'date'],
            'category_id' => ['nullable', 'integer', 'exists:cms_categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:cms_tags,id'],
            'author_uuid' => ['nullable', 'uuid', 'exists:users,uuid'],
            'featured_image_media_uuid' => ['nullable', 'uuid'],
            'is_featured' => ['nullable', 'boolean'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
            'seo.canonical_url' => ['nullable', 'url', 'max:2048'],
            'seo.og_image_media_uuid' => ['nullable', 'uuid'],
        ];
    }
}
