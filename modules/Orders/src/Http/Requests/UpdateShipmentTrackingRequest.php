<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateShipmentTrackingRequest extends FormRequest
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
            'carrier' => ['nullable', 'string', 'max:80'],
            'tracking_number' => ['nullable', 'string', 'max:80'],
            'tracking_url' => ['nullable', 'string', 'max:255'],
        ];
    }
}
