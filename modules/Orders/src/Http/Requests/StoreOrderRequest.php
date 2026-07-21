<?php

declare(strict_types=1);

namespace Commerce\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_uuid' => ['nullable', 'uuid'],
            'currency' => ['nullable', 'string', 'size:3'],
            'channel' => ['nullable', 'string', 'max:30'],
            'billing_address' => ['nullable', 'array'],
            'shipping_address' => ['nullable', 'array'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.purchasable_uuid' => ['nullable', 'uuid'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $filledLines = collect($this->input('lines', []))
                ->filter(static fn ($line) => ! empty($line['purchasable_uuid']));

            if ($filledLines->isEmpty()) {
                $validator->errors()->add('lines', 'At least one line item is required.');

                return;
            }

            foreach ($filledLines as $line) {
                if (empty($line['quantity']) || (int) $line['quantity'] < 1) {
                    $validator->errors()->add('lines', 'Each line item must have a quantity of at least 1.');
                    break;
                }
            }
        });
    }
}
