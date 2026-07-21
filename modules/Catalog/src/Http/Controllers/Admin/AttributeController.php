<?php

declare(strict_types=1);

namespace Commerce\Catalog\Http\Controllers\Admin;

use Commerce\Catalog\Contracts\AttributeServiceInterface;
use Commerce\Catalog\DTO\CreateAttributeData;
use Commerce\Catalog\DTO\UpdateAttributeData;
use Commerce\Catalog\Http\Requests\StoreAttributeRequest;
use Commerce\Catalog\Http\Requests\UpdateAttributeRequest;
use Commerce\Catalog\Models\Attribute;
use Commerce\Catalog\Services\AttributeQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class AttributeController extends Controller
{
    public function __construct(
        private readonly AttributeQueryService $queryService,
        private readonly AttributeServiceInterface $attributeService,
    ) {}

    public function index(): View
    {
        return view('catalog::admin.attributes.index', [
            'attributes' => $this->queryService->paginate(),
            'types' => config('catalog.attribute_types', []),
        ]);
    }

    public function create(): View
    {
        return view('catalog::admin.attributes.create', [
            'types' => config('catalog.attribute_types', []),
        ]);
    }

    public function store(StoreAttributeRequest $request): RedirectResponse
    {
        $this->attributeService->create(new CreateAttributeData(
            code: $request->validated('code'),
            name: $request->validated('name'),
            type: $request->validated('type'),
            isFilterable: (bool) $request->validated('is_filterable', false),
            isRequired: (bool) $request->validated('is_required', false),
            isVisible: (bool) $request->validated('is_visible', true),
            position: (int) $request->validated('position', 0),
            options: $this->parseOptions($request->validated('options')),
        ));

        return redirect()->route('admin.catalog.attributes.index')->with('status', 'Attribute created.');
    }

    public function edit(string $attribute): View
    {
        $model = Attribute::query()->where('uuid', $attribute)->firstOrFail();

        return view('catalog::admin.attributes.edit', [
            'attribute' => $model,
            'types' => config('catalog.attribute_types', []),
        ]);
    }

    public function update(UpdateAttributeRequest $request, string $attribute): RedirectResponse
    {
        $this->attributeService->update($attribute, new UpdateAttributeData(
            code: $request->validated('code'),
            name: $request->validated('name'),
            type: $request->validated('type'),
            isFilterable: (bool) $request->validated('is_filterable', false),
            isRequired: (bool) $request->validated('is_required', false),
            isVisible: (bool) $request->validated('is_visible', true),
            position: (int) $request->validated('position', 0),
            options: $this->parseOptions($request->validated('options')),
        ));

        return redirect()->route('admin.catalog.attributes.index')->with('status', 'Attribute updated.');
    }

    public function destroy(string $attribute): RedirectResponse
    {
        $this->attributeService->delete($attribute);

        return redirect()->route('admin.catalog.attributes.index')->with('status', 'Attribute deleted.');
    }

    /**
     * @return list<string>|null
     */
    private function parseOptions(?string $options): ?array
    {
        if ($options === null || trim($options) === '') {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode("\n", $options))));
    }
}
