import { formatMoneyMinor } from './money.js';

const STORAGE_KEY = 'commerce:wishlist';

let wishlistProductIds = new Set();
let wishlistCount = 0;
let initialized = false;

function configRoot() {
    return document.querySelector('[data-wishlist-root]');
}

function storageKey() {
    return configRoot()?.dataset.storageKey || STORAGE_KEY;
}

function isAuthenticated() {
    return configRoot()?.dataset.authenticated === '1';
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function normalizeGuestItems(raw) {
    if (!Array.isArray(raw)) {
        return [];
    }

    if (raw.every((entry) => typeof entry === 'string')) {
        return raw
            .filter((entry) => entry.trim() !== '')
            .map((productId) => ({ product_id: productId, variant_id: null }));
    }

    return raw
        .map((entry) => {
            if (!entry || typeof entry !== 'object') {
                return null;
            }

            const productId = String(entry.product_id ?? '').trim();

            if (productId === '') {
                return null;
            }

            const variantId = entry.variant_id ? String(entry.variant_id).trim() : null;

            return {
                product_id: productId,
                variant_id: variantId || null,
            };
        })
        .filter(Boolean);
}

export function readGuestItems() {
    try {
        return normalizeGuestItems(JSON.parse(localStorage.getItem(storageKey()) || '[]'));
    } catch {
        return [];
    }
}

export function writeGuestItems(items) {
    localStorage.setItem(storageKey(), JSON.stringify(items));
    applyLocalState(items);
    dispatchChanged();
}

export function clearGuestItems() {
    localStorage.removeItem(storageKey());
}

function applyLocalState(items) {
    wishlistProductIds = new Set(items.map((item) => item.product_id));
    wishlistCount = items.length;
}

function applyPayload(payload) {
    const items = Array.isArray(payload?.items) ? payload.items : [];
    wishlistProductIds = new Set(items.map((item) => item.product_id));
    wishlistCount = Number(payload?.count ?? items.length);
}

async function apiFetch(url, options = {}) {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
            ...(options.headers ?? {}),
        },
        ...options,
    });

    const body = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new Error(body?.error?.message ?? 'Wishlist request failed.');
    }

    return body;
}

async function fetchAuthenticatedWishlist() {
    const root = configRoot();
    const url = root?.dataset.wishlistIndexUrl;

    if (!url) {
        return { count: 0, items: [] };
    }

    const body = await apiFetch(url);
    applyPayload(body.data);

    return body.data;
}

async function fetchGuestPreview() {
    const root = configRoot();
    const url = root?.dataset.wishlistPreviewUrl;
    const items = readGuestItems();

    if (!url || items.length === 0) {
        applyLocalState(items);

        return { count: items.length, items: [] };
    }

    const body = await apiFetch(url, {
        method: 'POST',
        body: JSON.stringify({ items }),
    });

    applyPayload({ count: body.data?.count ?? 0, items: body.data?.items ?? [] });

    return body.data;
}

export async function mergeGuestWishlistIfNeeded() {
    if (!isAuthenticated()) {
        return;
    }

    const root = configRoot();
    const mergeUrl = root?.dataset.wishlistMergeUrl;
    const guestItems = readGuestItems();

    if (!mergeUrl) {
        await fetchAuthenticatedWishlist();

        return;
    }

    if (guestItems.length === 0) {
        await fetchAuthenticatedWishlist();

        return;
    }

    const body = await apiFetch(mergeUrl, {
        method: 'POST',
        body: JSON.stringify({ items: guestItems }),
    });

    clearGuestItems();
    applyPayload(body.data);
    updateWishlistUi();
}

export async function refreshWishlistDrawer() {
    const drawer = document.querySelector('[data-drawer="wishlist"]');

    if (!drawer) {
        return;
    }

    const list = drawer.querySelector('[data-wishlist-list]');
    const loading = drawer.querySelector('[data-wishlist-loading]');

    if (loading) {
        loading.hidden = false;
    }

    try {
        const payload = isAuthenticated()
            ? await fetchAuthenticatedWishlist()
            : await fetchGuestPreview();

        renderWishlistDrawer(payload?.items ?? []);
    } catch {
        renderWishlistDrawer([]);
    } finally {
        if (loading) {
            loading.hidden = true;
        }

        updateWishlistUi();
    }
}

