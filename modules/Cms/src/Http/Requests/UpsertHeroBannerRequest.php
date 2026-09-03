<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Requests;

use Commerce\Cms\Models\HeroBanner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpsertHeroBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('type')) {
            $this->merge(['type' => HeroBanner::TYPE_IMAGE]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([HeroBanner::TYPE_IMAGE, HeroBanner::TYPE_VIDEO])],
            'image_media_uuid' => ['required', 'uuid'],
            'mobile_image_media_uuid' => ['nullable', 'uuid'],
            'video_media_uuid' => ['nullable', 'required_if:type,'.HeroBanner::TYPE_VIDEO, 'uuid'],
            'mobile_video_media_uuid' => ['nullable', 'uuid'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
