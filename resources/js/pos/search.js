import { routeUrl, apiGet } from './api.js';
import { showToast } from './state.js';
import { renderProductResults } from './render.js';
import { addToCart } from './cart.js';
import { focusBarcodeInput } from './barcode.js';

let searchTimer = null;

export function initSearch() {
    const input = document.getElementById('pos-search-input');
    const list = document.getElementById('pos-product-results');
    if (!input || !list) return;

    let focusedIndex = -1;

    input.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = input.value.trim();
        if (q === '') {
            renderProductResults([]);
            return;
        }
        searchTimer = setTimeout(() => performSearch(q), 200);
    });

    input.addEventListener('keydown', (event) => {
        const items = [...list.querySelectorAll('[data-pos-product-result]')];
        if (items.length === 0) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                focusedIndex = Math.min(focusedIndex + 1, items.length - 1);
                updateFocus(items, focusedIndex);
                break;
            case 'ArrowUp':
                event.preventDefault();
                focusedIndex = Math.max(focusedIndex - 1, 0);
                updateFocus(items, focusedIndex);
                break;
            case 'Enter':
                if (focusedIndex >= 0 && items[focusedIndex]) {
                    event.preventDefault();
                    addProductFromResult(items[focusedIndex]);
                } else if (input.value.trim() !== '') {
                    event.preventDefault();
                    void submitSearchAsBarcode(input.value.trim(), input);
                }
                break;
            case 'Escape':
                event.preventDefault();
                focusedIndex = -1;
                input.value = '';
                renderProductResults([]);
                break;
        }
    });

    list.addEventListener('click', (event) => {
        const result = event.target.closest('[data-pos-product-result]');
        if (result) addProductFromResult(result);
    });

    list.addEventListener('dblclick', (event) => {
        const result = event.target.closest('[data-pos-product-result]');
        if (result) addProductFromResult(result);
    });
}

async function performSearch(query) {
    try {
        const data = await apiGet(`${routeUrl('search')}?q=${encodeURIComponent(query)}`);
        renderProductResults(data.results || []);
    } catch (error) {
        showToast(error.message);
    }
}

async function addProductFromResult(result) {
    const uuid = result.dataset.productUuid;
    if (!uuid) return;

    try {
        await addToCart({ purchasableUuid: uuid });
        const input = document.getElementById('pos-search-input');
        if (input) input.value = '';
        renderProductResults([]);
        focusBarcodeInput();
    } catch (error) {
        showToast(error.message);
    }
}

async function submitSearchAsBarcode(sku, input) {
    input.value = '';
    renderProductResults([]);

    try {
        await addToCart({ sku });
        focusBarcodeInput();
    } catch (error) {
        showToast(error.message);
    }
}

function updateFocus(items, index) {
    items.forEach((item, i) => {
        item.classList.toggle('is-focused', i === index);
        if (i === index) item.scrollIntoView({ block: 'nearest' });
    });
}