function renderWishlistDrawer(items) {
    const drawer = document.querySelector('[data-drawer="wishlist"]');
    const list = drawer?.querySelector('[data-wishlist-list]');

    if (!list) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        list.innerHTML = '';

        return;
    }

    const removeLabel = list.dataset.removeLabel || 'Remove';

    list.innerHTML = items.map((item) => {
        const image = item.image_url
            ? `<img src="${escapeHtml(item.image_url)}" alt="" class="storefront-drawer-line__image" loading="lazy" decoding="async"${item.image_srcset ? ` srcset="${escapeHtml(item.image_srcset)}" sizes="88px"` : ''}>`
            : `<div class="storefront-drawer-line__placeholder">${escapeHtml(list.dataset.noImageLabel || '')}</div>`;

        const variant = item.variant_label
            ? `<p class="storefront-drawer-line__meta">${escapeHtml(item.variant_label)}</p>`
            : '';

        const price = formatMoneyMinor(item.price, item.currency);

        return `
            <article class="storefront-drawer-line" data-wishlist-line data-product-uuid="${escapeHtml(item.product_id)}">
                <a href="${escapeHtml(item.url)}" class="storefront-drawer-line__image-link">
                    ${image}
                </a>
                <div class="storefront-drawer-line__content">
                    <a href="${escapeHtml(item.url)}" class="storefront-drawer-line__name">${escapeHtml(item.name)}</a>
                    ${variant}
                    <p class="storefront-drawer-line__price">${escapeHtml(price)}</p>
                </div>
                <button
                    type="button"
                    class="storefront-wishlist-drawer__remove"
                    data-wishlist-remove
                    data-product-uuid="${escapeHtml(item.product_id)}"
                    data-variant-uuid="${escapeHtml(item.variant_id ?? '')}"
                    aria-label="${escapeHtml(removeLabel)}"
                >×</button>
            </article>
        `;
    }).join('');
}

export function updateWishlistUi() {
    document.querySelectorAll('[data-wishlist-count]').forEach((element) => {
        element.textContent = String(wishlistCount);
        element.hidden = wishlistCount === 0;
    });

    document.querySelectorAll('[data-wishlist-empty]').forEach((element) => {
        element.hidden = wishlistCount > 0;
    });

    document.querySelectorAll('[data-wishlist-filled]').forEach((element) => {
        element.hidden = wishlistCount === 0;
    });
}

