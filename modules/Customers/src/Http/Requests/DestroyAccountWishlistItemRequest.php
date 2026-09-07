<?php

declare(strict_types=1);

namespace Commerce\Customers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DestroyAccountWishlistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'uuid'],
            'variant_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}
