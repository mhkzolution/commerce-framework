/**
 * Barcode Center — manual (non-product) label creation.
 */

import { manualQueueItemFromInput } from './queue-item.js';
import { validateManualQueueItem } from './queue-validator.js';
import { buildNumericSequence, isNumericBarcode } from './sequential-barcode.js';

/**
 * @param {HTMLElement} root
 * @param {{
 *   siteName: string,
 *   sellers: Array<{uuid: string, name: string}>,
 *   generateUrl: string,
 *   generateStrategy: string,
 *   i18n: Record<string, string>,
 *   onAdd: (item: Omit<import('./queue-item.js').QueueItem, 'id'>) => void
 *   onAddMany: (items: Array<Omit<import('./queue-item.js').QueueItem, 'id'>>) => void
 * }} options
 */
export function initManualBarcode(root, options) {
    const form = root.querySelector('[data-bc-manual-form]');
    const nameInput = root.querySelector('[data-bc-manual-name]');
    const barcodeInput = root.querySelector('[data-bc-manual-barcode]');
    const skuInput = root.querySelector('[data-bc-manual-sku]');
    const sellerSelect = root.querySelector('[data-bc-manual-seller]');
    const generateBtn = root.querySelector('[data-bc-manual-generate]');
    const sequentialToggle = root.querySelector('[data-bc-manual-sequential]');
    const sequentialHint = root.querySelector('[data-bc-manual-sequential-hint]');
    const qtyLabel = root.querySelector('[data-bc-manual-qty-label]');
    const qtyWrap = root.querySelector('[data-bc-manual-qty-wrap]');
    const i18n = options.i18n || {};

    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    function isSequentialMode() {
        return sequentialToggle instanceof HTMLInputElement && sequentialToggle.checked;
    }

    function updateSequentialUi() {
        const enabled = isSequentialMode();

        if (sequentialHint instanceof HTMLElement) {
            sequentialHint.hidden = !enabled;
        }

        if (qtyLabel) {
            qtyLabel.textContent = enabled
                ? (i18n.sequentialQuantity || 'Number of SKUs to create')
                : (i18n.quantity || 'Quantity');
        }
    }

    sequentialToggle?.addEventListener('change', updateSequentialUi);
    updateSequentialUi();

    generateBtn?.addEventListener('click', async () => {
        if (!(barcodeInput instanceof HTMLInputElement) || !options.generateUrl) {
            return;
        }

        try {
            const url = new URL(options.generateUrl, window.location.origin);

            if (isSequentialMode() && isNumericBarcode(barcodeInput.value)) {
                const qtyInput = qtyWrap?.querySelector('[data-bc-manual-qty-input]');
                const count = qtyInput instanceof HTMLInputElement
                    ? Math.max(1, Number(qtyInput.value) || 1)
                    : 1;

                url.searchParams.set('strategy', 'numeric_sequence');
                url.searchParams.set('start', barcodeInput.value.trim());
                url.searchParams.set('count', String(count));

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('generate failed');
                }

                const payload = await response.json();
                const barcodes = Array.isArray(payload.barcodes) ? payload.barcodes : [];
                if (barcodes.length > 0) {
                    barcodeInput.value = String(barcodes[0]);
                }
            } else {
                url.searchParams.set('strategy', options.generateStrategy || 'random');

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('generate failed');
                }

                const payload = await response.json();
                barcodeInput.value = String(payload.barcode ?? '');
            }

            barcodeInput.focus();
        } catch {
            alert(i18n.generateError || 'Unable to generate barcode.');
        }
    });

    if (qtyWrap) {
        const decrease = qtyWrap.querySelector('[data-bc-manual-qty-decrease]');
        const increase = qtyWrap.querySelector('[data-bc-manual-qty-increase]');
        const qtyInput = qtyWrap.querySelector('[data-bc-manual-qty-input]');

        decrease?.addEventListener('click', () => {
            if (!(qtyInput instanceof HTMLInputElement)) return;
            qtyInput.value = String(Math.max(1, Number(qtyInput.value) - 1));
        });

        increase?.addEventListener('click', () => {
            if (!(qtyInput instanceof HTMLInputElement)) return;
            qtyInput.value = String(Math.max(1, Number(qtyInput.value) + 1));
        });
    }

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        if (!(nameInput instanceof HTMLInputElement) || !(barcodeInput instanceof HTMLInputElement)) {
            return;
        }

        const qtyInput = qtyWrap?.querySelector('[data-bc-manual-qty-input]');
        const quantity = qtyInput instanceof HTMLInputElement
            ? Math.max(1, Number(qtyInput.value) || 1)
            : 1;

        const baseInput = {
            name: nameInput.value.trim(),
            sku: skuInput instanceof HTMLInputElement ? skuInput.value.trim() : '',
            seller_uuid: sellerSelect instanceof HTMLSelectElement ? sellerSelect.value : null,
        };
        const context = {
            siteName: options.siteName || '',
            sellers: options.sellers || [],
        };

        if (isSequentialMode()) {
            const startBarcode = barcodeInput.value.trim();

            if (!isNumericBarcode(startBarcode)) {
                alert(i18n.sequentialNumericRequired || 'Sequential mode requires a numeric starting barcode.');
                barcodeInput.focus();
                return;
            }

            const barcodes = buildNumericSequence(startBarcode, quantity);
            const items = [];

            for (const barcode of barcodes) {
                const item = manualQueueItemFromInput({
                    ...baseInput,
                    name: baseInput.name,
                    barcode,
                    sku: baseInput.sku || barcode,
                }, 1, context);

                const error = validateManualQueueItem(item, [], i18n);
                if (error) {
                    alert(error);
                    return;
                }

                items.push(item);
            }

            if (items.length === 0) {
                return;
            }

            options.onAddMany(items);
        } else {
            const item = manualQueueItemFromInput({
                ...baseInput,
                barcode: barcodeInput.value.trim(),
            }, quantity, context);

            const error = validateManualQueueItem(item, [], i18n);
            if (error) {
                alert(error);
                return;
            }

            options.onAdd(item);
        }

        form.reset();
        if (qtyInput instanceof HTMLInputElement) {
            qtyInput.value = '1';
        }
        if (sequentialToggle instanceof HTMLInputElement) {
            sequentialToggle.checked = false;
        }
        updateSequentialUi();
        nameInput.focus();
    });
}

/**
 * @param {HTMLElement} searchPanel
 */
export function initSearchModeToggle(searchPanel) {
    const productTab = searchPanel.querySelector('[data-bc-mode-products]');
    const manualTab = searchPanel.querySelector('[data-bc-mode-manual]');
    const searchSection = searchPanel.querySelector('[data-bc-search-section]');
    const manualSection = searchPanel.querySelector('[data-bc-manual]');

    function setMode(mode) {
        const isManual = mode === 'manual';

        productTab?.classList.toggle('bc-search-mode__tab--active', !isManual);
        manualTab?.classList.toggle('bc-search-mode__tab--active', isManual);
        productTab?.setAttribute('aria-selected', isManual ? 'false' : 'true');
        manualTab?.setAttribute('aria-selected', isManual ? 'true' : 'false');

        if (searchSection instanceof HTMLElement) {
            searchSection.hidden = isManual;
        }
        if (manualSection instanceof HTMLElement) {
            manualSection.hidden = !isManual;
        }
    }

    productTab?.addEventListener('click', () => setMode('products'));
    manualTab?.addEventListener('click', () => setMode('manual'));
}
