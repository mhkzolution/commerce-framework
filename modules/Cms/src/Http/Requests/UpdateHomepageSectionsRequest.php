<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Requests;

use Commerce\Cms\Models\HomepageSection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateHomepageSectionsRequest extends FormRequest
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
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.uuid' => ['required', 'uuid', 'exists:cms_homepage_sections,uuid'],
            'sections.*.layout' => ['required', Rule::in([
                HomepageSection::LAYOUT_SLIDER,
                HomepageSection::LAYOUT_GRID,
                HomepageSection::LAYOUT_FULL_WIDTH,
            ])],
            'sections.*.sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'sections.*.is_active' => ['nullable', 'boolean'],
            'sections.*.columns' => ['nullable', 'integer', 'min:1', 'max:4'],
        ];
    }
}
