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
            'social' => ['nullable', 'array'],
            'social.facebook' => ['nullable', 'string', 'max:2048'],
            'social.instagram' => ['nullable', 'string', 'max:2048'],
            'social.tiktok' => ['nullable', 'string', 'max:2048'],
            'social.line' => ['nullable', 'string', 'max:2048'],
        ];
    }

    /**
     * @return array{name: string, description: ?string, logo_media_uuid: ?string}
     */
    public function storePayload(): array
    {
        $logo = $this->validated('logo_media_uuid');

        return [
            'name' => trim((string) $this->validated('name')),
            'description' => $this->nullableString($this->validated('description')),
            'logo_media_uuid' => is_string($logo) && $logo !== '' ? $logo : null,
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

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
