import { apiGet, apiPost, apiPatch, routeUrl } from './api.js';
import { setState, showToast } from './state.js';
import { renderAll, renderCustomer } from './render.js';

let customerTimer = null;

export function initCustomer() {
    const input = document.getElementById('pos-customer-search-input');
    const results = document.getElementById('pos-customer-results');

    if (input && results) {
        input.addEventListener('input', () => {
            clearTimeout(customerTimer);
            const q = input.value.trim();
            customerTimer = setTimeout(() => searchCustomers(q, results), 250);
        });

        results.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-pos-customer-uuid]');
            if (!button) return;
            await attachCustomer(button.dataset.posCustomerUuid);
            closeDialog('pos-customer-dialog');
        });
    }

    document.addEventListener('click', async (event) => {
        if (event.target.closest('[data-pos-action="guest-checkout"]')) {
            await attachCustomer(null);
            closeDialog('pos-customer-dialog');
        }
        if (event.target.closest('[data-pos-action="detach-customer"]')) {
            await attachCustomer(null);
        }
    });
}

async function searchCustomers(query, container) {
    if (query === '') {
        container.innerHTML = '<p class="text-sm text-muted">Type to search customers.</p>';
        return;
    }

    try {
        const data = await apiGet(`${routeUrl('searchCustomers')}?q=${encodeURIComponent(query)}`);
        const results = data.results || [];

        if (!results.length) {
            container.innerHTML = '<p class="text-sm text-muted">No customers found.</p>';
            return;
        }

        container.innerHTML = results.map((customer) => `
            <button type="button" class="pos-btn pos-btn--secondary w-full justify-start text-left" data-pos-customer-uuid="${customer.uuid}">
                <span class="font-semibold">${escape(customer.name)}</span>
                <span class="block text-xs text-muted">${escape(customer.phone || customer.email || '')}</span>
            </button>
        `).join('');
    } catch (error) {
        showToast(error.message);
    }
}

async function attachCustomer(uuid) {
    const data = await apiPost(routeUrl('attachCustomer'), { customer_uuid: uuid });
    setState(data);
    renderAll(data);
}

export async function saveNotes(notes) {
    const data = await apiPatch(routeUrl('updateNotes'), { notes });
    setState(data);
}

function closeDialog(id) {
    const dialog = document.getElementById(id);
    if (dialog) dialog.hidden = true;
}

function escape(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}
