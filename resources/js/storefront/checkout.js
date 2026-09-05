const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const fillAddressFields = (prefix, address) => {
    const map = {
        recipient_name: address.recipient_name || '',
        phone: address.phone || '',
        line1: address.line1 || '',
        line2: address.line2 || '',
        city: address.city || address.district || '',
        state: address.state || address.province || '',
        postal_code: address.postal_code || '',
        country_code: address.country_code || '',
        district: address.district || '',
        subdistrict: address.subdistrict || '',
    };

    Object.entries(map).forEach(([field, value]) => {
        document.querySelectorAll(`[data-address-prefix="${prefix}"][data-address-field="${field}"]`).forEach((input) => {
            input.value = value;
        });
    });

    const root = document.querySelector(`[data-address-prefix="${prefix}"][data-address-country]`)?.closest('[data-thailand-address]');

    if (root) {
        const province = root.querySelector('[data-thailand-province]');
        const district = root.querySelector('[data-thailand-district]');
        const subdistrict = root.querySelector('[data-thailand-subdistrict]');

        if (province) {
            province.dataset.selected = map.state;
        }

        if (district) {
            district.dataset.selected = map.district;
        }

        if (subdistrict) {
            subdistrict.dataset.selected = map.subdistrict;
        }
    }

    document.dispatchEvent(new CustomEvent('storefront:address-sync'));
};

const setEditorFieldsEnabled = (editor, enabled) => {
    editor.querySelectorAll('input, select, textarea').forEach((input) => {
        if (input.type === 'hidden' && input.name?.includes('_token')) {
            return;
        }

        input.disabled = !enabled;
    });
};

const setAddressEditorOpen = (role, open, { mode = 'add' } = {}) => {
    const editor = document.querySelector(`[data-address-editor="${role}"]`);
    const addButton = document.querySelector(`[data-add-address="${role}"]`);

    if (!editor) {
        return;
    }

    editor.hidden = !open;
    setEditorFieldsEnabled(editor, open);
    addButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
    addButton?.classList.toggle('is-active', open && mode === 'add');

    const title = editor.querySelector('.storefront-address-editor__title');

    if (title) {
        title.textContent = mode === 'edit'
            ? (title.dataset.titleEdit || title.textContent)
            : (title.dataset.titleAdd || title.textContent);
    }

    if (open) {
        document.dispatchEvent(new CustomEvent('storefront:address-sync'));
        editor.querySelector('input:not([type="hidden"]):not([disabled])')?.focus();
    }
};

const selectAddressCard = (card, { edit = false } = {}) => {
    const role = card.dataset.addressRole;
    const uuid = card.dataset.addressUuid;
    let address = {};

    try {
        address = JSON.parse(card.dataset.address || '{}');
    } catch {
        address = {};
    }

    document.querySelectorAll(`[data-address-role="${role}"]`).forEach((item) => {
        const selected = item === card;
        item.dataset.selected = selected ? '1' : '0';
        item.setAttribute('aria-checked', selected ? 'true' : 'false');
    });

    const uuidInput = document.querySelector(`[data-address-uuid-input="${role}"]`);
    const updateInput = document.querySelector(`[data-update-address-uuid="${role}"]`);

    if (uuidInput) {
        uuidInput.value = edit ? '' : (uuid || '');
    }

    if (updateInput) {
        updateInput.value = edit ? (uuid || '') : '';
    }

    fillAddressFields(role === 'billing' ? 'billing_address' : 'shipping_address', address);
    setAddressEditorOpen(role, edit, { mode: edit ? 'edit' : 'add' });
};

const clearAddressSelection = (role) => {
    document.querySelectorAll(`[data-address-role="${role}"]`).forEach((item) => {
        item.dataset.selected = '0';
        item.setAttribute('aria-checked', 'false');
    });

    const uuidInput = document.querySelector(`[data-address-uuid-input="${role}"]`);
    const updateInput = document.querySelector(`[data-update-address-uuid="${role}"]`);

    if (uuidInput) {
        uuidInput.value = '';
    }

    if (updateInput) {
        updateInput.value = '';
    }
};

const blankAddressFromContact = () => ({
    recipient_name: document.querySelector('[data-contact-name]')?.value
        || document.querySelector('.storefront-contact-card__name')?.textContent?.trim()
        || '',
    phone: document.querySelector('[data-contact-phone], #customer_phone')?.value || '',
    line1: '',
    line2: '',
    city: '',
    state: '',
    postal_code: '',
    country_code: 'TH',
    district: '',
    subdistrict: '',
});

