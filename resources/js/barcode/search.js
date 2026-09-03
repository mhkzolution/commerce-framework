/**
 * Barcode Center — product search with debounce and scanner support.
 */

const DEBOUNCE_MS = 200;
const SCAN_GAP_MS = 250;

/**
 * @param {string} value
 */
export function normalizeSku(value) {
    return String(value || '')
        .replace(/[\x00-\x1F\x7F]/g, '')
        .trim();
}

/**
 * @param {string} url
 * @param {string} query
 * @param {{ exact?: boolean }} options
 * @returns {Promise<Array<import('./index.js').ProductResult>>}
 */
export async function searchProducts(url, query, options = {}) {
    const normalized = normalizeSku(query);
    if (!normalized) {
        return [];
    }

    const params = new URLSearchParams({ q: normalized });
    if (options.exact) {
        params.set('exact', '1');
    }

    const response = await fetch(`${url}?${params.toString()}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (!response.ok) {
        throw new Error('Search failed');
    }

    const payload = await response.json();
    return payload.data || [];
}

/**
 * @param {HTMLInputElement} input
 * @param {string} searchUrl
 * @param {(results: Array<import('./index.js').ProductResult>) => void} onResults
 * @param {(sku: string) => void} [onExactMatch]
 */
export function initSearch(input, searchUrl, onResults, onExactMatch) {
    let timer = null;

    const runSearch = async () => {
        const query = normalizeSku(input.value);
        if (query.length < 1) {
            onResults([]);
            return;
        }

        try {
            const results = await searchProducts(searchUrl, query);
            onResults(results);
        } catch {
            onResults([]);
        }
    };

    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(runSearch, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        clearTimeout(timer);

        const sku = normalizeSku(input.value);
        if (onExactMatch && sku) {
            onExactMatch(sku);
            return;
        }

        runSearch();
    });

    return {
        focus: () => input.focus(),
        clear: () => {
            input.value = '';
            onResults([]);
        },
        searchNow: runSearch,
    };
}

/**
 * Hardware barcode scanner when search input is not focused.
 * @param {(sku: string) => void} onScan
 */
export function initScanner(onScan) {
    let buffer = '';
    let lastKeyTime = 0;

    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey && event.key.toLowerCase() === 'b') {
            event.preventDefault();
            const input = document.querySelector('[data-bc-search-input]');
            if (input instanceof HTMLInputElement) {
                input.focus();
            }
            return;
        }

        const searchInput = document.querySelector('[data-bc-search-input]');
        const target = event.target;

        const isBlockedInput = (
            (target instanceof HTMLInputElement
                || target instanceof HTMLTextAreaElement
                || target instanceof HTMLSelectElement)
            && target !== searchInput
        );

        if (isBlockedInput || target === searchInput) {
            return;
        }

        const now = Date.now();
        if (lastKeyTime > 0 && now - lastKeyTime > SCAN_GAP_MS) {
            buffer = '';
        }
        lastKeyTime = now;

        if (event.key === 'Enter') {
            if (buffer.length > 0) {
                event.preventDefault();
                onScan(normalizeSku(buffer));
                buffer = '';
            }
            return;
        }

        if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            event.preventDefault();
            buffer += event.key;
        }
    });
}
