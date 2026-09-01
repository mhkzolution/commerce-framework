<?php

declare(strict_types=1);

namespace Commerce\Cart\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateStorefrontNavigationRequest extends FormRequest
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
            'promo_enabled' => ['nullable', 'boolean'],
            'promo_message' => ['nullable', 'string', 'max:500'],
            'promo_dismissible' => ['nullable', 'boolean'],
            'items_json' => ['nullable', 'string', 'max:50000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'promo_enabled' => $this->boolean('promo_enabled'),
            'promo_dismissible' => $this->boolean('promo_dismissible'),
        ]);
    }
}
