<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWebsiteSettingsRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo_media_uuid' => ['nullable', 'uuid'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'social' => ['nullable', 'array'],
            'social.facebook' => ['nullable', 'string', 'max:2048'],
            'social.instagram' => ['nullable', 'string', 'max:2048'],
            'social.tiktok' => ['nullable', 'string', 'max:2048'],
            'social.line' => ['nullable', 'string', 'max:2048'],
            'seo_title_suffix' => ['nullable', 'string', 'max:120'],
            'seo_default_description' => ['nullable', 'string', 'max:2000'],
            'seo_og_image_media_uuid' => ['nullable', 'uuid'],
        ];
    }

    /**
     * @return array{name: string, description: ?string, logo_media_uuid: ?string, email: ?string, phone: ?string}
     */
    public function storePayload(): array
    {
        $logo = $this->validated('logo_media_uuid');

        return [
            'name' => trim((string) $this->validated('name')),
            'description' => $this->nullableString($this->validated('description')),
            'logo_media_uuid' => is_string($logo) && $logo !== '' ? $logo : null,
            'email' => $this->nullableString($this->validated('email')),
            'phone' => $this->nullableString($this->validated('phone')),
        ];
    }

    /**
     * @return array{facebook: ?string, instagram: ?string, tiktok: ?string, line: ?string}
     */
    public function socialPayload(): array
    {
        $social = $this->validated('social') ?? [];

        return [
            'facebook' => $this->nullableString($social['facebook'] ?? null),
            'instagram' => $this->nullableString($social['instagram'] ?? null),
            'tiktok' => $this->nullableString($social['tiktok'] ?? null),
            'line' => $this->nullableString($social['line'] ?? null),
        ];
    }

    /**
     * @return array{seo.title_suffix: ?string, seo.default_description: ?string, seo.default_og_image_media_uuid: ?string}
     */
    public function websitePayload(): array
    {
        $og = $this->validated('seo_og_image_media_uuid');

        return [
            'seo.title_suffix' => $this->nullableString($this->validated('seo_title_suffix')),
            'seo.default_description' => $this->nullableString($this->validated('seo_default_description')),
            'seo.default_og_image_media_uuid' => is_string($og) && $og !== '' ? $og : null,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
