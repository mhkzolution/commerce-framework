import '../../css/admin/product-workspace.css';

import { ProductWorkspaceState } from './product-workspace/state.js';
import { bindVariantBuilder } from './product-workspace/variant-builder.js';

function initTabs(workspace) {
    const tabButtons = workspace.querySelectorAll('[data-workspace-tab]');
    const panels = workspace.querySelectorAll('[data-workspace-panel]');

    const activate = (key) => {
        tabButtons.forEach((button) => {
            const active = button.dataset.workspaceTab === key;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', String(active));
        });

        panels.forEach((panel) => {
            const active = panel.dataset.workspacePanel === key;
            panel.hidden = !active;
            panel.classList.toggle('hidden', !active);
        });

        if (location.hash !== `#${key}`) {
            history.replaceState(null, '', `#${key}`);
        }
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activate(button.dataset.workspaceTab));
    });

    const hash = location.hash.replace('#', '');
    if (hash && workspace.querySelector(`[data-workspace-panel="${hash}"]`)) {
        activate(hash);
    }
}

function bindProductField(form, state, fieldName, stateKey) {
    const field = form?.querySelector(`[name="${fieldName}"]`);
    if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
        return;
    }

    field.addEventListener('change', () => {
        state.setProductField(stateKey, field.value);
    });
}

function syncFormFieldsToState(form, state) {
    if (!form) {
        return;
    }

    const mappings = [
        ['brand_uuid', 'brandUuid'],
        ['seller_uuid', 'sellerUuid'],
        ['attribute_set_id', 'attributeSetId'],
        ['status', 'status'],
        ['visibility', 'visibility'],
        ['publish_at', 'publishAt'],
        ['description', 'description'],
    ];

    mappings.forEach(([fieldName, stateKey]) => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
            state.data.product[stateKey] = field.value;
        }
    });

    const categoryIds = [...form.querySelectorAll('[name="category_ids[]"]:checked')].map((el) => Number(el.value));
    const collectionIds = [...form.querySelectorAll('[name="collection_ids[]"]:checked')].map((el) => Number(el.value));

    state.data.product.categoryIds = categoryIds;
    state.data.product.collectionIds = collectionIds;
}

function initDirtyState(workspace, state) {
    const form = workspace.querySelector('[data-product-workspace-form]');
    const dirtyLabel = workspace.querySelector('[data-workspace-dirty-label]');
    const dirtyIndicator = workspace.querySelector('[data-workspace-dirty-indicator]');
    const discardBtn = workspace.querySelector('[data-workspace-discard]');
    const payloadInput = workspace.querySelector('[data-workspace-payload]');
    const nameInput = workspace.querySelector('[data-workspace-product-name]');
    const slugInput = workspace.querySelector('[data-workspace-slug-input]');
    const slugPreview = workspace.querySelector('[data-workspace-slug-preview]');

    const syncDirtyUi = (data) => {
        const dirty = data.dirty;
        const labels = data.labels ?? {};
        if (dirtyLabel) {
            dirtyLabel.textContent = dirty
                ? (labels.unsavedChanges ?? 'Unsaved changes')
                : (labels.allChangesSaved ?? 'All changes saved');
        }
        if (dirtyIndicator) {
            dirtyIndicator.hidden = !dirty;
        }
        if (discardBtn) {
            discardBtn.hidden = !dirty;
        }
    };

    state.subscribe(syncDirtyUi);

    form?.addEventListener('input', () => state.markDirty());
    form?.addEventListener('change', () => state.markDirty());

    nameInput?.addEventListener('input', () => {
        state.setProductField('name', nameInput.value);
        if (slugInput && !slugInput.dataset.manual) {
            slugInput.value = state.getState().product.slug;
        }
        if (slugPreview) {
            slugPreview.textContent = state.getState().product.slug;
        }
    });

    slugInput?.addEventListener('input', () => {
        slugInput.dataset.manual = 'true';
        state.setProductField('slug', slugInput.value);
        if (slugPreview) {
            slugPreview.textContent = slugInput.value;
        }
    });

    bindProductField(form, state, 'brand_uuid', 'brandUuid');
    bindProductField(form, state, 'seller_uuid', 'sellerUuid');
    bindProductField(form, state, 'attribute_set_id', 'attributeSetId');
    bindProductField(form, state, 'status', 'status');
    bindProductField(form, state, 'visibility', 'visibility');
    bindProductField(form, state, 'publish_at', 'publishAt');
    bindProductField(form, state, 'description', 'description');

    discardBtn?.addEventListener('click', () => {
        const labels = state.getState().labels ?? {};
        if (confirm(labels.discardConfirm ?? 'Discard unsaved changes?')) {
            window.location.reload();
        }
    });

    form?.addEventListener('submit', () => {
        syncFormFieldsToState(form, state);

        const mediaInputs = form.querySelectorAll('[name="media_uuids[]"]');
        if (mediaInputs.length > 0) {
            state.setMediaUuids([...mediaInputs].map((input) => input.value));
        }

        if (payloadInput) {
            payloadInput.value = state.serialize();
        }
        state.setDirty(false);
    });

    workspace.querySelector('[data-product-media-manager]')?.addEventListener('images-reordered', () => {
        const mediaInputs = form?.querySelectorAll('[name="media_uuids[]"]') ?? [];
        state.setMediaUuids([...mediaInputs].map((input) => input.value));
        state.markDirty();
    });
}

function parseInitialState(workspace) {
    const script = workspace.querySelector('[data-product-workspace-state]');
    if (!script) {
        return {};
    }

    try {
        return JSON.parse(script.textContent || '{}');
    } catch {
        return {};
    }
}

export function initProductWorkspaces() {
    document.querySelectorAll('[data-product-workspace]').forEach((workspace) => {
        try {
            const initial = parseInitialState(workspace);
            const state = new ProductWorkspaceState(initial);

            initTabs(workspace);
            initDirtyState(workspace, state);

            const builder = workspace.querySelector('[data-variant-builder]');
            if (builder) {
                bindVariantBuilder(builder, state);
            }

            initMediaFilters(workspace);
        } catch (error) {
            console.error('Product workspace failed to initialize', error);
        }
    });
}

function bootProductWorkspaces() {
    if (!document.querySelector('[data-product-workspace]')) {
        return;
    }

    initProductWorkspaces();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootProductWorkspaces);
} else {
    bootProductWorkspaces();
}

function initMediaFilters(workspace) {
    const manager = workspace.querySelector('[data-product-media-manager]');
    if (!manager) {
        return;
    }

    const filters = manager.querySelectorAll('[data-media-filter]');
    const attachRoot = manager.querySelector('[data-images-attach]');
    const list = attachRoot?.querySelector('[data-images-list]');

    if (!filters.length || !list) {
        return;
    }

    const applyFilter = (filter) => {
        filters.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.mediaFilter === filter);
        });

        list.querySelectorAll('[data-image-item]').forEach((item) => {
            const type = item.dataset.mediaType || 'image';
            const visible = filter === 'all'
                || (filter === 'image' && type === 'image')
                || (filter === 'video' && type === 'video')
                || (filter === 'document' && !['image', 'video'].includes(type));

            item.classList.toggle('hidden', !visible);
        });
    };

    filters.forEach((button) => {
        button.addEventListener('click', () => applyFilter(button.dataset.mediaFilter || 'all'));
    });
}
