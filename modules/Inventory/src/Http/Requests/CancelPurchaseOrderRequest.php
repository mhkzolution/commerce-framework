<?php

declare(strict_types=1);

namespace Commerce\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CancelPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
