@php
    $selectedAttributeSetId = (string) old(
        'attribute_set_id',
        $product?->attribute_set_id ?? $defaultAttributeSetId ?? '',
    );
    $attributeValueMap = [];

    foreach ($attributeValues ?? [] as $attributeId => $value) {
        $attributeValueMap[$attributeId] = old("attributes.{$attributeId}", $value?->value);
    }

    foreach (old('attributes', []) as $attributeId => $value) {
        $attributeValueMap[$attributeId] = $value;
    }
@endphp

<section class="rounded-lg border border-border bg-primary-subtle/40 p-4" data-product-attributes>
    <h3 class="text-sm font-medium text-text">Attributes</h3>

    <div class="mt-4">
        <label class="block text-sm font-medium text-text" for="attribute_set_id">Attribute set</label>
        <select id="attribute_set_id" name="attribute_set_id" class="cf-input mt-1" data-attribute-set-select>
            <option value="">— None —</option>
            @foreach ($attributeSets as $set)
                <option value="{{ $set->id }}" @selected($selectedAttributeSetId === (string) $set->id)>{{ $set->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-muted">Choose a set, then pick attribute values below.</p>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2" data-attribute-fields></div>
    <p class="mt-4 hidden text-sm text-muted" data-attribute-empty>Select an attribute set to configure product attributes.</p>
</section>

@include('product::components.searchable-filter')

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const root = document.querySelector('[data-product-attributes]');
            if (!root) {
                return;
            }

            const setSelect = root.querySelector('[data-attribute-set-select]');
            const fieldsRoot = root.querySelector('[data-attribute-fields]');
            const emptyState = root.querySelector('[data-attribute-empty]');
            const attributeSets = @json($attributeSetsPayload ?? []);
            const attributeOptionPresets = @json($attributeOptionPresets ?? []);
            const initialValues = @json($attributeValueMap);

            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');

            const parseMultiValue = (rawValue) => {
                if (Array.isArray(rawValue)) {
                    return rawValue;
                }

                if (typeof rawValue === 'string' && rawValue !== '') {
                    if (rawValue.startsWith('[')) {
                        try {
                            return JSON.parse(rawValue);
                        } catch (error) {
                            return [];
                        }
                    }

                    return rawValue.split(',').map((part) => part.trim()).filter(Boolean);
                }

                return [];
            };

            const resolvePreset = (attribute) => attributeOptionPresets[attribute.name] ?? null;

            const resolveOptions = (attribute) => {
                const dbOptions = (attribute.options ?? []).map(String);

                if (dbOptions.length > 0) {
                    return dbOptions;
                }

                const preset = resolvePreset(attribute);

                return (preset?.options ?? []).map(String);
            };

            const resolveType = (attribute) => {
                const preset = resolvePreset(attribute);

                if (preset?.type) {
                    return preset.type;
                }

                if (resolveOptions(attribute).length > 0) {
                    return attribute.type === 'multiselect' ? 'multiselect' : 'select';
                }

                return attribute.type;
            };

            const renderSearchableOptions = (attribute, type, options, rawValue) => {
                const fieldId = `attribute-${attribute.id}`;
                const inputType = type === 'multiselect' ? 'checkbox' : 'radio';
                const name = type === 'multiselect'
                    ? `attributes[${attribute.id}][]`
                    : `attributes[${attribute.id}]`;
                const selectedValues = type === 'multiselect'
                    ? parseMultiValue(rawValue)
                    : [String(rawValue ?? '')];

                const items = options.map((option) => {
                    const checked = type === 'multiselect'
                        ? selectedValues.includes(String(option))
                        : String(rawValue ?? '') === String(option);

                    return `
                        <label class="flex items-center gap-2 text-sm text-text" data-searchable-item data-searchable-text="${escapeHtml(String(option).toLowerCase())}">
                            <input
                                type="${inputType}"
                                id="${fieldId}-${escapeHtml(option)}"
                                name="${name}"
                                value="${escapeHtml(option)}"
                                ${checked ? 'checked' : ''}
                                class="rounded border-border"
                            >
                            <span>${escapeHtml(option)}</span>
                        </label>
                    `;
                }).join('');

                return `
                    <div data-searchable-list>
                        <input
                            type="search"
                            class="cf-input"
                            placeholder="ค้นหา${attribute.name}..."
                            data-searchable-filter
                            autocomplete="off"
                        >
                        <div class="mt-2 space-y-2">${items}</div>
                    </div>
                `;
            };

            const renderField = (attribute, rawValue) => {
                const fieldId = `attribute-${attribute.id}`;
                const required = attribute.is_required ? '<span class="text-danger">*</span>' : '';
                const type = resolveType(attribute);
                const options = resolveOptions(attribute);
                const wide = ['textarea', 'multiselect'].includes(type) || options.length > 6 ? 'md:col-span-2' : '';
                let control = '';

                if (type === 'boolean') {
                    const checked = ['1', 'true', true, 1].includes(rawValue) ? 'checked' : '';
                    control = `
                        <input type="hidden" name="attributes[${attribute.id}]" value="0">
                        <input id="${fieldId}" type="checkbox" name="attributes[${attribute.id}]" value="1" ${checked} class="mt-2 rounded border-border">
                    `;
                } else if (options.length > 0 && (type === 'select' || type === 'multiselect')) {
                    control = renderSearchableOptions(attribute, type, options, rawValue);
                } else if (type === 'textarea') {
                    control = `<textarea id="${fieldId}" name="attributes[${attribute.id}]" rows="3" class="cf-input mt-1">${escapeHtml(rawValue ?? '')}</textarea>`;
                } else if (type === 'number') {
                    control = `<input id="${fieldId}" type="number" name="attributes[${attribute.id}]" value="${escapeHtml(rawValue ?? '')}" class="cf-input mt-1">`;
                } else {
                    control = `<input id="${fieldId}" type="text" name="attributes[${attribute.id}]" value="${escapeHtml(rawValue ?? '')}" class="cf-input mt-1">`;
                }

                const wrapper = document.createElement('div');
                wrapper.className = wide;
                wrapper.innerHTML = `
                    <label class="block text-sm font-medium text-text" for="${fieldId}">
                        ${escapeHtml(attribute.name)} ${required}
                    </label>
                    <div class="mt-1">${control}</div>
                `;

                return wrapper;
            };

            const renderFields = () => {
                const setId = setSelect.value;
                const selectedSet = attributeSets.find((set) => String(set.id) === String(setId));
                fieldsRoot.innerHTML = '';

                if (!selectedSet || !selectedSet.attributes.length) {
                    emptyState.classList.toggle('hidden', Boolean(setId));
                    emptyState.textContent = setId
                        ? 'This attribute set has no attributes yet.'
                        : 'Select an attribute set to configure product attributes.';
                    return;
                }

                emptyState.classList.add('hidden');

                selectedSet.attributes.forEach((attribute) => {
                    let rawValue = initialValues[attribute.id] ?? initialValues[String(attribute.id)] ?? '';
                    fieldsRoot.appendChild(renderField(attribute, rawValue));
                });

                window.initSearchableLists?.(fieldsRoot);
            };

            setSelect.addEventListener('change', renderFields);
            renderFields();
        });
    </script>
@endpush
