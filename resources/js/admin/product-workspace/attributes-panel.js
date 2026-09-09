function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function parseCatalog(root) {
    const script = root.querySelector('[data-attribute-sets-catalog]');
    if (!script) {
        return [];
    }

    try {
        const parsed = JSON.parse(script.textContent || '[]');
        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function bindAttributesPanel(root, state) {
    const setSelect = root.querySelector('[data-attribute-set-select]');
    const fieldsRoot = root.querySelector('[data-attribute-fields]');
    const emptyState = root.querySelector('[data-attribute-empty]');
    const generateButtons = document.querySelectorAll('[data-generate-variants]');
    const generateWrap = root.querySelector('[data-generate-variants-wrap]');
    const variationLabel = root.querySelector('[data-used-for-variations] span')?.textContent
        || 'Used for Variations';
    const addValueLabel = root.dataset.addValueLabel || 'Add';
    const catalog = parseCatalog(root);

    state.data.attributeSets = catalog;

    const currentSetId = () => setSelect?.value ?? state.getState().product.attributeSetId ?? '';

    const ensureRowsForSet = () => {
        const setId = currentSetId();
        const rows = state.getState().productAttributes ?? [];
        if (rows.length > 0 || !setId) {
            return;
        }

        state.syncProductAttributesFromSet(setId, catalog, { dirty: false });
    };

    const syncGenerateControls = () => {
        const variable = state.getState().product.type === 'variable';
        const enabled = state.canGenerateVariants();

        generateWrap?.toggleAttribute('hidden', !variable);
        generateButtons.forEach((button) => {
            button.disabled = !enabled;
            if (button.closest('[data-attributes-panel]')) {
                return;
            }
            button.toggleAttribute('hidden', !variable);
        });
    };

    const renderFields = () => {
        if (!fieldsRoot) {
            return;
        }

        const setId = currentSetId();
        const selected = catalog.find((set) => String(set.id) === String(setId));
        const variable = state.getState().product.type === 'variable';
        const rows = state.getState().productAttributes ?? [];

        fieldsRoot.innerHTML = '';

        if (!selected || !(selected.attributes ?? []).length) {
            if (emptyState) {
                emptyState.hidden = false;
                emptyState.textContent = setId
                    ? emptyState.dataset.hasAttributesEmpty || emptyState.textContent
                    : emptyState.dataset.setEmpty || emptyState.textContent;
            }
            syncGenerateControls();
            return;
        }

        if (emptyState) {
            emptyState.hidden = true;
        }

        selected.attributes.forEach((attribute) => {
            const row = rows.find((item) => Number(item.attributeId) === Number(attribute.id)) ?? {
                attributeId: Number(attribute.id),
                usedForVariations: false,
                valueIds: [],
                newLabels: [],
            };
            const isSelect = attribute.type === 'select';
            const usedForVariations = variable && isSelect && Boolean(row.usedForVariations);
            const values = attribute.values ?? [];
            const selectedIds = new Set((row.valueIds ?? []).map(Number));
            const wrapper = document.createElement('div');
            wrapper.className = values.length > 6 || usedForVariations ? 'md:col-span-2' : '';

            const options = values.map((value) => {
                const inputType = 'checkbox';
                const checked = selectedIds.has(Number(value.id)) ? 'checked' : '';

                return `
                    <label class="cf-attributes-panel__value">
                        <input
                            type="${inputType}"
                            name="workspace-attribute-${attribute.id}"
                            value="${escapeHtml(value.id)}"
                            data-attribute-value
                            data-attribute-id="${escapeHtml(attribute.id)}"
                            ${checked}
                        >
                        <span>${escapeHtml(value.label)}</span>
                    </label>
                `;
            }).join('');

            const variationToggle = isSelect
                ? `
                    <label class="cf-attributes-panel__variation" data-used-for-variations ${variable ? '' : 'hidden'}>
                        <input type="checkbox" data-used-for-variations-input data-attribute-id="${escapeHtml(attribute.id)}" ${usedForVariations ? 'checked' : ''}>
                        <span>${escapeHtml(variationLabel)}</span>
                    </label>
                `
                : '';

            const pending = (row.newLabels ?? []).map((label) => (
                `<span class="cf-variant-chip">${escapeHtml(label)}</span>`
            )).join('');

            wrapper.innerHTML = `
                <div class="cf-attributes-panel__field" data-attribute-row="${escapeHtml(attribute.id)}">
                    <div class="cf-attributes-panel__field-header">
                        <label class="cf-product-workspace__label">${escapeHtml(attribute.name)}</label>
                        ${variationToggle}
                    </div>
                    <div class="cf-attributes-panel__values">${options || `<p class="text-sm text-muted">${escapeHtml(attribute.type)}</p>`}</div>
                    ${pending ? `<div class="cf-attributes-panel__pending">${pending}</div>` : ''}
                    ${isSelect ? `
                        <div class="cf-attributes-panel__add">
                            <input type="text" class="cf-input" data-attribute-new-label data-attribute-id="${escapeHtml(attribute.id)}" placeholder="${escapeHtml(attribute.name)}">
                            <button type="button" class="cf-btn cf-btn--secondary cf-btn--sm" data-attribute-add-value data-attribute-id="${escapeHtml(attribute.id)}">${escapeHtml(addValueLabel)}</button>
                        </div>
                    ` : ''}
                </div>
            `;

            fieldsRoot.appendChild(wrapper);
        });

        syncGenerateControls();
    };

    setSelect?.addEventListener('change', () => {
        state.syncProductAttributesFromSet(setSelect.value, catalog);
        renderFields();
    });

    fieldsRoot?.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof HTMLInputElement)) {
            return;
        }

        if (target.matches('[data-used-for-variations-input]')) {
            const accepted = state.setUsedForVariations(target.dataset.attributeId, target.checked);
            if (!accepted) {
                target.checked = true;
            }
            renderFields();
            return;
        }

        if (target.matches('[data-attribute-value]')) {
            const attributeId = target.dataset.attributeId;
            const group = fieldsRoot.querySelectorAll(`[data-attribute-value][data-attribute-id="${attributeId}"]`);
            const valueIds = [...group].filter((input) => input.checked).map((input) => Number(input.value));
            state.setAttributeValues(attributeId, valueIds);
            syncGenerateControls();
        }
    });

    fieldsRoot?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-attribute-add-value]');
        if (!button) {
            return;
        }

        const attributeId = button.dataset.attributeId;
        const input = fieldsRoot.querySelector(`[data-attribute-new-label][data-attribute-id="${attributeId}"]`);
        state.addAttributeLabel(attributeId, input?.value ?? '');
        if (input) {
            input.value = '';
        }
        renderFields();
    });

    generateButtons.forEach((button) => {
        button.addEventListener('click', () => {
            state.generateFromAttributes();
            syncGenerateControls();
        });
    });

    ensureRowsForSet();
    renderFields();

    let lastType = state.getState().product.type;
    state.subscribe(() => {
        const nextType = state.getState().product.type;
        if (nextType !== lastType) {
            lastType = nextType;
            renderFields();
            return;
        }

        syncGenerateControls();
    });
}
