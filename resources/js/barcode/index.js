import { BarcodeQueue } from './queue.js';
import { applyZoom, renderPreviewPage } from './preview.js';
import { initScanner, initSearch, searchProducts } from './search.js';
import { labelStyleFromTemplate } from './label-style.js';
import { initManualBarcode, initSearchModeToggle } from './manual.js';
import { productQueueItemFromSearchResult } from './queue-item.js';

/**
 * @typedef {import('./queue-item.js').ProductSearchResult} ProductResult
 */

function readConfig() {
    const root = document.querySelector('[data-bc-app]');
    if (!root) return null;

    try {
        return JSON.parse(root.dataset.bcConfig || '{}');
    } catch {
        return null;
    }
}

function templateToSettings(template) {
    return {
        paper_size: template.paper_size,
        rows: template.rows,
        columns: template.columns,
        margin_top: template.margin_top,
        margin_right: template.margin_right,
        margin_bottom: template.margin_bottom,
        margin_left: template.margin_left,
        spacing_horizontal: template.spacing_horizontal,
        spacing_vertical: template.spacing_vertical,
        label_width: template.label_width,
        label_height: template.label_height,
        label_orientation: template.label_orientation || 'vertical',
        ...labelStyleFromTemplate(template),
    };
}

function bindQtyStepper(container, onChange) {
    const decrease = container.querySelector('[data-bc-qty-decrease], [data-bc-add-qty-decrease]');
    const increase = container.querySelector('[data-bc-qty-increase], [data-bc-add-qty-increase]');
    const input = container.querySelector('[data-bc-qty-input], [data-bc-add-qty-input]');

    decrease?.addEventListener('click', () => {
        if (!(input instanceof HTMLInputElement)) return;
        input.value = String(Math.max(1, Number(input.value) - 1));
        onChange(Number(input.value));
    });

    increase?.addEventListener('click', () => {
        if (!(input instanceof HTMLInputElement)) return;
        input.value = String(Math.max(1, Number(input.value) + 1));
        onChange(Number(input.value));
    });

    input?.addEventListener('change', () => {
        if (!(input instanceof HTMLInputElement)) return;
        const value = Math.max(1, Number(input.value) || 1);
        input.value = String(value);
        onChange(value);
    });
}

