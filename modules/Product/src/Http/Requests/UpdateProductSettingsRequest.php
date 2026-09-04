<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductSettingsRequest extends FormRequest
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
            'fallback_image_media_uuid' => ['nullable', 'uuid'],
            'sku_pattern' => ['nullable', 'string', 'max:255'],
        ];
    }
}
