<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertPromotionBannerRequest extends FormRequest
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
            'image_media_uuid' => ['required', 'uuid'],
            'url' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)/'],
            'open_in_new_tab' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }
}
