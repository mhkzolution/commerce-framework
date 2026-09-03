<?php

declare(strict_types=1);

namespace Commerce\Barcode\Services;

use Commerce\Marketplace\Models\Seller;

final class BarcodeWorkspaceService
{
    public function __construct(
        private readonly BarcodeTemplateService $templateService,
    ) {}

    /**
     * @return array{
     *     templates: list<array<string, mixed>>,
     *     default_template: array<string, mixed>,
     *     paper_sizes: array<string, array{label: string, width_mm: int|float, height_mm: int|float}>,
     *     sellers: list<array{uuid: string, name: string}>
     * }
     */
    public function config(): array
    {
        $templates = $this->templateService->listForWorkspace();
        $default = $this->templateService->defaultTemplate();

        return [
            'templates' => $templates,
            'default_template' => $default?->toSettingsArray() ?? $templates[0] ?? [],
            'paper_sizes' => config('barcode.paper_sizes', []),
            'sellers' => $this->sellers(),
        ];
    }

    /**
     * @return list<array{uuid: string, name: string}>
     */
    private function sellers(): array
    {
        if (! class_exists(Seller::class)) {
            return [];
        }

        return Seller::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['uuid', 'name'])
            ->map(static fn ($seller): array => [
                'uuid' => (string) $seller->uuid,
                'name' => (string) $seller->name,
            ])
            ->all();
    }
}
