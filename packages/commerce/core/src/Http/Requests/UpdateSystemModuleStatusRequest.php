<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Requests;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Core\Enums\ModuleStatus;
use Commerce\Core\Models\SystemModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateSystemModuleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! app()->bound(AuthorizationServiceInterface::class)) {
            return false;
        }

        return app(AuthorizationServiceInterface::class)->can($this->user(), 'system.module.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(ModuleStatus::class)],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $module = $this->route('systemModule');

                if ($module instanceof SystemModule && $module->is_core) {
                    $validator->errors()->add('status', __('commerce::admin.module_core_locked'));
                }
            },
        ];
    }

    public function status(): ModuleStatus
    {
        return ModuleStatus::from((string) $this->validated('status'));
    }
}