const initAddressCards = (root) => {
    root.querySelectorAll('.storefront-address-card').forEach((card) => {
        card.addEventListener('click', (event) => {
            if (event.target.closest('[data-edit-address]')) {
                return;
            }

            selectAddressCard(card);
        });

        card.addEventListener('keydown', (event) => {
            if (event.target.closest('[data-edit-address]')) {
                return;
            }

            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            selectAddressCard(card);
        });
    });

    root.querySelectorAll('[data-edit-address]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const card = button.closest('.storefront-address-card');

            if (card) {
                selectAddressCard(card, { edit: true });
            }
        });
    });

    root.querySelectorAll('[data-add-address]').forEach((button) => {
        button.addEventListener('click', () => {
            const role = button.dataset.addAddress;
            const prefix = role === 'billing' ? 'billing_address' : 'shipping_address';
            clearAddressSelection(role);
            fillAddressFields(prefix, blankAddressFromContact());
            setAddressEditorOpen(role, true, { mode: 'add' });
        });
    });

    root.querySelectorAll('[data-cancel-address]').forEach((button) => {
        button.addEventListener('click', () => {
            const role = button.dataset.cancelAddress;
            const selected = root.querySelector(`[data-address-role="${role}"][data-selected="1"]`);
            setAddressEditorOpen(role, false);

            if (selected) {
                selectAddressCard(selected);
            }
        });
    });

    ['shipping_address', 'billing_address'].forEach((prefix) => {
        root.querySelectorAll(`[data-address-prefix="${prefix}"]`).forEach((input) => {
            input.addEventListener('input', () => {
                const role = prefix === 'billing_address' ? 'billing' : 'shipping';
                const uuidInput = document.querySelector(`[data-address-uuid-input="${role}"]`);

                if (uuidInput) {
                    uuidInput.value = '';
                }

                document.querySelectorAll(`[data-address-role="${role}"]`).forEach((item) => {
                    item.dataset.selected = '0';
                    item.setAttribute('aria-checked', 'false');
                });
            });
        });
    });

    root.querySelectorAll('[data-address-editor]').forEach((editor) => {
        setEditorFieldsEnabled(editor, !editor.hidden);
    });
};

const initBillingToggle = () => {
    const sameAsShipping = document.getElementById('billing_same_as_shipping');
    const billingFields = document.getElementById('billing-address-fields');

    if (!sameAsShipping) {
        return;
    }

    const sync = () => {
        if (billingFields) {
            billingFields.classList.toggle('storefront-is-hidden', sameAsShipping.checked);
        }
    };

    sameAsShipping.addEventListener('change', sync);
    sync();
};

const initTotals = (root) => {
    const subtotalEl = document.getElementById('checkout-subtotal');
    const shippingEl = document.getElementById('checkout-shipping');
    const totalEl = document.getElementById('checkout-total');
    const totalMobileEl = document.getElementById('checkout-total-mobile');
    const toggleTotal = root.querySelector('.storefront-checkout__summary-toggle-total');
    const shippingInputs = root.querySelectorAll('.shipping-method-input');
    const discount = parseInt(root.dataset.discount || '0', 10);
    const tax = parseInt(root.dataset.tax || '0', 10);
    const currency = root.dataset.currency || '';

    const formatTotal = (amount) => `${(amount / 100).toFixed(2)} ${currency}`.trim();

    const updateCheckoutTotal = () => {
        if (!subtotalEl || !totalEl) {
            return;
        }

        const subtotal = parseInt(subtotalEl.dataset.amount || '0', 10);
        let shipping = 0;

        shippingInputs.forEach((input) => {
            if (input.checked) {
                shipping = parseInt(input.dataset.price || '0', 10);
            }
        });

        if (shippingEl) {
            const freeLabel = shippingEl.dataset.free || '';
            shippingEl.textContent = shipping === 0 ? freeLabel : (shipping / 100).toFixed(2);
        }

        const total = formatTotal(subtotal - discount + tax + shipping);
        totalEl.textContent = total;

        if (totalMobileEl) {
            totalMobileEl.textContent = total;
        }

        if (toggleTotal) {
            toggleTotal.textContent = total;
        }
    };

    shippingInputs.forEach((input) => input.addEventListener('change', updateCheckoutTotal));
    updateCheckoutTotal();
};

const saveDraftThenGo = async (url, draftUrl, form) => {
    if (!form || !draftUrl) {
        window.location.href = url;
        return;
    }

    const data = new FormData(form);
    data.append('_token', csrfToken());

    try {
        await fetch(draftUrl, {
            method: 'POST',
            body: data,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });
    } catch {
        // Continue to auth even if the draft save fails; cart is session-backed.
    }

    window.location.href = url;
};

const initAuthLinks = (root) => {
    const form = root.querySelector('[data-checkout-form]');
    const draftUrl = root.dataset.draftUrl;

    root.querySelectorAll('[data-checkout-auth]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            saveDraftThenGo(link.href, draftUrl, form);
        });
    });
};

const initSummaryToggle = (root) => {
    const summary = root.querySelector('[data-checkout-summary]');
    const toggle = root.querySelector('[data-summary-toggle]');

    if (!summary || !toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const open = !summary.classList.contains('is-open');
        summary.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
};

const initContactPrefill = (root) => {
    const nameInput = root.querySelector('[data-contact-name]');
    const phoneInput = root.querySelector('[data-contact-phone]');
    const sync = (source, field) => {
        const shipping = root.querySelector(`[data-address-prefix="shipping_address"][data-address-field="${field}"]`);

        if (shipping && !shipping.value) {
            shipping.value = source.value;
        }
    };

    nameInput?.addEventListener('blur', () => sync(nameInput, 'recipient_name'));
    phoneInput?.addEventListener('blur', () => sync(phoneInput, 'phone'));
};

document.querySelectorAll('[data-checkout]').forEach((root) => {
    initAddressCards(root);
    initBillingToggle();
    initTotals(root);
    initAuthLinks(root);
    initSummaryToggle(root);
    initContactPrefill(root);
});
