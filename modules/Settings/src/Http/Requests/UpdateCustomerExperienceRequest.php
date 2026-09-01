<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Commerce\Settings\Services\CustomerExperienceConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCustomerExperienceRequest extends FormRequest
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
            'config' => ['required'],
            'quickView' => ['nullable', 'array'],
            'notifications' => ['nullable', 'array'],
            'navigation' => ['nullable', 'array'],
            'productCard' => ['nullable', 'array'],
            'productDetail' => ['nullable', 'array'],
            'cart' => ['nullable', 'array'],
            'checkout' => ['nullable', 'array'],
            'notifications.duration' => ['nullable', 'integer', Rule::in([5, 10, 15])],
            'notifications.position' => ['nullable', 'string', Rule::in(['top-left', 'top-right', 'bottom-left', 'bottom-right'])],
            'navigation.showAfter' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'navigation.position' => ['nullable', 'string', Rule::in(['bottom-left', 'bottom-right'])],
            'navigation.style' => ['nullable', 'string', Rule::in(['circle', 'square', 'pill'])],
            'navigation.target' => ['nullable', 'string', Rule::in(['top', 'filter', 'category'])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function configPayload(): array
    {
        $raw = $this->input('config');

        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            $raw = [];
        }

        foreach (CustomerExperienceConfig::SECTIONS as $section) {
            $sectionInput = $this->input($section);

            if (is_array($sectionInput)) {
                $raw[$section] = array_replace($raw[$section] ?? [], $sectionInput);
            }
        }

        return $raw;
    }
}
