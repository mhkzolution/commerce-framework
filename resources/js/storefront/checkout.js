/**
 * Storefront checkout — totals, sections, mobile summary.
 */

import { formatMoneyMinor } from './money.js';

function formatMoney(amountMinor, currency) {
    return formatMoneyMinor(amountMinor, currency);
}

function updateTotals(root) {
    const currency = root.dataset.currency || 'THB';
    const subtotal = Number(root.dataset.subtotal || 0);
    const discount = Number(root.dataset.discount || 0);
    const tax = Number(root.dataset.tax || 0);
    let shipping = Number(root.dataset.shipping || 0);

    root.querySelectorAll('[data-checkout-shipping-method], .shipping-method-input').forEach((input) => {
        if (input.checked) {
            shipping = Number(input.dataset.price || 0);
        }
    });

    root.dataset.shipping = String(shipping);

    const total = Math.max(0, subtotal - discount + tax + shipping);
    const shippingFreeLabel = root.dataset.shippingFreeLabel || 'Free';

    const shippingEl = root.querySelector('[data-checkout-shipping-amount]');
    if (shippingEl) {
        shippingEl.textContent = shipping === 0 ? shippingFreeLabel : formatMoney(shipping, currency);
    }

    const totalEls = root.querySelectorAll('[data-cart-summary-total], [data-checkout-footer-total] .storefront-product-card__price, [data-checkout-summary-toggle-total] .storefront-product-card__price');
    totalEls.forEach((el) => {
        const priceEl = el.classList?.contains('storefront-product-card__price') ? el : el.querySelector('.storefront-product-card__price');
        if (priceEl) {
            priceEl.textContent = formatMoney(total, currency);
        }
    });
}

function initBillingToggle(root) {
    const sameCheckbox = root.querySelector('[data-checkout-billing-same]');
    if (!sameCheckbox) {
        return;
    }

    const billingFields = root.querySelector('#billing-address-fields');
    const billingPicker = root.querySelector('[data-checkout-billing-picker]');

    const sync = () => {
        const hidden = sameCheckbox.checked;
        billingFields?.classList.toggle('hidden', hidden);
        billingPicker?.classList.toggle('hidden', hidden);
    };

    sameCheckbox.addEventListener('change', sync);
    sync();
}

function initSavedAddressToggle(root) {
    const manualPanel = root.querySelector('[data-checkout-manual-shipping]');
    const toggleBtn = root.querySelector('[data-checkout-address-toggle="manual-shipping"]');
    const savedInputs = root.querySelectorAll('[data-checkout-saved-address]');

    if (!manualPanel || savedInputs.length === 0) {
        return;
    }

    const syncManualRequired = (showManual) => {
        manualPanel.querySelectorAll('input, select, textarea').forEach((field) => {
            if (field.dataset.addressDistrict || field.dataset.addressSubdistrict) {
                field.required = showManual;
                return;
            }

            if (field.name?.includes('[line1]') || field.name?.includes('[postal_code]') || field.name?.includes('[country_code]')) {
                field.required = showManual;
            }

            if (field.dataset.addressSubdistrict !== undefined) {
                field.required = showManual;
            }
        });
    };

    const setManualVisible = (visible) => {
        manualPanel.hidden = !visible;
        toggleBtn?.setAttribute('aria-expanded', visible ? 'true' : 'false');
        syncManualRequired(visible);
    };

    toggleBtn?.addEventListener('click', () => {
        setManualVisible(manualPanel.hidden);
        if (!manualPanel.hidden) {
            savedInputs.forEach((input) => {
                input.checked = false;
            });
        }
    });

    savedInputs.forEach((input) => {
        input.addEventListener('change', () => {
            if (input.checked) {
                setManualVisible(false);
            }
        });
    });

    const hasSavedSelected = Array.from(savedInputs).some((input) => input.checked);
    setManualVisible(!hasSavedSelected || !manualPanel.hidden);
}

function initAddressCityMerge(root) {
    const form = root.querySelector('[data-checkout-form]');
    if (!form) {
        return;
    }

    form.addEventListener('submit', () => {
        form.querySelectorAll('[data-checkout-address-fields]').forEach((group) => {
            const prefix = group.dataset.addressPrefix;
            if (!prefix) {
                return;
            }

            const district = group.querySelector('[data-address-district]')?.value?.trim() || '';
            const subdistrict = group.querySelector('[data-address-subdistrict]')?.value?.trim() || '';
            const cityInput = group.querySelector('[data-address-city]');

            if (!cityInput) {
                return;
            }

            cityInput.value = [district, subdistrict].filter(Boolean).join(', ') || subdistrict;
        });
    });
}

function initShippingMethods(root) {
    root.querySelectorAll('[data-checkout-shipping-method], .shipping-method-input').forEach((input) => {
        input.addEventListener('change', () => updateTotals(root));
    });
    updateTotals(root);
}

function initSummaryToggle(root) {
    const toggle = root.querySelector('[data-checkout-summary-toggle]');
    const panel = root.querySelector('[data-checkout-summary-panel]');
    if (!toggle || !panel) {
        return;
    }

    toggle.addEventListener('click', () => {
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        panel.classList.toggle('storefront-checkout-summary__panel--open', !expanded);
    });
}

function initFooterHeight(root) {
    const footer = root.querySelector('[data-checkout-footer]');
    if (!footer) {
        return;
    }

    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    const sync = () => {
        if (desktopQuery.matches) {
            document.documentElement.style.removeProperty('--checkout-footer-height');
            return;
        }

        document.documentElement.style.setProperty('--checkout-footer-height', `${footer.offsetHeight}px`);
    };

    sync();
    window.addEventListener('resize', sync);
    desktopQuery.addEventListener('change', sync);

    if ('ResizeObserver' in window) {
        new ResizeObserver(sync).observe(footer);
    }
}

function initCheckout() {
    const root = document.querySelector('[data-checkout]');
    if (!root) {
        return;
    }

    initBillingToggle(root);
    initSavedAddressToggle(root);
    initAddressCityMerge(root);
    initShippingMethods(root);
    initSummaryToggle(root);
    initFooterHeight(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCheckout);
} else {
    initCheckout();
}
