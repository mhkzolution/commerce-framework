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
            'optionValues' => $this->resolveFormOptions(null),
            'suggestedType' => null,
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
        $preset = $this->attributePreset($model->name);

        return view('catalog::admin.attributes.edit', [
            'attribute' => $model,
            'types' => config('catalog.attribute_types', []),
            'optionValues' => $this->resolveFormOptions($model),
            'suggestedType' => $preset['type'] ?? null,
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
    private function parseOptions(mixed $options): ?array
    {
        if ($options === null) {
            return null;
        }

        if (is_array($options)) {
            $parsed = array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $options,
            )));

            return $parsed === [] ? null : $parsed;
        }

        if (trim((string) $options) === '') {
            return null;
        }

        $parsed = array_values(array_filter(array_map('trim', explode("\n", (string) $options))));

        return $parsed === [] ? null : $parsed;
    }

    /**
     * @return list<string>
     */
    private function resolveFormOptions(?Attribute $attribute): array
    {
        $oldOptions = old('options');

        if (is_array($oldOptions)) {
            return array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $oldOptions,
            )));
        }

        if (is_string($oldOptions)) {
            return $this->parseOptions($oldOptions) ?? [];
        }

        $storedOptions = $attribute?->options ?? [];

        if ($storedOptions !== []) {
            return array_map('strval', $storedOptions);
        }

        $preset = $this->attributePreset($attribute?->name);

        return array_map('strval', $preset['options'] ?? []);
    }

    /**
     * @return array{type?: string, options?: list<string>}|null
     */
    private function attributePreset(?string $name): ?array
    {
        if ($name === null || $name === '') {
            return null;
        }

        $preset = config('product.attribute_option_presets')[$name] ?? null;

        return is_array($preset) ? $preset : null;
    }
}
