<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderShipmentRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, int>
     */
    public function quantitiesByLineUuid(): array
    {
        $quantities = [];

        foreach ($this->validated('items', []) as $uuid => $item) {
            if (! is_string($uuid) || ! is_array($item)) {
                continue;
            }

            $quantities[$uuid] = (int) ($item['quantity'] ?? 0);
        }

        return $quantities;
    }
}
