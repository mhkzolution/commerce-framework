<?php

declare(strict_types=1);

namespace Commerce\Core\Http\Requests;

use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Core\Enums\FeatureStatus;
use Commerce\Core\Models\SystemFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateSystemFeatureStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! app()->bound(AuthorizationServiceInterface::class)) {
            return false;
        }

        return app(AuthorizationServiceInterface::class)->can($this->user(), 'system.feature.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(FeatureStatus::class)],
        ];
    }

    /**
     * @return list<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $feature = $this->route('systemFeature');

                if ($feature instanceof SystemFeature && $feature->is_core) {
                    $validator->errors()->add('status', __('commerce::admin.feature_core_locked'));
                }
            },
        ];
    }

    public function status(): FeatureStatus
    {
        return FeatureStatus::from((string) $this->validated('status'));
    }
}
