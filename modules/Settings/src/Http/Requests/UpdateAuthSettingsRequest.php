<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateAuthSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'recaptcha_enabled' => $this->boolean('recaptcha_enabled'),
            'line_enabled' => $this->boolean('line_enabled'),
            'registration_enabled' => $this->boolean('registration_enabled'),
        ]);
    }

    public function rules(): array
    {
        return [
            'recaptcha_enabled' => ['boolean'],
            'recaptcha_site_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_secret_key' => ['nullable', 'string', 'max:255'],
            'recaptcha_min_score' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'line_enabled' => ['boolean'],
            'line_channel_id' => ['nullable', 'string', 'max:255'],
            'line_channel_secret' => ['nullable', 'string', 'max:255'],
            'registration_enabled' => ['boolean'],
        ];
    }
}
