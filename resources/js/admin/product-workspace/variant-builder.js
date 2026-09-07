import { openMediaPicker } from '../media-picker';
import { applyVariantBuilderNotification } from './variant-builder-render.js';

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

export function bindVariantBuilder(root, state) {
    const optionsList = root.querySelector('[data-variant-options-list]');
    const valuesRoot = root.querySelector('[data-variant-values]');
    const presetSelect = root.querySelector('[data-variant-option-preset]');
    const addPresetBtn = root.querySelector('[data-variant-option-add-preset]');
    const customInput = root.querySelector('[data-variant-option-custom]');
    const addCustomBtn = root.querySelector('[data-variant-option-add-custom]');
    const matrixCount = root.querySelector('[data-variant-matrix-count]');
    const matrixFormula = root.querySelector('[data-variant-matrix-formula]');
    const skuPatternSelect = root.querySelector('[data-variant-sku-pattern]');
    const generateBtn = root.querySelector('[data-variant-generate]');
    const gridBody = root.querySelector('[data-variant-grid-body]');
    const rowTemplate = root.querySelector('#variant-grid-row-template')
        ?? document.getElementById('variant-grid-row-template');
    const cardsRoot = root.querySelector('[data-variant-cards]');
    const cardTemplate = root.querySelector('[data-variant-card]');
    const bulkToolbar = root.querySelector('[data-variant-bulk-toolbar]');
    const bulkCount = root.querySelector('[data-variant-bulk-count]');
    const bulkDialog = root.querySelector('[data-variant-bulk-dialog]');
    const bulkDialogTitle = root.querySelector('[data-bulk-dialog-title]');
    const bulkDialogBody = root.querySelector('[data-bulk-dialog-body]');
    const bulkDialogCancel = root.querySelector('[data-bulk-dialog-cancel]');
    const bulkDialogApply = root.querySelector('[data-bulk-dialog-apply]');
    const selectAll = root.querySelector('[data-variant-select-all]');
    const imageDialog = root.querySelector('[data-variant-image-dialog]');
    const imageGrid = root.querySelector('[data-variant-image-grid]');
    const imageSearch = root.querySelector('[data-variant-image-search]');
    const imageClose = root.querySelector('[data-variant-image-close]');
    const pickerUrl = root.dataset.mediaPickerUrl || '/admin/media/picker';

    let pendingBulkAction = null;
    let activeImageVariantId = null;
    let bulkImageMode = false;

    const updateMatrixSummary = () => {
        if (!matrixCount || !matrixFormula) {
            return;
        }

        const count = state.getState().variants.length;
        matrixCount.textContent = `${count} variant${count === 1 ? '' : 's'}`;

        const axes = typeof state.variationAxes === 'function' ? state.variationAxes() : [];
        if (!axes.length) {
            matrixFormula.textContent = 'Default variant only';
            return;
        }

        matrixFormula.textContent = axes
            .map((axis) => `${(axis.valueIds ?? []).length} ${axis.name ?? axis.attributeId}`)
            .join(' × ');
    };

    const renderOptions = () => {
        if (!optionsList || !valuesRoot) {
            return;
        }

        const { options } = state.getState();
        optionsList.innerHTML = '';

        options.forEach((option) => {
            const row = document.createElement('div');
            row.className = 'cf-variant-option';
            row.innerHTML = `
                <span class="cf-variant-option__name">${escapeHtml(option.name)}</span>
                <button type="button" class="cf-btn cf-btn--ghost cf-btn--sm" data-remove-option="${option.id}">Remove</button>
            `;
            optionsList.appendChild(row);
        });

        valuesRoot.innerHTML = '';
        options.forEach((option) => {
            const group = document.createElement('div');
            group.className = 'cf-variant-values__group';
            group.innerHTML = `
                <div class="cf-variant-values__label">${escapeHtml(option.name)}</div>
                <div class="cf-variant-values__chips" data-option-chips="${option.id}"></div>
                <div class="cf-variant-values__input-row">
                    <input type="text" class="cf-input" placeholder="Add ${escapeHtml(option.name)} value" data-option-value-input="${option.id}">
                    <button type="button" class="cf-btn cf-btn--secondary cf-btn--sm" data-option-value-add="${option.id}">Add</button>
                </div>
            `;
            valuesRoot.appendChild(group);

            const chips = group.querySelector(`[data-option-chips="${option.id}"]`);
            option.values.forEach((value) => {
                const chip = document.createElement('span');
                chip.className = 'cf-variant-chip';
                chip.innerHTML = `
                    ${escapeHtml(value)}
                    <button type="button" class="cf-variant-chip__remove" data-remove-value="${option.id}" data-value="${escapeHtml(value)}">&times;</button>
                `;
                chips.appendChild(chip);
            });
        });

        updateMatrixSummary();
    };

    const bindStockSummary = (container, variant) => {
        const available = container.querySelector('[data-variant-stock-available]');
        const reserved = container.querySelector('[data-variant-stock-reserved]');
        const onHandInput = container.querySelector('[data-variant-stock-on-hand-input]');
        const onHandWrap = container.querySelector('[data-variant-on-hand-wrap]');

        if (!available || !reserved) {
            return;
        }

        available.textContent = variant.stock.available;
        reserved.textContent = variant.stock.reserved;
        if (onHandInput) {
            onHandInput.value = variant.stock.onHand;
            onHandInput.addEventListener('input', () => {
                state.updateVariantStock(variant.id, 'onHand', onHandInput.value);
            });
        }
        onHandWrap?.toggleAttribute('hidden', !state.getState().product.trackInventory);
    };

    const bindVariantFields = (row, variant) => {
        if (!row) {
            return;
        }

        row.dataset.variantId = variant.id;

        row.querySelectorAll('[data-variant-field]').forEach((input) => {
            const field = input.dataset.variantField;
            input.value = variant[field] ?? '';

            input.addEventListener('input', () => {
                state.updateVariant(variant.id, field, input.value);
            });
        });

        const imagePreview = row.querySelector('[data-variant-image-preview]');
        const imageButton = row.querySelector('[data-variant-image]');
        if (imagePreview) {
            if (variant.imageMediaUuid && variant.imagePreviewUrl) {
                imagePreview.innerHTML = `<img src="${escapeHtml(variant.imagePreviewUrl)}" alt="" class="cf-variant-grid__image-thumb">`;
            } else {
                imagePreview.textContent = '+';
            }
        }

        imageButton?.addEventListener('click', async () => {
            const item = await openMediaPicker({
                url: pickerUrl,
                multiple: false,
                imagesOnly: true,
                title: 'Select image',
            });
            if (!item) {
                return;
            }
            state.updateVariant(variant.id, 'imageMediaUuid', item.uuid);
            state.updateVariant(variant.id, 'imagePreviewUrl', item.preview_url || item.url, { rebuild: true });
        });

        const checkbox = row.querySelector('[data-variant-select]');
        if (checkbox) {
            checkbox.checked = state.getState().selection.includes(variant.id);
            checkbox.addEventListener('change', () => {
                state.toggleSelection(variant.id, checkbox.checked);
            });
        }

        row.querySelector('[data-variant-delete]')?.addEventListener('click', () => {
            state.deleteVariant(variant.id);
        });

        const stockEl = row.querySelector('.cf-variant-stock-summary');
        if (stockEl) {
            bindStockSummary(stockEl, variant);
        }
    };

    const renderGrid = () => {
        if (!gridBody) {
            return;
        }

        if (!rowTemplate?.content) {
            return;
        }

        const { variants } = state.getState();
        gridBody.innerHTML = '';

        variants.forEach((variant) => {
            const clone = rowTemplate.content.cloneNode(true);
            const row = clone.querySelector('tr');
            bindVariantFields(row, variant);
            gridBody.appendChild(clone);
        });

        renderCards();
        renderBulkToolbar();
    };

    const loadImagePicker = async () => {
        if (!imageGrid) {
            return;
        }

        const params = new URLSearchParams({
            images_only: '1',
            search: imageSearch?.value || '',
        });

        const response = await fetch(`${pickerUrl}?${params}`, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();
        imageGrid.innerHTML = '';

        (payload.data ?? []).forEach((item) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'cf-variant-image-dialog__item';
            button.innerHTML = `
                <img src="${escapeHtml(item.url || item.preview_url)}" alt="${escapeHtml(item.filename)}" class="cf-variant-image-dialog__thumb">
            `;
            button.addEventListener('click', () => {
                if (bulkImageMode) {
                    state.applyBulkImage(item.uuid, item.url || item.preview_url);
                    bulkImageMode = false;
                    imageDialog?.close();
                    return;
                }

                if (!activeImageVariantId) {
                    return;
                }

                state.updateVariant(activeImageVariantId, 'imageMediaUuid', item.uuid);
                state.updateVariant(activeImageVariantId, 'imagePreviewUrl', item.url || item.preview_url, { rebuild: true });
                imageDialog?.close();
                activeImageVariantId = null;
            });
            imageGrid.appendChild(button);
        });
    };

    imageSearch?.addEventListener('input', () => {
        loadImagePicker();
    });

    imageClose?.addEventListener('click', () => imageDialog?.close());
    imageDialog?.addEventListener('close', () => {
        activeImageVariantId = null;
        bulkImageMode = false;
    });

    const renderCards = () => {
        if (!cardsRoot) {
            return;
        }

        const { variants } = state.getState();
        cardsRoot.innerHTML = '';

        variants.forEach((variant) => {
            const card = document.createElement('article');
            card.className = 'cf-variant-card';
            card.dataset.variantId = variant.id;
            card.innerHTML = `
                <header class="cf-variant-card__header">
                    <input type="checkbox" data-variant-select aria-label="Select variant">
                    <h4 class="cf-variant-card__title">${escapeHtml(variant.name)}</h4>
                    <button type="button" class="cf-variant-card__toggle" data-variant-card-toggle aria-expanded="false">
                        <span class="sr-only">Toggle details</span>
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                    </button>
                </header>
                <div class="cf-variant-card__summary"></div>
                <div class="cf-variant-card__body hidden" data-variant-card-body>
                    <div class="cf-variant-card__fields"></div>
                    <button type="button" class="cf-btn cf-btn--danger cf-btn--sm cf-variant-card__delete" data-variant-delete>Delete variant</button>
                </div>
            `;

            const summarySlot = card.querySelector('.cf-variant-card__summary');
            summarySlot.innerHTML = `
                <div class="cf-variant-stock-summary">
                    <label data-variant-on-hand-wrap>
                        <span>On hand</span>
                        <input type="number" min="0" step="1" class="cf-input" data-variant-stock-on-hand-input>
                    </label>
                    <span class="cf-variant-stock-summary__meta">
                        <span><strong data-variant-stock-reserved>${variant.stock.reserved}</strong> reserved</span> ·
                        <span><strong data-variant-stock-available>${variant.stock.available}</strong> available</span>
                    </span>
                </div>
            `;

            const fields = card.querySelector('.cf-variant-card__fields');
            ['sku', 'price', 'cost', 'comparePrice', 'weight'].forEach((field) => {
                const label = document.createElement('label');
                label.className = 'cf-variant-card__field';
                label.innerHTML = `<span>${field}</span><input type="${field.includes('price') || field === 'cost' || field === 'weight' ? 'number' : 'text'}" class="cf-input" data-variant-field="${field}">`;
                fields.appendChild(label);
            });

            const statusField = document.createElement('label');
            statusField.className = 'cf-variant-card__field';
            statusField.innerHTML = `
                <span>Status</span>
                <select class="cf-input" data-variant-field="status">
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>
            `;
            fields.appendChild(statusField);

            bindVariantFields(card, variant);

            card.querySelector('[data-variant-card-toggle]')?.addEventListener('click', () => {
                const body = card.querySelector('[data-variant-card-body]');
                const expanded = body.classList.toggle('hidden') === false;
                card.querySelector('[data-variant-card-toggle]').setAttribute('aria-expanded', String(expanded));
            });

            cardsRoot.appendChild(card);
        });
    };

    const renderBulkToolbar = () => {
        const { selection } = state.getState();
        const visible = selection.length > 0;
        bulkToolbar?.classList.toggle('hidden', !visible);
        if (bulkCount) {
            bulkCount.textContent = String(selection.length);
        }
        if (selectAll) {
            selectAll.checked = selection.length > 0 && selection.length === state.getState().variants.length;
        }
    };

    addPresetBtn?.addEventListener('click', () => {
        const name = presetSelect.value;
        if (!name) {
            return;
        }
        state.addOption(name, state.getState().optionPresets?.[name] ?? []);
        presetSelect.value = '';
    });

    addCustomBtn?.addEventListener('click', () => {
        state.addOption(customInput.value);
        customInput.value = '';
    });

    customInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            state.addOption(customInput.value);
            customInput.value = '';
        }
    });

    optionsList?.addEventListener('click', (event) => {
        const removeId = event.target.dataset.removeOption;
        if (removeId) {
            state.removeOption(removeId);
        }
    });

    valuesRoot?.addEventListener('click', (event) => {
        const optionId = event.target.dataset.optionValueAdd;
        if (optionId) {
            const input = valuesRoot.querySelector(`[data-option-value-input="${optionId}"]`);
            state.addOptionValue(optionId, input?.value ?? '');
            if (input) {
                input.value = '';
            }
        }

        const removeValueId = event.target.dataset.removeValue;
        if (removeValueId) {
            state.removeOptionValue(removeValueId, event.target.dataset.value);
        }
    });

    valuesRoot?.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        const input = event.target;
        const optionId = input.dataset?.optionValueInput;
        if (optionId) {
            event.preventDefault();
            state.addOptionValue(optionId, input.value);
            input.value = '';
        }
    });

    skuPatternSelect?.addEventListener('change', () => {
        state.setSkuPattern(skuPatternSelect.value);
    });

    generateBtn?.addEventListener('click', () => {
        if (typeof state.generateFromAttributes === 'function') {
            state.generateFromAttributes();
            return;
        }

        state.generateMatrix();
    });

    selectAll?.addEventListener('change', () => {
        state.selectAll(selectAll.checked);
    });

    const openBulkDialog = (action, title, bodyHtml, onApply) => {
        if (!bulkDialog || !bulkDialogTitle || !bulkDialogBody) {
            return;
        }

        pendingBulkAction = onApply;
        bulkDialogTitle.textContent = title;
        bulkDialogBody.innerHTML = bodyHtml;
        bulkDialog.showModal();
    };

    bulkDialogCancel?.addEventListener('click', () => bulkDialog.close());
    bulkDialog?.addEventListener('close', () => {
        pendingBulkAction = null;
    });

    bulkDialogApply?.addEventListener('click', (event) => {
        event.preventDefault();
        pendingBulkAction?.();
        bulkDialog.close();
    });

    root.querySelector('[data-variant-bulk-toolbar]')?.addEventListener('click', (event) => {
        const action = event.target.dataset?.bulkAction;
        if (!action) {
            return;
        }

        if (action === 'delete') {
            state.bulkDelete();
            return;
        }

        if (action === 'image') {
            openMediaPicker({
                url: pickerUrl,
                multiple: false,
                imagesOnly: true,
                title: 'Select image',
            }).then((item) => {
                if (item) {
                    state.applyBulkImage(item.uuid, item.preview_url || item.url);
                }
            });
            return;
        }

        if (action === 'sku') {
            state.applyBulk(action, null);
            return;
        }

        if (action === 'status') {
            openBulkDialog(
                action,
                'Set status',
                `<select class="cf-input" data-bulk-value>
                    <option value="active">Active</option>
                    <option value="draft">Draft</option>
                    <option value="archived">Archived</option>
                </select>`,
                () => {
                    const value = bulkDialogBody.querySelector('[data-bulk-value]').value;
                    state.applyBulk('status', value);
                },
            );
            return;
        }

        const labels = { price: 'Set price', cost: 'Set cost', weight: 'Set weight (g)' };
        openBulkDialog(
            action,
            labels[action] ?? 'Bulk action',
            `<input type="number" class="cf-input" data-bulk-value step="0.01" min="0">`,
            () => {
                const value = bulkDialogBody.querySelector('[data-bulk-value]').value;
                state.applyBulk(action, value);
            },
        );
    });

    const hydrateExistingRows = () => {
        if (!gridBody) {
            return;
        }

        const variantById = new Map(
            state.getState().variants.map((variant) => [String(variant.id), variant]),
        );

        gridBody.querySelectorAll('[data-variant-row]').forEach((row) => {
            const variant = variantById.get(row.dataset.variantId ?? '');
            if (variant) {
                bindVariantFields(row, variant);
            }
        });
    };

    let lastEpoch = state.getState().uiEpoch ?? 0;

    const syncTrackVisibility = () => {
        const tracked = Boolean(state.getState().product.trackInventory);
        root.querySelectorAll('[data-variant-on-hand-wrap]').forEach((wrap) => {
            wrap.toggleAttribute('hidden', !tracked);
        });
    };

    state.subscribe(() => {
        const { uiEpoch } = state.getState();
        const result = applyVariantBuilderNotification(uiEpoch, lastEpoch, {
            renderGrid,
            renderOptions,
        });
        lastEpoch = result.lastEpoch;
        syncTrackVisibility();
        renderBulkToolbar();
    });

    renderOptions();
    const existingRowCount = gridBody?.querySelectorAll('[data-variant-row]').length ?? 0;
    renderGrid();

    if (existingRowCount > 0 && !rowTemplate?.content) {
        hydrateExistingRows();
    }
}