export function syncWishlistButtons(root = document) {
    root.querySelectorAll('[data-wishlist-toggle]').forEach((button) => {
        const productUuid = button.dataset.productUuid;
        const active = wishlistProductIds.has(productUuid);
        button.classList.toggle('storefront-wishlist-btn--active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

function renderWishlistPageItem(item, list) {
    const variantOptionsLabel = list?.dataset.variantOptionsLabel || 'Options';
    const addToCartLabel = list?.dataset.addToCartLabel || 'Add to cart';
    const removeLabel = list?.dataset.removeLabel || 'Remove';
    const noImageLabel = list?.dataset.noImageLabel || '';
    const cartStoreUrl = list?.dataset.cartStoreUrl || '/cart/items';

    const image = item.image_url
        ? `<img src="${escapeHtml(item.image_url)}" alt="" class="storefront-cart-line__image" loading="lazy" decoding="async"${item.image_srcset ? ` srcset="${escapeHtml(item.image_srcset)}" sizes="88px"` : ''}>`
        : `<div class="storefront-cart-line__placeholder">${escapeHtml(noImageLabel)}</div>`;

    const variant = item.variant_label
        ? `
            <a href="${escapeHtml(item.url)}" class="storefront-cart-line__variant-btn">
                <span class="storefront-cart-line__variant-text">${escapeHtml(variantOptionsLabel)}: ${escapeHtml(item.variant_label)}</span>
                <span class="storefront-cart-line__variant-chevron" aria-hidden="true">›</span>
            </a>
        `
        : '';

    const price = formatMoneyMinor(item.price, item.currency);

    const addToCart = item.variant_id
        ? `
            <form method="POST" action="${escapeHtml(cartStoreUrl)}" class="storefront-wishlist-line__add-form">
                <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
                <input type="hidden" name="purchasable_uuid" value="${escapeHtml(item.variant_id)}">
                <input type="hidden" name="quantity" value="1">
                <button type="submit" class="storefront-wishlist-line__add-btn">${escapeHtml(addToCartLabel)}</button>
            </form>
        `
        : '';

    return `
        <article class="storefront-cart-line storefront-cart-line--wishlist" data-wishlist-page-item data-product-uuid="${escapeHtml(item.product_id)}">
            <div class="storefront-cart-line__select storefront-cart-line__select--spacer" aria-hidden="true">
                <span class="storefront-cart-line__checkbox-spacer"></span>
            </div>
            <div class="storefront-cart-line__media">
                <a href="${escapeHtml(item.url)}" class="storefront-cart-line__media-link" aria-hidden="true" tabindex="-1">
                    ${image}
                </a>
            </div>
            <div class="storefront-cart-line__content">
                <h2 class="storefront-cart-line__title" title="${escapeHtml(item.name)}">
                    <a href="${escapeHtml(item.url)}">${escapeHtml(item.name)}</a>
                </h2>
                ${variant}
                <div class="storefront-cart-line__footer">
                    <div class="storefront-cart-line__price">
                        <span class="storefront-price storefront-product-card__price">${escapeHtml(price)}</span>
                    </div>
                    <div class="storefront-cart-line__qty storefront-wishlist-line__actions">
                        ${addToCart}
                        <button
                            type="button"
                            class="storefront-wishlist-line__remove-btn"
                            data-wishlist-page-remove
                            data-product-uuid="${escapeHtml(item.product_id)}"
                            data-variant-uuid="${escapeHtml(item.variant_id ?? '')}"
                        >${escapeHtml(removeLabel)}</button>
                    </div>
                </div>
            </div>
        </article>
    `;
}

export async function refreshWishlistPage() {
    const page = document.querySelector('[data-wishlist-page]');

    if (!page) {
        return;
    }

    const empty = page.querySelector('[data-wishlist-page-empty]');
    const content = page.querySelector('[data-wishlist-page-content]');
    const list = page.querySelector('[data-wishlist-page-list]');
    const loading = page.querySelector('[data-wishlist-page-loading]');

    if (loading) {
        loading.hidden = false;
    }

    try {
        const payload = isAuthenticated()
            ? await fetchAuthenticatedWishlist()
            : await fetchGuestPreview();

        const items = Array.isArray(payload?.items) ? payload.items : [];

        if (!list) {
            return;
        }

        if (items.length === 0) {
            list.innerHTML = '';
            content?.setAttribute('hidden', '');
            empty?.removeAttribute('hidden');
        } else {
            list.innerHTML = items.map((item) => renderWishlistPageItem(item, list)).join('');
            content?.removeAttribute('hidden');
            empty?.setAttribute('hidden', '');
        }

        updateWishlistUi();
    } catch {
        list.innerHTML = '';
        content?.setAttribute('hidden', '');
        empty?.removeAttribute('hidden');
    } finally {
        if (loading) {
            loading.hidden = true;
        }
    }
}

function dispatchChanged() {
    window.dispatchEvent(new CustomEvent('commerce:wishlist-changed'));
}

export async function toggleWishlist(productUuid, variantUuid = null) {
    if (!productUuid) {
        return;
    }

    const variantId = variantUuid && variantUuid !== '' ? variantUuid : null;

    if (isAuthenticated()) {
        const root = configRoot();
        const isActive = wishlistProductIds.has(productUuid);

        if (isActive) {
            const body = await apiFetch(root.dataset.wishlistDestroyUrl, {
                method: 'DELETE',
                body: JSON.stringify({
                    product_id: productUuid,
                    variant_id: variantId,
                }),
            });
            applyPayload(body.data);
        } else {
            const body = await apiFetch(root.dataset.wishlistStoreUrl, {
                method: 'POST',
                body: JSON.stringify({
                    product_id: productUuid,
                    variant_id: variantId,
                }),
            });
            applyPayload(body.data);
        }
    } else {
        const items = readGuestItems();
        const index = items.findIndex((item) => item.product_id === productUuid);

        if (index >= 0) {
            items.splice(index, 1);
        } else {
            items.push({ product_id: productUuid, variant_id: variantId });
        }

        writeGuestItems(items);
    }

    updateWishlistUi();
    syncWishlistButtons();
    dispatchChanged();
}

function bindWishlistInteractions() {
    document.addEventListener('click', async (event) => {
        const toggle = event.target.closest('[data-wishlist-toggle]');

        if (toggle) {
            event.preventDefault();

            const variantUuid = toggle.dataset.variantUuid || null;

            try {
                await toggleWishlist(toggle.dataset.productUuid, variantUuid);
                syncWishlistButtons(toggle.closest('[data-shop]') || toggle.closest('[data-product-page]') || document);
            } catch {
                // Keep UI stable if the network request fails.
            }

            return;
        }

        const removeButton = event.target.closest('[data-wishlist-remove], [data-wishlist-page-remove]');

        if (removeButton) {
            event.preventDefault();

            try {
                await toggleWishlist(removeButton.dataset.productUuid, removeButton.dataset.variantUuid || null);
                await refreshWishlistDrawer();
                await refreshWishlistPage();
            } catch {
                // Ignore remove failures for now.
            }
        }

        const openDrawer = event.target.closest('[data-drawer-open="wishlist"]');

        if (openDrawer) {
            window.setTimeout(() => {
                refreshWishlistDrawer();
            }, 0);
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key === storageKey() && !isAuthenticated()) {
            applyLocalState(readGuestItems());
            updateWishlistUi();
            syncWishlistButtons();
        }
    });

    window.addEventListener('commerce:wishlist-changed', () => {
        updateWishlistUi();
        syncWishlistButtons();
        refreshWishlistPage();
    });
}

export async function initWishlist() {
    if (initialized) {
        return;
    }

    initialized = true;
    bindWishlistInteractions();

    if (isAuthenticated()) {
        await mergeGuestWishlistIfNeeded();
    } else {
        applyLocalState(readGuestItems());
    }

    updateWishlistUi();
    syncWishlistButtons();
    await refreshWishlistPage();
}

export function initWishlistScope(root) {
    syncWishlistButtons(root);
}
