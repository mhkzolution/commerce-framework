<?php

declare(strict_types=1);

namespace Commerce\Settings\Http\Requests;

use Commerce\Settings\Http\Requests\Concerns\NormalizesFooterConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateFooterRequest extends FormRequest
{
    use NormalizesFooterConfig;

    private const SUPPORTED_SCHEMA_VERSIONS = [1];

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
            'config' => ['required', 'array'],
            'config.schema_version' => ['required', 'integer', Rule::in(self::SUPPORTED_SCHEMA_VERSIONS)],
            'config.enabled' => ['required', 'boolean'],
            'config.layout' => ['required', 'array'],
            'config.layout.columns' => ['required', 'integer', 'min:1', 'max:6'],
            'config.layout.color_scheme' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.layout.surface' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.layout.variant' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.layout.divider_style' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.layout.padding' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.layout.spacing' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.sections' => ['required', 'array'],
            'config.sections.*.id' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'config.sections.*.type' => ['required', 'string', 'max:100'],
            'config.sections.*.enabled' => ['required', 'boolean'],
            'config.sections.*.settings' => ['nullable', 'array'],
            'config.sections.*.visibility' => ['nullable', 'array'],
        ];
    }
}
