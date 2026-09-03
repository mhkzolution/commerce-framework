<?php

declare(strict_types=1);

namespace Commerce\Navigation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateNavigationMenuRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'items' => ['nullable', 'array', 'max:50'],
            'items.*.label' => ['nullable', 'string', 'max:120'],
            'items.*.url' => ['nullable', 'string', 'max:2048'],
            'items.*.is_visible' => ['sometimes', 'boolean'],
            'items.*.footer_enabled' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return list<array{label: string, url: string, is_visible: bool, footer_enabled: bool}>
     */
    public function itemsPayload(): array
    {
        $items = [];

        foreach ($this->validated('items') ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));

            if ($label === '' || $url === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'url' => $url,
                'is_visible' => $this->toBool($item['is_visible'] ?? true),
                'footer_enabled' => $this->toBool($item['footer_enabled'] ?? true),
            ];
        }

        return $items;
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array($value, [1, '1', 'true', 'on', 'yes'], true);
    }
}
