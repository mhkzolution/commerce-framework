<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Barcode\Models\BarcodeTemplate;

final class BarcodeTemplateService
{
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

        return [
            [
                'name' => 'A4 4×10',
                'paper_size' => 'a4',
                'rows' => 10,
                'columns' => 4,
                'margin_top' => 10,
                'margin_right' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'spacing_horizontal' => 2,
                'spacing_vertical' => 2,
                'label_width' => 48.5,
                'label_height' => 25.4,
                'label_orientation' => 'vertical',
                ...$labelStyle,
                'is_favorite' => true,
                'is_default' => true,
            ],
            [
                'name' => 'A4 3×8',
                'paper_size' => 'a4',
                'rows' => 8,
                'columns' => 3,
                'margin_top' => 10,
                'margin_right' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'spacing_horizontal' => 2,
                'spacing_vertical' => 2,
                'label_width' => 63.5,
                'label_height' => 33.9,
                'label_orientation' => 'vertical',
                ...$labelStyle,
                'is_favorite' => true,
                'is_default' => false,
            ],
            [
                'name' => 'Thermal 50×30',
                'paper_size' => 'thermal',
                'rows' => 1,
                'columns' => 1,
                'margin_top' => 0,
                'margin_right' => 0,
                'margin_bottom' => 0,
                'margin_left' => 0,
                'spacing_horizontal' => 0,
                'spacing_vertical' => 0,
                'label_width' => 50,
                'label_height' => 30,
                'label_orientation' => 'vertical',
                ...$labelStyle,
                'is_favorite' => false,
                'is_default' => false,
            ],
            [
                'name' => 'Thermal 40×30',
                'paper_size' => 'thermal',
                'rows' => 1,
                'columns' => 1,
                'margin_top' => 0,
                'margin_right' => 0,
                'margin_bottom' => 0,
                'margin_left' => 0,
                'spacing_horizontal' => 0,
                'spacing_vertical' => 0,
                'label_width' => 40,
                'label_height' => 30,
                'label_orientation' => 'vertical',
                ...$labelStyle,
                'is_favorite' => false,
                'is_default' => false,
            ],
        ];
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

    private function clearDefaultFlag(?int $exceptId = null): void
    {
        $query = BarcodeTemplate::query()->where('is_default', true);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update(['is_default' => false]);
    }
}
