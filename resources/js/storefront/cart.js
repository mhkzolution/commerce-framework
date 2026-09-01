import { formatMoneyMajor, formatMoneyMinor } from './money.js';
import { initWishlistScope, syncWishlistButtons } from './wishlist.js';

const RECENT_KEY = 'commerce:recently-viewed';

function readRecentlyViewed() {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
    } catch {
        return [];
    }
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function renderRecentlyViewedCard(item) {
    return `
        <article class="storefront-product-card" data-product-card>
            <div class="storefront-product-card__media">
                <a href="${escapeHtml(item.url)}" class="storefront-product-card__media-link" aria-label="${escapeHtml(item.name)}">
                    ${item.image
                        ? `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="storefront-product-card__image storefront-product-card__image--primary" loading="lazy">`
                        : '<div class="storefront-product-card__placeholder"></div>'}
                </a>
            </div>
            <div class="storefront-product-card__body">
                <a href="${escapeHtml(item.url)}" class="storefront-product-card__name">${escapeHtml(item.name)}</a>
                <div class="storefront-product-card__meta">
                    <span class="storefront-product-card__price">${formatMoneyMajor(item.price, item.currency, { decimals: 0 })}</span>
                </div>
            </div>
        </article>`;
}

function renderRecentlyViewed(root) {
    const section = root.querySelector('[data-recently-viewed-section]');
    const grid = section?.querySelector('[data-recently-viewed-grid]');

    if (!section || !grid) {
        return;
    }

    const cartProductUuids = new Set(
        [...root.querySelectorAll('[data-cart-line][data-product-uuid]')]
            .map((line) => line.dataset.productUuid)
            .filter(Boolean),
    );

    const items = readRecentlyViewed().filter((entry) => !cartProductUuids.has(entry.uuid));

    if (items.length === 0) {
        section.hidden = true;
        return;
    }

    grid.innerHTML = items.map((item) => renderRecentlyViewedCard(item)).join('');
    section.hidden = false;
}

function formatMoney(amountMinor, currency) {
    return formatMoneyMinor(amountMinor, currency);
}

function getSelectedLines(root) {
    return [...root.querySelectorAll('[data-cart-line-select]:checked')].map((input) => input.closest('[data-cart-line]')).filter(Boolean);
}

function syncSelectionForms(root) {
    const selected = getSelectedLines(root);
    const uuids = selected.map((line) => line.dataset.purchasableUuid).filter(Boolean);
    const hiddenInputs = uuids.map((uuid) => `<input type="hidden" name="items[]" value="${escapeHtml(uuid)}">`).join('');

    root.querySelectorAll('[data-cart-checkout-items]').forEach((container) => {
        container.innerHTML = hiddenInputs;
    });

    const deleteContainer = root.querySelector('[data-cart-delete-items]');
    if (deleteContainer) {
        deleteContainer.innerHTML = hiddenInputs;
    }

    const hasSelection = uuids.length > 0;
    const lineChecks = [...root.querySelectorAll('[data-cart-line-select]')];
    const allChecked = lineChecks.length > 0 && lineChecks.every((input) => input.checked);

    root.querySelectorAll('[data-cart-delete-selected]').forEach((button) => {
        button.disabled = !hasSelection;
    });

    root.querySelectorAll('[data-cart-checkout-button]').forEach((button) => {
        button.disabled = !hasSelection;
    });

    root.querySelectorAll('[data-cart-selected-count]').forEach((badge) => {
        badge.textContent = hasSelection ? `(${uuids.length})` : '';
    });

    root.querySelectorAll('[data-cart-select-all]').forEach((input) => {
        input.checked = allChecked;
        input.indeterminate = hasSelection && !allChecked;
    });
}

