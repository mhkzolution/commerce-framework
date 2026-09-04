const debounce = (fn, wait = 250) => {
    let timer;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => fn(...args), wait);
    };
};

const money = (cents) => (Number(cents || 0) / 100).toFixed(2);

const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

const initOrderCreate = (root) => {
    const i18n = JSON.parse(root.dataset.i18n || '{}');
    const customersUrl = root.dataset.customersUrl;
    const productsUrl = root.dataset.productsUrl;
    const customerSearch = root.querySelector('[data-customer-search]');
    const customerResults = root.querySelector('[data-customer-results]');
    const productSearch = root.querySelector('[data-product-search]');
    const productResults = root.querySelector('[data-product-results]');
    const linesEl = root.querySelector('[data-lines]');
    const emptyEl = root.querySelector('[data-lines-empty]');
    const intentInput = root.querySelector('[data-intent]');
    const createBtn = root.querySelector('[data-submit-create]');
    const draftBtn = root.querySelector('[data-submit-draft]');
    let activeCustomerIndex = -1;
    let activeProductIndex = -1;
    let customerHits = [];
    let productHits = [];
    let submitting = false;

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

    const fetchJson = async (url) => {
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrf() || '',
            },
        });

        if (!response.ok) {
            return { results: [] };
        }

        return response.json();
    };

    const highlight = (container, index) => {
        container.querySelectorAll('.order-create-result').forEach((row, rowIndex) => {
            row.classList.toggle('is-active', rowIndex === index);
        });
    };

    const renderResults = (container, items, type, activeIndex) => {
        container.hidden = false;

        if (type === 'customer') {
            customerSearch.setAttribute('aria-expanded', 'true');
        } else {
            productSearch.setAttribute('aria-expanded', 'true');
        }

        if (!items.length) {
            container.innerHTML = `<p class="px-3 py-2 text-sm text-muted">${escapeHtml(i18n.noResults)}</p>`;
            return;
        }

        container.innerHTML = items
            .map((item, index) => {
                if (type === 'customer') {
                    const avatar = item.avatar_url
                        ? `<img src="${escapeHtml(item.avatar_url)}" alt="" class="h-10 w-10 rounded-full object-cover">`
                        : `<span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-subtle text-sm font-semibold">${escapeHtml((item.name || '?').slice(0, 1))}</span>`;

                    return `<button type="button" class="order-create-result${index === activeIndex ? ' is-active' : ''}" data-pick-customer="${index}" role="option">
                        ${avatar}
                        <span class="min-w-0">
                            <span class="block truncate font-medium">${escapeHtml(item.name || '')}</span>
                            <span class="block truncate text-xs text-muted">${escapeHtml(item.email || '')} ${escapeHtml(item.phone || '')}</span>
                        </span>
                    </button>`;
                }

                const stock = item.stock_status === 'out_of_stock'
                    ? i18n.outOfStock
                    : (item.stock_status === 'low_stock' ? i18n.lowStock : i18n.inStock).replace(':count', item.available);

                return `<button type="button" class="order-create-result${index === activeIndex ? ' is-active' : ''}" data-pick-product="${index}" role="option">
                    <span class="order-create-thumb">${item.image_url ? `<img src="${escapeHtml(item.image_url)}" alt="">` : ''}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate font-medium">${escapeHtml(item.product_name || '')}</span>
                        <span class="block truncate text-xs text-muted">${escapeHtml(item.variant_name || '')} · ${escapeHtml(item.sku || '')} · ${money(item.price)}</span>
                        <span class="block text-xs text-muted">${escapeHtml(stock)}</span>
                    </span>
                </button>`;
            })
            .join('');
    };

    const hideResults = (container, input) => {
        container.hidden = true;
        input.setAttribute('aria-expanded', 'false');
    };

    const fillShipping = (address = {}, customer = {}) => {
        root.querySelector('[data-ship-name]').value = address.recipient_name || customer.name || '';
        root.querySelector('[data-ship-phone]').value = address.phone || customer.phone || '';
        root.querySelector('[data-ship-line1]').value = address.line1 || '';
        root.querySelector('[data-ship-line2]').value = address.line2 || '';
        root.querySelector('[data-ship-district]').value = address.district || '';
        root.querySelector('[data-ship-subdistrict]').value = address.subdistrict || '';
        root.querySelector('[data-ship-province]').value = address.province || '';
        root.querySelector('[data-ship-postal]').value = address.postal_code || '';
    };

    const selectCustomer = (customer) => {
        root.querySelector('[data-customer-uuid]').value = customer.uuid || '';
        root.querySelector('[data-customer-name]').value = customer.name || '';
        root.querySelector('[data-customer-email]').value = customer.email || '';
        root.querySelector('[data-customer-phone]').value = customer.phone || '';
        root.querySelector('[data-customer-mode]').textContent = customer.name || '';
        fillShipping(customer.address || {}, customer);
        hideResults(customerResults, customerSearch);
        updateSummary();
    };

    const continueAsGuest = () => {
        root.querySelector('[data-customer-uuid]').value = '';
        const mode = root.querySelector('[data-customer-mode]');
        mode.textContent = mode.dataset.guestLabel || 'Guest';
        hideResults(customerResults, customerSearch);
        updateSummary();
    };

    const lines = () => [...linesEl.querySelectorAll('[data-line]')];

    const addLine = (product, quantity = 1) => {
        const existing = linesEl.querySelector(`[data-line][data-uuid="${product.purchasable_uuid}"]`);
        if (existing) {
            const qty = existing.querySelector('[data-qty]');
            const available = Number(product.available || existing.dataset.available || 0);
            const next = Number(qty.value || 1) + Number(quantity || 1);
            qty.value = String(available > 0 ? Math.min(available, next) : next);
            updateSummary();
            return;
        }

        emptyEl.hidden = true;
        const row = document.createElement('div');
        row.className = 'order-create-line';
        row.dataset.line = '1';
        row.dataset.uuid = product.purchasable_uuid;
        row.dataset.price = String(product.price || 0);
        row.dataset.available = String(product.available || 0);
        row.dataset.name = product.product_name || '';
        const maxQty = Math.max(Number(product.available || 0), 1);
        const unitMajor = product.unit_price !== undefined && product.unit_price !== null && product.unit_price !== ''
            ? Number(product.unit_price).toFixed(2)
            : money(product.price);
        row.innerHTML = `
            <input type="hidden" name="lines[${escapeHtml(product.purchasable_uuid)}][purchasable_uuid]" value="${escapeHtml(product.purchasable_uuid)}">
            <div class="order-create-thumb">${product.image_url ? `<img src="${escapeHtml(product.image_url)}" alt="">` : ''}</div>
            <div class="min-w-0">
                <p class="font-medium">${escapeHtml(product.product_name || '')}</p>
                <p class="text-xs text-muted">${escapeHtml(product.variant_name || '')} · ${escapeHtml(i18n.sku)} ${escapeHtml(product.sku || '')}</p>
                <p class="mt-1 text-xs" data-stock></p>
            </div>
            <div class="space-y-2 text-right">
                <label class="block text-xs text-muted">${escapeHtml(i18n.unitPrice || 'Unit price')}</label>
                <input type="number" min="0" step="0.01" name="lines[${escapeHtml(product.purchasable_uuid)}][unit_price]" value="${unitMajor}" class="cf-input w-24" data-unit-price>
                <input type="number" min="1" max="${maxQty}" name="lines[${escapeHtml(product.purchasable_uuid)}][quantity]" value="${Math.max(1, Number(quantity) || 1)}" class="cf-input w-20" data-qty>
                <div class="text-sm font-semibold" data-line-total>${money(product.price)}</div>
                <button type="button" class="text-sm text-danger" data-remove>${escapeHtml(i18n.remove)}</button>
            </div>
        `;
        linesEl.appendChild(row);
        updateSummary();
    };

    const stockLabel = (available) => {
        if (available <= 0) {
            return i18n.outOfStock;
        }
        if (available <= 5) {
            return i18n.lowStock.replace(':count', available);
        }

        return i18n.inStock.replace(':count', available);
    };

    const warnings = () => {
        const items = [];
        if (!lines().length) {
            items.push(i18n.warnNoProducts);
        }
        if (!root.querySelector('[data-customer-phone]').value.trim()) {
            items.push(i18n.warnNoPhone);
        }
        if (lines().some((line) => {
            const available = Number(line.dataset.available || 0);
            const qty = Number(line.querySelector('[data-qty]').value || 0);

            return available <= 0 || qty > available;
        })) {
            items.push(i18n.warnOutOfStock);
        }

        return items;
    };

    const updateSummary = () => {
        let subtotal = 0;
        const compact = [];

        lines().forEach((line) => {
            const qtyInput = line.querySelector('[data-qty]');
            let qty = Math.max(1, Number(qtyInput.value || 1));
            const available = Number(line.dataset.available || 0);
            const unitInput = line.querySelector('[data-unit-price]');
            const price = unitInput
                ? Math.round(Number(unitInput.value || 0) * 100)
                : Number(line.dataset.price || 0);
            line.dataset.price = String(price);

            if (available > 0 && qty > available) {
                qty = available;
            }

            qtyInput.value = String(qty);
            qtyInput.max = String(Math.max(available, 1));
            line.classList.toggle('is-invalid', available <= 0 || qty > available);

            const lineTotal = price * qty;
            subtotal += lineTotal;
            line.querySelector('[data-line-total]').textContent = money(lineTotal);
            line.querySelector('[data-stock]').textContent = stockLabel(available);
            compact.push(`${line.dataset.name} × ${qty}`);
        });

        emptyEl.hidden = lines().length > 0;
        const discountType = root.querySelector('[data-discount-type]').value;
        const discountValue = Number(root.querySelector('[data-discount-value]').value || 0);
        const shipping = Math.round(Number(root.querySelector('[data-shipping-fee]').value || 0) * 100);
        const tax = Math.round(Number(root.querySelector('[data-tax-total]').value || 0) * 100);
        const discount = discountType === 'percent'
            ? Math.floor(subtotal * Math.min(100, Math.max(0, discountValue)) / 100)
            : Math.min(subtotal, Math.round(discountValue * 100));
        const total = Math.max(0, subtotal - discount + shipping + tax);

        root.querySelector('[data-item-summary]').innerHTML = compact.length
            ? compact.map((row) => `<div>${escapeHtml(row)}</div>`).join('')
            : `<p class="text-muted">${escapeHtml(i18n.productEmpty)}</p>`;
        root.querySelector('[data-summary-subtotal]').textContent = money(subtotal);
        root.querySelector('[data-summary-discount]').textContent = money(discount);
        root.querySelector('[data-summary-shipping]').textContent = money(shipping);
        root.querySelector('[data-summary-tax]').textContent = money(tax);
        root.querySelector('[data-summary-total]').textContent = money(total);

        const list = warnings();
        const box = root.querySelector('[data-warnings]');
        box.hidden = list.length === 0;
        root.querySelector('[data-warning-list]').innerHTML = list.map((item) => `<li>${escapeHtml(item)}</li>`).join('');
        const blocked = list.length > 0 || submitting;
        createBtn.disabled = blocked;
        draftBtn.disabled = blocked;
    };

    const searchCustomers = debounce(async () => {
        const q = customerSearch.value.trim();
        if (q.length < 1) {
            hideResults(customerResults, customerSearch);
            return;
        }
        customerResults.hidden = false;
        customerResults.innerHTML = `<p class="px-3 py-2 text-sm text-muted">${escapeHtml(i18n.searching)}</p>`;
        const payload = await fetchJson(`${customersUrl}?q=${encodeURIComponent(q)}`);
        customerHits = payload.results || [];
        activeCustomerIndex = 0;
        renderResults(customerResults, customerHits, 'customer', activeCustomerIndex);
    });

    const searchProducts = debounce(async () => {
        const q = productSearch.value.trim();
        if (q.length < 1) {
            hideResults(productResults, productSearch);
            return;
        }
        productResults.hidden = false;
        productResults.innerHTML = `<p class="px-3 py-2 text-sm text-muted">${escapeHtml(i18n.searching)}</p>`;
        const payload = await fetchJson(`${productsUrl}?q=${encodeURIComponent(q)}`);
        productHits = payload.results || [];
        activeProductIndex = 0;
        renderResults(productResults, productHits, 'product', activeProductIndex);
    });

    const moveIndex = (event, hits, type) => {
        const container = type === 'customer' ? customerResults : productResults;
        if (container.hidden && event.key !== 'Enter') {
            return;
        }
        if (!['ArrowDown', 'ArrowUp', 'Enter', 'Escape'].includes(event.key)) {
            return;
        }

        event.preventDefault();

        if (event.key === 'Escape') {
            if (type === 'customer') {
                hideResults(customerResults, customerSearch);
            } else {
                hideResults(productResults, productSearch);
            }
            return;
        }

        if (event.key === 'Enter') {
            const current = hits[type === 'customer' ? activeCustomerIndex : activeProductIndex];
            if (!current) {
                return;
            }
            if (type === 'customer') {
                selectCustomer(current);
            } else {
                addLine(current);
                hideResults(productResults, productSearch);
                productSearch.value = '';
            }
            return;
        }

        if (!hits.length) {
            return;
        }

        const delta = event.key === 'ArrowDown' ? 1 : -1;
        if (type === 'customer') {
            activeCustomerIndex = (activeCustomerIndex + delta + hits.length) % hits.length;
            highlight(customerResults, activeCustomerIndex);
        } else {
            activeProductIndex = (activeProductIndex + delta + hits.length) % hits.length;
            highlight(productResults, activeProductIndex);
        }
    };

    const sameBilling = root.querySelector('[data-billing-same]');
    const billingFields = root.querySelector('[data-billing-fields]');
    const syncBillingVisibility = () => {
        if (!billingFields || !sameBilling) {
            return;
        }

        billingFields.hidden = sameBilling.checked;
    };

    if (sameBilling) {
        sameBilling.addEventListener('change', syncBillingVisibility);
        syncBillingVisibility();
    }

    customerSearch.addEventListener('input', searchCustomers);
    productSearch.addEventListener('input', searchProducts);
    customerSearch.addEventListener('keydown', (event) => moveIndex(event, customerHits, 'customer'));
    productSearch.addEventListener('keydown', (event) => moveIndex(event, productHits, 'product'));

    customerResults.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pick-customer]');
        if (!button) {
            return;
        }
        selectCustomer(customerHits[Number(button.dataset.pickCustomer)]);
    });

    productResults.addEventListener('click', (event) => {
        const button = event.target.closest('[data-pick-product]');
        if (!button) {
            return;
        }
        addLine(productHits[Number(button.dataset.pickProduct)]);
        hideResults(productResults, productSearch);
        productSearch.value = '';
    });

    root.querySelector('[data-add-product]').addEventListener('click', () => {
        if (productHits[activeProductIndex] || productHits[0]) {
            addLine(productHits[activeProductIndex] || productHits[0]);
            hideResults(productResults, productSearch);
            productSearch.value = '';
        } else {
            productSearch.focus();
        }
    });

    root.querySelector('[data-guest]').addEventListener('click', continueAsGuest);

    linesEl.addEventListener('input', (event) => {
        if (event.target.matches('[data-qty], [data-unit-price]')) {
            updateSummary();
        }
    });
    linesEl.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove]')) {
            event.target.closest('[data-line]').remove();
            updateSummary();
        }
    });

    ['discount-type', 'discount-value', 'shipping-fee', 'tax-total'].forEach((key) => {
        root.querySelector(`[data-${key}]`).addEventListener('input', updateSummary);
        root.querySelector(`[data-${key}]`).addEventListener('change', updateSummary);
    });
    root.querySelector('[data-customer-phone]').addEventListener('input', updateSummary);

    root.querySelector('[data-submit-draft]').addEventListener('click', () => {
        intentInput.value = 'draft';
    });
    root.querySelector('[data-submit-create]').addEventListener('click', () => {
        intentInput.value = 'create';
    });

    root.addEventListener('submit', (event) => {
        updateSummary();
        if (warnings().length) {
            event.preventDefault();
            return;
        }

        submitting = true;
        createBtn.disabled = true;
        draftBtn.disabled = true;
        if (intentInput.value === 'draft') {
            draftBtn.textContent = i18n.saving || 'Saving…';
        } else {
            createBtn.textContent = i18n.creating || 'Creating…';
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-customer-search], [data-customer-results]')) {
            hideResults(customerResults, customerSearch);
        }
        if (!event.target.closest('[data-product-search], [data-product-results], [data-add-product]')) {
            hideResults(productResults, productSearch);
        }
    });

    try {
        const initial = JSON.parse(root.dataset.initialLines || '[]');
        if (Array.isArray(initial)) {
            initial.forEach((item) => addLine(item, item.quantity || 1));
        }
    } catch {
        // Ignore malformed restoration payload.
    }

    updateSummary();
};

document.querySelectorAll('[data-order-create]').forEach(initOrderCreate);
