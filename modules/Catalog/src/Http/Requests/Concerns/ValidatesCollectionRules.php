<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Requests\Concerns;

trait ValidatesCollectionRules
{
    /**
     * @return array<string, mixed>
     */
    protected function collectionRuleFields(string $prefix = 'rules'): array
    {
        return [
            "{$prefix}.match" => ['nullable', 'in:all,any'],
            "{$prefix}.category_match" => ['nullable', 'in:all,any'],
            "{$prefix}.brand_match" => ['nullable', 'in:all,any'],
            "{$prefix}.tag_match" => ['nullable', 'in:all,any'],
            "{$prefix}.on_sale" => ['nullable', 'boolean'],
            "{$prefix}.category_ids" => ['nullable', 'array'],
            "{$prefix}.category_ids.*" => ['integer', 'exists:categories,id'],
            "{$prefix}.brand_uuids" => ['nullable', 'array'],
            "{$prefix}.brand_uuids.*" => ['uuid', 'exists:brands,uuid'],
            "{$prefix}.tag_ids" => ['nullable', 'array'],
            "{$prefix}.tag_ids.*" => ['integer', 'exists:tags,id'],
            "{$prefix}.price_min" => ['nullable', 'numeric', 'min:0'],
            "{$prefix}.price_max" => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectionRulesValidation(): array
    {
        return array_merge(
            [
                'rules' => ['nullable', 'array'],
                'rules.use_groups' => ['nullable', 'boolean'],
                'rules.groups' => ['nullable', 'array', 'max:5'],
            ],
            $this->collectionRuleFields('rules'),
            $this->collectionRuleFields('rules.groups.*'),
        );
    }
}