function updateSummaryTotals(root) {
    const selected = getSelectedLines(root);
    const currency = root.dataset.currency || '';
    const cheapestShipping = Number(root.dataset.cheapestShipping || 0);

    let subtotal = 0;
    let itemCount = 0;

    selected.forEach((line) => {
        subtotal += Number(line.dataset.lineTotal || 0);
        itemCount += Number(line.dataset.quantity || 0);
    });

    const grandTotal = subtotal + (selected.length > 0 ? cheapestShipping : 0);
    const formattedTotal = formatMoney(grandTotal, currency);
    const totalHtml = `<span class="storefront-price storefront-product-card__price">${formattedTotal}</span>`;

    const subtotalEl = root.querySelector('[data-cart-summary-subtotal]');
    const countEl = root.querySelector('[data-cart-summary-count]');

    if (subtotalEl) {
        subtotalEl.innerHTML = `
            <span class="storefront-price storefront-product-card__price">${formatMoney(subtotal, currency)}</span>
            <span class="storefront-cart-summary__meta" data-cart-summary-count">${itemCount} item${itemCount === 1 ? '' : 's'}</span>`;
    }

    root.querySelectorAll('[data-cart-summary-total], [data-cart-dock-total]').forEach((element) => {
        element.innerHTML = totalHtml;
    });

    if (countEl && !subtotalEl) {
        countEl.textContent = `${itemCount} item${itemCount === 1 ? '' : 's'}`;
    }
}

function initSelection(root) {
    const deleteForm = root.querySelector('[data-cart-delete-form]');

    root.addEventListener('change', (event) => {
        const target = event.target;

        if (target.matches('[data-cart-select-all]')) {
            root.querySelectorAll('[data-cart-line-select]').forEach((input) => {
                input.checked = target.checked;
            });
            root.querySelectorAll('[data-cart-select-all]').forEach((input) => {
                if (input !== target) {
                    input.checked = target.checked;
                    input.indeterminate = false;
                }
            });
        }

        if (target.matches('[data-cart-line-select], [data-cart-select-all]')) {
            syncSelectionForms(root);
            updateSummaryTotals(root);
        }
    });

    deleteForm?.addEventListener('submit', (event) => {
        if (getSelectedLines(root).length === 0) {
            event.preventDefault();
        }
    });

    root.querySelectorAll('[data-cart-checkout-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (getSelectedLines(root).length === 0) {
                event.preventDefault();
            }
        });
    });

    syncSelectionForms(root);
    updateSummaryTotals(root);
}

function initCouponToggle(root) {
    const toggle = root.querySelector('[data-cart-coupon-toggle]');
    const panel = root.querySelector('[data-cart-coupon-panel]');

    if (!toggle || !panel) {
        return;
    }

    const sync = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle.addEventListener('click', () => {
        sync(panel.hidden);
    });

    if (!panel.hidden) {
        toggle.setAttribute('aria-expanded', 'true');
    }
}

function initQuantitySteppers(root) {
    root.querySelectorAll('[data-qty-form]').forEach((form) => {
        const input = form.querySelector('[data-qty-input]');
        const decrease = form.querySelector('[data-qty-decrease]');
        const increase = form.querySelector('[data-qty-increase]');

        if (!input) {
            return;
        }

        const submit = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        decrease?.addEventListener('click', () => {
            const value = Math.max(1, Number(input.value || 1) - 1);
            input.value = String(value);
            submit();
        });

        increase?.addEventListener('click', () => {
            const max = input.max ? Number(input.max) : null;
            let value = Number(input.value || 1) + 1;
            if (max !== null && value > max) {
                value = max;
            }
            input.value = String(value);
            submit();
        });

        input.addEventListener('change', submit);
    });
}

function initDockHeight(root) {
    const dock = root.querySelector('[data-cart-dock]');
    if (!dock) {
        return;
    }

    const desktopQuery = window.matchMedia('(min-width: 1024px)');

    const sync = () => {
        if (desktopQuery.matches) {
            document.documentElement.style.removeProperty('--cart-dock-height');
            return;
        }

        document.documentElement.style.setProperty('--cart-dock-height', `${dock.offsetHeight}px`);
    };

    sync();
    window.addEventListener('resize', sync);
    desktopQuery.addEventListener('change', sync);

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(sync);
        observer.observe(dock);
    }
}

function initCart() {
    const root = document.querySelector('[data-cart]');
    if (!root) {
        return;
    }

    initQuantitySteppers(root);
    initWishlistScope(root);
    initSelection(root);
    initCouponToggle(root);
    renderRecentlyViewed(root);
    initDockHeight(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCart);
} else {
    initCart();
}

window.addEventListener('commerce:wishlist-changed', () => {
    const root = document.querySelector('[data-cart]');
    if (root) {
        syncWishlistButtons(root);
    }
});
