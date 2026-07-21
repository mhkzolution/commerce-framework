<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Commerce\Catalog\Contracts\AttributeSetServiceInterface;
use Commerce\Catalog\DTO\CreateAttributeSetData;
use Commerce\Catalog\DTO\UpdateAttributeSetData;
use Commerce\Catalog\Http\Requests\StoreAttributeSetRequest;
use Commerce\Catalog\Http\Requests\UpdateAttributeSetRequest;
use Commerce\Catalog\Models\AttributeSet;
use Commerce\Catalog\Services\AttributeQueryService;
use Commerce\Catalog\Services\AttributeSetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AttributeSetController extends Controller
{
    public function __construct(
        private readonly AttributeSetService $attributeSetService,
        private readonly AttributeQueryService $attributeQueryService,
    ) {}

    public function index(): View
    {
        return view('catalog::admin.attribute-sets.index', [
            'attributeSets' => $this->attributeSetService->paginate(),
        ]);
    }

    public function create(): View
    {
        return view('catalog::admin.attribute-sets.create', [
            'attributes' => $this->attributeQueryService->all(),
        ]);
    }

    public function store(StoreAttributeSetRequest $request): RedirectResponse
    {
        $this->attributeSetService->create(new CreateAttributeSetData(
            code: $request->validated('code'),
            name: $request->validated('name'),
            attributeIds: array_map('intval', $request->validated('attribute_ids', [])),
        ));

        return redirect()->route('admin.catalog.attribute-sets.index')->with('status', 'Attribute set created.');
    }

    public function edit(string $attributeSet): View
    {
        $model = AttributeSet::query()->with('attributes')->where('uuid', $attributeSet)->firstOrFail();

        return view('catalog::admin.attribute-sets.edit', [
            'attributeSet' => $model,
            'attributes' => $this->attributeQueryService->all(),
        ]);
    }

    public function update(UpdateAttributeSetRequest $request, string $attributeSet): RedirectResponse
    {
        $this->attributeSetService->update($attributeSet, new UpdateAttributeSetData(
            code: $request->validated('code'),
            name: $request->validated('name'),
            attributeIds: array_map('intval', $request->validated('attribute_ids', [])),
        ));

        return redirect()->route('admin.catalog.attribute-sets.index')->with('status', 'Attribute set updated.');
    }

    public function destroy(string $attributeSet): RedirectResponse
    {
        $this->attributeSetService->delete($attributeSet);

        return redirect()->route('admin.catalog.attribute-sets.index')->with('status', 'Attribute set deleted.');
    }
}
