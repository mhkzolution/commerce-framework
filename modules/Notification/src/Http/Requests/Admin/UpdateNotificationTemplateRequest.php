<?php

declare(strict_types=1);

namespace Commerce\Notification\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNotificationTemplateRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'view' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
