<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSiteIdentityRequest extends FormRequest
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
            'name' => ['nullable', 'string', 'max:255'],
            'logo_media_uuid' => ['nullable', 'uuid'],
            'favicon_media_uuid' => ['nullable', 'uuid'],
            'contact_address' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'social_facebook' => ['nullable', 'url', 'max:500'],
            'social_instagram' => ['nullable', 'url', 'max:500'],
            'social_tiktok' => ['nullable', 'url', 'max:500'],
            'social_line' => ['nullable', 'string', 'max:500'],
        ];
    }
}
