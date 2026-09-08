<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
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
            'type' => ['nullable', 'string', Rule::in(['simple', 'variable'])],
            'backorder_policy' => ['nullable', 'string', Rule::in(['deny', 'notify', 'allow'])],
            'backorderPolicy' => ['nullable', 'string', Rule::in(['deny', 'notify', 'allow'])],
            'onHand' => ['nullable', 'integer', 'min:0'],
            'on_hand' => ['nullable', 'integer', 'min:0'],
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

            $rawPayload = $this->input('workspace_payload');
            $payload = is_array($rawPayload) ? $rawPayload : json_decode((string) $rawPayload, true);
            $nested = Validator::make(is_array($payload) ? $payload : [], [
                'product.type' => ['sometimes', 'string', Rule::in(['simple', 'variable'])],
                'product.backorderPolicy' => ['sometimes', 'string', Rule::in(['deny', 'notify', 'allow'])],
                'product.backorder_policy' => ['sometimes', 'string', Rule::in(['deny', 'notify', 'allow'])],
                'product.onHand' => ['nullable', 'integer', 'min:0'],
                'product.on_hand' => ['nullable', 'integer', 'min:0'],
                'variants.*.onHand' => ['nullable', 'integer', 'min:0'],
                'variants.*.on_hand' => ['nullable', 'integer', 'min:0'],
            ]);

            foreach ($nested->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $validator->errors()->add('workspace_payload.'.$field, $message);
                }
            }
        });
    }
}
