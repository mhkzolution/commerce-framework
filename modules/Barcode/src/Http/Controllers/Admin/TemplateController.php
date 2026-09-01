<?php

declare(strict_types=1);

namespace Commerce\Barcode\Http\Controllers\Admin;

use Commerce\Barcode\Http\Requests\StoreBarcodeTemplateRequest;
use Commerce\Barcode\Http\Requests\UpdateBarcodeTemplateRequest;
use Commerce\Barcode\Models\BarcodeTemplate;
use Commerce\Barcode\Services\BarcodeTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TemplateController
{
    public function __construct(
        private readonly BarcodeTemplateService $templateService,
    ) {}

    public function index(): View
    {
        $this->templateService->ensureDefaults();

        return view('barcode::admin.templates.index', [
            'templates' => BarcodeTemplate::query()
                ->orderByDesc('is_default')
                ->orderByDesc('is_favorite')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('barcode::admin.templates.create', [
            'template' => null,
        ]);
    }

    public function store(StoreBarcodeTemplateRequest $request): RedirectResponse
    {
        $this->templateService->create($request->validated());

        return redirect()
            ->route('admin.barcode.templates.index')
            ->with('success', __('barcode::admin.templates.saved'));
    }

    public function edit(BarcodeTemplate $template): View
    {
        return view('barcode::admin.templates.edit', [
            'template' => $template,
        ]);
    }

    public function update(UpdateBarcodeTemplateRequest $request, BarcodeTemplate $template): RedirectResponse
    {
        $this->templateService->update($template, $request->validated());

        return redirect()
            ->route('admin.barcode.templates.index')
            ->with('success', __('barcode::admin.templates.saved'));
    }

    public function destroy(BarcodeTemplate $template): RedirectResponse
    {
        $this->templateService->delete($template);

        return redirect()
            ->route('admin.barcode.templates.index')
            ->with('success', __('barcode::admin.templates.deleted'));
    }

    public function duplicate(BarcodeTemplate $template): RedirectResponse
    {
        $this->templateService->duplicate($template);

        return redirect()
            ->route('admin.barcode.templates.index')
            ->with('success', __('barcode::admin.templates.duplicated'));
    }

    public function favorite(BarcodeTemplate $template): RedirectResponse
    {
        $this->templateService->toggleFavorite($template);

        return redirect()
            ->route('admin.barcode.templates.index');
    }
}
