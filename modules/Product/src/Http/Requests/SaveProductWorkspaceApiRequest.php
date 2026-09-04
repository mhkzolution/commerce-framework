<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SaveProductWorkspaceApiRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(array_keys(config('product.statuses', [])))],
            'visibility' => ['required', 'string', Rule::in(array_keys(config('product.visibilities', [])))],
            'brand_uuid' => ['nullable', 'uuid'],
            'seller_uuid' => ['nullable', 'uuid', 'exists:marketplace_sellers,uuid'],
            'attribute_set_id' => ['nullable', 'integer', 'exists:attribute_sets,id'],
            'workspace_payload' => ['required'],
            'publish_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'collection_ids' => ['nullable', 'array'],
            'collection_ids.*' => ['integer'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'media_uuids' => ['nullable', 'array'],
            'media_uuids.*' => ['uuid'],
            'attributes' => ['nullable', 'array'],
            'seo' => ['nullable', 'array'],
            'seo.meta_title' => ['nullable', 'string', 'max:255'],
            'seo.meta_description' => ['nullable', 'string', 'max:500'],
            'seo.meta_keywords' => ['nullable', 'string', 'max:255'],
            'seo.canonical_url' => ['nullable', 'url', 'max:255'],
            'seo.og_image_media_uuid' => ['nullable', 'uuid'],
            'meta' => ['nullable', 'array'],
            'meta.external_id' => ['nullable', 'string', 'max:255'],
            'meta.notes' => ['nullable', 'string'],
            'meta.custom_json' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('status') === 'scheduled' && ! $this->filled('publish_at')) {
                $validator->errors()->add('publish_at', 'Publish date is required for scheduled products.');
            }
        });
    }
}
