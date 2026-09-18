<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Commerce\Contracts\Storefront\StoreVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateStoreVisibilityRequest extends FormRequest
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
            'visibility' => ['required', 'string', Rule::in(StoreVisibility::values())],
        ];
    }
}