function initBarcodeCenter() {
    const config = readConfig();
    if (!config) return;

    const queue = new BarcodeQueue();
    let settings = templateToSettings(config.defaultTemplate);
    let currentPage = 0;
    let zoom = 100;
    let showGuides = true;

    const searchInput = document.querySelector('[data-bc-search-input]');
    const searchResults = document.querySelector('[data-bc-search-results]');
    const searchList = document.querySelector('[data-bc-search-list]');
    const searchEmpty = document.querySelector('[data-bc-search-empty]');
    const searchResultTemplate = document.getElementById('bc-search-result-template');
    const queueBody = document.querySelector('[data-bc-queue-body]');
    const queueList = document.querySelector('[data-bc-queue-list]');
    const queueEmpty = document.querySelector('[data-bc-queue-empty]');
    const queueFooter = document.querySelector('[data-bc-queue-footer]');
    const queueItemTemplate = document.getElementById('bc-queue-item-template');
    const totalLabelsEl = document.querySelector('[data-bc-total-labels]');
    const totalProductsEl = document.querySelector('[data-bc-total-products]');
    const mobileBar = document.querySelector('[data-bc-mobile-bar]');
    const mobileTotalLabels = document.querySelector('[data-bc-mobile-total-labels]');
    const previewPage = document.querySelector('[data-bc-preview-page]');
    const previewCanvas = document.querySelector('[data-bc-preview-canvas]');
    const zoomSlider = document.querySelector('[data-bc-zoom]');
    const zoomValue = document.querySelector('[data-bc-zoom-value]');
    const pageLabel = document.querySelector('[data-bc-page-label]');
    const pagePrev = document.querySelector('[data-bc-page-prev]');
    const pageNext = document.querySelector('[data-bc-page-next]');
    const toggleGuides = document.querySelector('[data-bc-toggle-guides]');
    const templateSelect = document.querySelector('[data-bc-template-select]');
    const templateChips = document.querySelectorAll('[data-bc-template-chip]');
    const settingsToggle = document.querySelector('[data-bc-settings-toggle]');
    const settingsBody = document.querySelector('[data-bc-settings-body]');
    const settingInputs = document.querySelectorAll('[data-bc-setting]');
    const clearQueueBtn = document.querySelector('[data-bc-clear-queue]');
    const printBtns = document.querySelectorAll('[data-bc-print]');
    const openQueueBtn = document.querySelector('[data-bc-open-queue]');
    const queuePanel = document.querySelector('.bc-workspace__panel--queue');
    const searchPanel = document.querySelector('.bc-workspace__panel--search');
    const manualSection = document.querySelector('[data-bc-manual]');

    const i18n = config.i18n || {};

    function formatPageLabel(current, total) {
        return (i18n.page || 'Page :current of :total')
            .replace(':current', String(current))
            .replace(':total', String(total));
    }

    function renderSearchResults(results) {
        if (!searchResults || !searchList) return;

        searchList.innerHTML = '';

        if (results.length === 0) {
            searchResults.hidden = false;
            if (searchEmpty) searchEmpty.hidden = false;
            return;
        }

        searchResults.hidden = false;
        if (searchEmpty) searchEmpty.hidden = true;

        results.forEach((product) => {
            if (!searchResultTemplate) return;
            const node = searchResultTemplate.content.cloneNode(true);
            const item = node.querySelector('[data-bc-search-item]');
            if (!item) return;

            const thumb = item.querySelector('[data-bc-item-thumb]');
            const owner = item.querySelector('[data-bc-item-owner]');
            const name = item.querySelector('[data-bc-item-name]');
            const sku = item.querySelector('[data-bc-item-sku]');
            const addBtn = item.querySelector('[data-bc-add-to-queue]');
            const qtyWrap = item.querySelector('[data-bc-add-qty-wrap]');

            if (thumb instanceof HTMLElement) {
                if (product.thumbnail_url) {
                    thumb.style.backgroundImage = `url(${product.thumbnail_url})`;
                }
            }
            if (owner) owner.textContent = product.owner_name;
            if (name) name.textContent = product.product_name;
            if (sku) sku.textContent = product.sku;

            let addQty = 1;
            if (qtyWrap) {
                bindQtyStepper(qtyWrap, (value) => {
                    addQty = value;
                });
            }

            addBtn?.addEventListener('click', () => {
                queue.addItem(productQueueItemFromSearchResult(product, addQty));
                if (searchInput instanceof HTMLInputElement) {
                    searchInput.value = '';
                }
                renderSearchResults([]);
                searchResults.hidden = true;
                searchInput?.focus();
            });

            searchList.appendChild(node);
        });
    }

    function bindQueueItemEvents(item, line) {
        const lineId = line.id;
        const qtyWrap = item.querySelector('.bc-qty-stepper');

        if (qtyWrap) {
            bindQtyStepper(qtyWrap, (value) => queue.setQuantity(lineId, value));
        }

        item.querySelector('[data-bc-remove]')?.addEventListener('click', () => queue.remove(lineId));
        item.querySelector('[data-bc-duplicate]')?.addEventListener('click', () => queue.duplicate(lineId));
        item.querySelector('[data-bc-move-up]')?.addEventListener('click', () => queue.moveUp(lineId));
        item.querySelector('[data-bc-move-down]')?.addEventListener('click', () => queue.moveDown(lineId));
    }

    function renderQueue(snapshot) {
        const { lines, totalLabels, totalProducts } = snapshot;

        if (totalLabelsEl) totalLabelsEl.textContent = String(totalLabels);
        if (totalProductsEl) totalProductsEl.textContent = String(totalProducts);
        if (mobileTotalLabels) mobileTotalLabels.textContent = String(totalLabels);

        if (mobileBar) {
            mobileBar.hidden = totalProducts === 0;
        }

        if (queueFooter) {
            queueFooter.hidden = totalProducts === 0;
        }

        if (!queueBody) return;

        if (totalProducts === 0) {
            queueBody.innerHTML = `
                <div class="bc-queue__empty" data-bc-queue-empty>
                    <div class="bc-queue__empty-icon" aria-hidden="true">🏷</div>
                    <p class="bc-queue__empty-title"></p>
                    <p class="bc-queue__empty-hint"></p>
                </div>
            `;
            const emptyTitle = queueBody.querySelector('.bc-queue__empty-title');
            const emptyHint = queueBody.querySelector('.bc-queue__empty-hint');
            if (emptyTitle) emptyTitle.textContent = queueEmpty?.querySelector('.bc-queue__empty-title')?.textContent || 'Queue is empty';
            if (emptyHint) emptyHint.textContent = queueEmpty?.querySelector('.bc-queue__empty-hint')?.textContent || '';
            renderPreview();
            return;
        }

        if (!queueList && queueItemTemplate) {
            queueBody.innerHTML = '<ul class="bc-queue__list" role="list" data-bc-queue-list></ul>';
        }

        const list = queueBody.querySelector('[data-bc-queue-list]') || queueList;
        if (!list) return;

        list.innerHTML = '';

        lines.forEach((line, index) => {
            if (!queueItemTemplate) return;
            const node = queueItemTemplate.content.cloneNode(true);
            const item = node.querySelector('[data-bc-queue-item]');
            if (!item) return;

            item.dataset.lineId = line.id;
            item.dataset.position = String(index);

            const thumb = item.querySelector('.bc-queue-item__thumb');
            const owner = item.querySelector('.bc-queue-item__owner');
            const name = item.querySelector('.bc-queue-item__name');
            const sku = item.querySelector('.bc-queue-item__sku');
            const qtyInput = item.querySelector('[data-bc-qty-input]');
            const moveUp = item.querySelector('[data-bc-move-up]');
            const moveDown = item.querySelector('[data-bc-move-down]');

            if (thumb instanceof HTMLImageElement && line.thumbnail_url) {
                thumb.src = line.thumbnail_url;
            }
            if (owner) owner.textContent = line.owner_name;
            if (name) {
                name.textContent = line.title;
                if (line.source === 'MANUAL') {
                    const badge = document.createElement('span');
                    badge.className = 'bc-queue-item__badge';
                    badge.textContent = i18n.manualBadge || 'Manual';
                    name.appendChild(badge);
                }
            }
            if (sku) sku.textContent = line.barcode;
            if (qtyInput instanceof HTMLInputElement) qtyInput.value = String(line.quantity);
            if (moveUp instanceof HTMLButtonElement) moveUp.disabled = index === 0;
            if (moveDown instanceof HTMLButtonElement) moveDown.disabled = index === lines.length - 1;

            bindQueueItemEvents(item, line);
            list.appendChild(node);
        });

        renderPreview();
    }

    function readSettingsFromDom() {
        settingInputs.forEach((input) => {
            const key = input.dataset.bcSetting;
            if (!key) return;
            settings[key] = input instanceof HTMLSelectElement || input instanceof HTMLInputElement
                ? input.value
                : settings[key];
        });
    }

    function applySettingsToDom() {
        settingInputs.forEach((input) => {
            const key = input.dataset.bcSetting;
            if (!key || settings[key] === undefined) return;
            if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement) {
                input.value = String(settings[key]);
            }
        });
    }

    function renderPreview() {
        if (!previewPage || !previewCanvas) return;

        const labels = queue.expandedLabels();
        const result = renderPreviewPage(previewPage, labels, settings, currentPage, showGuides);

        previewCanvas.classList.toggle('bc-preview__canvas--guides', showGuides);
        applyZoom(previewCanvas, zoom);

        if (pageLabel) {
            pageLabel.textContent = formatPageLabel(result.currentPage, result.totalPages);
        }
        if (pagePrev instanceof HTMLButtonElement) {
            pagePrev.disabled = currentPage <= 0;
        }
        if (pageNext instanceof HTMLButtonElement) {
            pageNext.disabled = currentPage >= result.totalPages - 1;
        }
    }

    function selectTemplate(templateId) {
        const template = (config.templates || []).find((t) => String(t.id) === String(templateId));
        if (!template) return;

        settings = templateToSettings(template);
        applySettingsToDom();
        currentPage = 0;

        templateChips.forEach((chip) => {
            chip.classList.toggle('bc-template-chip--active', chip.dataset.templateId === String(templateId));
        });

        if (templateSelect instanceof HTMLSelectElement) {
            templateSelect.value = String(templateId);
        }

        renderPreview();
    }

    queue.subscribe(renderQueue);

    if (searchPanel instanceof HTMLElement) {
        initSearchModeToggle(searchPanel);
    }

    if (manualSection instanceof HTMLElement) {
        initManualBarcode(manualSection, {
            siteName: config.siteName || config.defaultOwnerName || '',
            sellers: config.sellers || [],
            generateUrl: config.routes?.generate || '',
            generateStrategy: config.generation?.defaultStrategy || 'random',
            i18n: {
                titleRequired: i18n.manualTitleRequired,
                barcodeRequired: i18n.manualBarcodeRequired,
                barcodeTooLong: i18n.barcodeTooLong,
                barcodeInvalidFormat: i18n.barcodeInvalidFormat,
                ownerRequired: i18n.ownerRequired,
                quantityInvalid: i18n.quantityInvalid,
                generateError: i18n.generateError,
                sequentialQuantity: i18n.manualSequentialQuantity,
                quantity: i18n.manualQuantity,
                sequentialNumericRequired: i18n.manualSequentialNumericRequired,
            },
            onAdd: (item) => {
                queue.addItem(item);
            },
            onAddMany: (items) => {
                items.forEach((item) => queue.addItem(item));
            },
        });
    }

    if (searchInput instanceof HTMLInputElement) {
        initSearch(searchInput, config.routes?.search || '', renderSearchResults, async (sku) => {
            try {
                const results = await searchProducts(config.routes?.search || '', sku, { exact: true });
                const exact = results[0];
                if (exact) {
                    queue.addItem(productQueueItemFromSearchResult(exact, 1));
                    searchInput.value = '';
                    renderSearchResults([]);
                    if (searchResults) searchResults.hidden = true;
                } else {
                    alert(i18n.skuNotFound || 'SKU not found');
                }
            } catch {
                alert(i18n.skuNotFound || 'SKU not found');
            }
            searchInput.focus();
        });
        searchInput.focus();
    }

    initScanner(async (sku) => {
        if (!sku) {
            return;
        }

        try {
            const results = await searchProducts(config.routes?.search || '', sku, { exact: true });
            const exact = results[0];
            if (exact) {
                queue.addItem(productQueueItemFromSearchResult(exact, 1));
                if (searchInput instanceof HTMLInputElement) {
                    searchInput.value = '';
                }
                renderSearchResults([]);
                if (searchResults) searchResults.hidden = true;
            } else {
                alert(i18n.skuNotFound || 'SKU not found');
            }
        } catch {
            alert(i18n.skuNotFound || 'SKU not found');
        }
    });

    templateSelect?.addEventListener('change', () => {
        if (templateSelect instanceof HTMLSelectElement) {
            selectTemplate(templateSelect.value);
        }
    });

    templateChips.forEach((chip) => {
        chip.addEventListener('click', () => {
            selectTemplate(chip.dataset.templateId || '');
        });
    });

    settingInputs.forEach((input) => {
        input.addEventListener('change', () => {
            readSettingsFromDom();
            currentPage = 0;
            renderPreview();
        });
    });

    settingsToggle?.addEventListener('click', () => {
        const expanded = settingsToggle.getAttribute('aria-expanded') !== 'false';
        settingsToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        if (settingsBody) settingsBody.hidden = expanded;
    });

    zoomSlider?.addEventListener('input', () => {
        if (!(zoomSlider instanceof HTMLInputElement)) return;
        zoom = Number(zoomSlider.value);
        if (zoomValue) zoomValue.textContent = `${zoom}%`;
        if (previewCanvas) applyZoom(previewCanvas, zoom);
    });

    pagePrev?.addEventListener('click', () => {
        if (currentPage > 0) {
            currentPage -= 1;
            renderPreview();
        }
    });

    pageNext?.addEventListener('click', () => {
        currentPage += 1;
        renderPreview();
    });

    toggleGuides?.addEventListener('click', () => {
        showGuides = !showGuides;
        toggleGuides.textContent = showGuides ? (i18n.hideGuides || 'Hide guides') : (i18n.showGuides || 'Show guides');
        renderPreview();
    });

    async function submitPrint() {
        const snapshot = queue.snapshot();

        if (snapshot.totalProducts === 0) {
            alert(i18n.queueEmpty || 'Queue is empty');
            return;
        }

        if (!config.routes?.print) {
            return;
        }

        readSettingsFromDom();

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        try {
            const response = await fetch(config.routes.print, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    lines: queue.toPayloadLines(),
                    settings: {
                        ...settings,
                        name: config.templates?.find((t) => String(t.id) === String(templateSelect?.value))?.name,
                    },
                    template_id: templateSelect instanceof HTMLSelectElement ? Number(templateSelect.value) || null : null,
                }),
            });

            if (!response.ok) {
                throw new Error('print failed');
            }

            const payload = await response.json();
            if (payload.print_url) {
                window.open(payload.print_url, '_blank', 'noopener');
            }
        } catch {
            alert(i18n.printError || 'Unable to print.');
        }
    }

    function loadReprintPayload() {
        const raw = sessionStorage.getItem('bc_reprint_payload');
        if (!raw) {
            return;
        }

        sessionStorage.removeItem('bc_reprint_payload');

        try {
            const payload = JSON.parse(raw);
            if (Array.isArray(payload.lines) && payload.lines.length > 0) {
                queue.loadItems(payload.lines);
            }
            if (payload.settings) {
                settings = { ...settings, ...payload.settings };
                applySettingsToDom();
                if (payload.settings.id && templateSelect instanceof HTMLSelectElement) {
                    templateSelect.value = String(payload.settings.id);
                }
            }
        } catch {
            // ignore invalid payload
        }
    }

    clearQueueBtn?.addEventListener('click', () => {
        if (confirm(i18n.clearConfirm || 'Clear queue?')) {
            queue.clear();
        }
    });

    printBtns.forEach((btn) => {
        btn.addEventListener('click', () => {
            submitPrint();
        });
    });

    openQueueBtn?.addEventListener('click', () => {
        queuePanel?.classList.add('bc-workspace__panel--queue-open');
    });

    queuePanel?.addEventListener('click', (event) => {
        if (event.target === queuePanel) {
            queuePanel.classList.remove('bc-workspace__panel--queue-open');
        }
    });

    applySettingsToDom();
    loadReprintPayload();
    renderQueue(queue.snapshot());
}

document.addEventListener('DOMContentLoaded', initBarcodeCenter);
