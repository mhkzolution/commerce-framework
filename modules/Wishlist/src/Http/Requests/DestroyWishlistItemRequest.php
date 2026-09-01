<?php

declare(strict_types=1);

namespace Commerce\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DestroyWishlistItemRequest extends FormRequest
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
            'product_id' => ['required', 'string', 'uuid'],
            'variant_id' => ['nullable', 'string', 'uuid'],
        ];
    }
}
