<?php

declare(strict_types=1);

namespace Commerce\Product\Http\Controllers\Admin;

use Commerce\Catalog\Models\Attribute;
use Commerce\Product\Http\Requests\StoreVariantOptionPresetRequest;
use Commerce\Product\Http\Requests\UpdateVariantOptionPresetRequest;
use Commerce\Product\Services\VariantOptionPresetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class VariantOptionPresetController extends Controller
{
    public function __construct(
        private readonly VariantOptionPresetService $presetService,
    ) {}

    public function index(): View
    {
        return view('product::admin.variant-options.index', [
            'options' => $this->presetService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('product::admin.variant-options.create', [
            'suggestedCode' => $this->presetService->suggestCode('option'),
        ]);
    }

    public function store(StoreVariantOptionPresetRequest $request): RedirectResponse
    {
        $this->presetService->create(
            name: $request->validated('name'),
            code: $request->validated('code'),
            options: array_values($request->validated('options')),
            position: (int) $request->validated('position', 0),
        );

        return redirect()
            ->route('admin.catalog.variant-options.index')
            ->with('status', __('product::workspace.variant_options_created'));
    }

    public function edit(string $variant_option): View
    {
        $model = Attribute::query()->where('uuid', $variant_option)->firstOrFail();

        abort_unless($this->presetService->belongsToPresetSet($model), 404);

        return view('product::admin.variant-options.edit', [
            'option' => $model,
        ]);
    }

    public function update(UpdateVariantOptionPresetRequest $request, string $variant_option): RedirectResponse
    {
        $model = Attribute::query()->where('uuid', $variant_option)->firstOrFail();

        abort_unless($this->presetService->belongsToPresetSet($model), 404);

        $this->presetService->update(
            attribute: $model,
            name: $request->validated('name'),
            code: $request->validated('code'),
            options: array_values($request->validated('options')),
            position: (int) $request->validated('position', 0),
        );

        return redirect()
            ->route('admin.catalog.variant-options.index')
            ->with('status', __('product::workspace.variant_options_updated'));
    }

    public function destroy(string $variant_option): RedirectResponse
    {
        $model = Attribute::query()->where('uuid', $variant_option)->firstOrFail();

        abort_unless($this->presetService->belongsToPresetSet($model), 404);

        $this->presetService->delete($model);

        return redirect()
            ->route('admin.catalog.variant-options.index')
            ->with('status', __('product::workspace.variant_options_deleted'));
    }
}
