<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\Models\BarcodeTemplate;

final class BarcodeTemplateService
{
    /**
     * @var list<string>
     */
    private const FROZEN_FIELDS = [
        'paper_size',
        'rows',
        'columns',
        'label_width',
        'label_height',
        'margin_top',
        'margin_right',
        'margin_bottom',
        'margin_left',
        'spacing_horizontal',
        'spacing_vertical',
    ];

    public function ensureDefaults(): void
    {
        if (BarcodeTemplate::query()->exists()) {
            return;
        }

        foreach ($this->defaultPresets() as $preset) {
            BarcodeTemplate::query()->create($preset);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultPresets(): array
    {
        $labelStyle = config('barcode.label_style', []);
        $catalog = config('barcode.presets', []);
        $defaults = [];

        foreach (['a4_40', 'a4_24', 'a4_65', 'thermal_50x30'] as $code) {
            $preset = $catalog[$code] ?? null;
            if (! is_array($preset)) {
                continue;
            }

            $defaults[] = [
                'name' => $preset['name'],
                'preset_code' => $code,
                ...$this->geometryFromPreset($preset),
                'label_orientation' => 'vertical',
                ...$labelStyle,
                'show_name' => true,
                'show_sku' => true,
                'show_owner' => true,
                'show_barcode' => true,
                'is_favorite' => $code === 'a4_40',
                'is_default' => $code === 'a4_40',
            ];
        }

        return $defaults;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForWorkspace(): array
    {
        $this->ensureDefaults();

        return BarcodeTemplate::query()
            ->orderByDesc('is_default')
            ->orderByDesc('is_favorite')
            ->orderBy('name')
            ->get()
            ->map(fn (BarcodeTemplate $template) => $template->toSettingsArray())
            ->all();
    }

    public function defaultTemplate(): ?BarcodeTemplate
    {
        $this->ensureDefaults();

        return BarcodeTemplate::query()
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BarcodeTemplate
    {
        $data = $this->applyPresetGeometry($data);

        if (! empty($data['is_default'])) {
            $this->clearDefaultFlag();
        }

        return BarcodeTemplate::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BarcodeTemplate $template, array $data): BarcodeTemplate
    {
        $presetCode = (string) ($data['preset_code'] ?? $template->preset_code);
        $presetChanged = $presetCode !== (string) $template->preset_code;

        $data = $this->rejectFrozenWrites($data);

        if ($presetChanged) {
            $data['preset_code'] = $presetCode;
            $data = $this->applyPresetGeometry($data);
        }

        if (! empty($data['is_default'])) {
            $this->clearDefaultFlag($template->id);
        }

        $template->update($data);

        return $template->refresh();
    }

    public function duplicate(BarcodeTemplate $template): BarcodeTemplate
    {
        $copy = $template->replicate(['uuid', 'is_default']);
        $copy->name = $template->name.' (copy)';
        $copy->is_default = false;
        $copy->save();

        return $copy;
    }

    public function delete(BarcodeTemplate $template): void
    {
        if ($template->is_default) {
            $replacement = BarcodeTemplate::query()
                ->where('id', '!=', $template->id)
                ->orderByDesc('is_favorite')
                ->orderBy('id')
                ->first();

            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        $template->delete();
    }

    public function toggleFavorite(BarcodeTemplate $template): BarcodeTemplate
    {
        $template->update(['is_favorite' => ! $template->is_favorite]);

        return $template->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyPresetGeometry(array $data): array
    {
        $presetCode = (string) ($data['preset_code'] ?? 'a4_40');
        $preset = config("barcode.presets.{$presetCode}");

        if (! is_array($preset)) {
            throw new \InvalidArgumentException("Unknown barcode preset [{$presetCode}].");
        }

        $data = $this->rejectFrozenWrites($data);
        $data['preset_code'] = $presetCode;

        return [
            ...$data,
            ...$this->geometryFromPreset($preset),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rejectFrozenWrites(array $data): array
    {
        foreach (self::FROZEN_FIELDS as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $preset
     * @return array<string, mixed>
     */
    private function geometryFromPreset(array $preset): array
    {
        return [
            'paper_size' => $preset['paper_size'],
            'rows' => $preset['rows'],
            'columns' => $preset['columns'],
            'label_width' => $preset['label_width'],
            'label_height' => $preset['label_height'],
            'margin_top' => $preset['margin_top'],
            'margin_right' => $preset['margin_right'],
            'margin_bottom' => $preset['margin_bottom'],
            'margin_left' => $preset['margin_left'],
            'spacing_horizontal' => $preset['spacing_horizontal'],
            'spacing_vertical' => $preset['spacing_vertical'],
        ];
    }

    private function clearDefaultFlag(?int $exceptId = null): void
    {
        $query = BarcodeTemplate::query()->where('is_default', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }
}
