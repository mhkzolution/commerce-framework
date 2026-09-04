import { initWishlistScope, syncWishlistButtons } from './wishlist.js';

function readJson(node, key, fallback) {
    try {
        return JSON.parse(node.dataset[key] || '') || fallback;
    } catch {
        return fallback;
    }
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function lockBody(lock) {
    document.body.classList.toggle('storefront-drawer-open', lock);
    document.body.classList.toggle('cx-quick-view-open', lock);
}

function show(path, config) {
    return Boolean(config?.[path]);
}

function renderQuickView(product, config, i18n) {
    const price = product.sale_price && config.showSalePrice ? product.sale_price : product.price;
    const images = (product.images || []).filter(Boolean);
    const imageHtml = show('showImages', config) && images.length
        ? `<div class="cx-store-qv__images">${images.map((src, index) => `<img src="${escapeHtml(src)}" alt="${escapeHtml(product.name)}" ${index === 0 ? '' : 'loading="lazy"'}>`).join('')}</div>`
        : '';

    const badge = show('showPromotionBadge', config) && product.promotion_badge
        ? `<span class="cx-store-qv__badge">${escapeHtml(product.promotion_badge)}</span>`
        : '';
    const name = show('showName', config) ? `<h2 id="cx-quick-view-title" class="cx-store-qv__name">${escapeHtml(product.name)}</h2>` : '<h2 id="cx-quick-view-title" class="sr-only">Quick view</h2>';
    const priceHtml = show('showPrice', config)
        ? `<div class="cx-store-qv__price">
                <strong>${escapeHtml(product.formatted_sale_price || product.formatted_price)}</strong>
                ${show('showSalePrice', config) && product.formatted_sale_price ? `<s>${escapeHtml(product.formatted_price)}</s>` : ''}
           </div>`
        : '';
    const shortDesc = show('showShortDescription', config) && product.short_description
        ? `<p class="cx-store-qv__desc">${escapeHtml(product.short_description)}</p>`
        : '';
    const fullDesc = show('showFullDescription', config) && product.description
        ? `<p class="cx-store-qv__desc">${escapeHtml(product.description)}</p>`
        : '';
    const meta = [
        show('showBrand', config) ? product.brand : null,
        show('showCategory', config) ? product.category : null,
        show('showSku', config) && product.sku ? `SKU ${product.sku}` : null,
    ].filter(Boolean);
    const tags = show('showTags', config) && Array.isArray(product.tags)
        ? `<div class="cx-store-qv__tags">${product.tags.map((tag) => `<span>${escapeHtml(tag)}</span>`).join('')}</div>`
        : '';
    const stockBits = [];
    if (show('showStockStatus', config)) {
        stockBits.push(product.in_stock ? i18n.inStock : i18n.outOfStock);
    }
    if (show('showRemainingStock', config) && product.in_stock) {
        stockBits.push(i18n.remaining.replace(':count', String(product.remaining_stock)));
    }
    const variants = show('showVariants', config) && Array.isArray(product.variants) && product.variants.length > 1
        ? `<div class="cx-store-qv__variants" data-qv-variants>
                ${product.variants.map((variant) => `
                    <button type="button" class="cx-store-qv__chip${variant.uuid === product.default_variant_uuid ? ' is-active' : ''}" data-qv-variant="${escapeHtml(variant.uuid)}" data-available="${variant.available ?? 0}">
                        ${escapeHtml(variant.name)}
                    </button>
                `).join('')}
           </div>`
        : '';
    const wishlist = show('showWishlist', config)
        ? `<button type="button" class="storefront-wishlist-btn" data-wishlist-toggle data-product-uuid="${escapeHtml(product.uuid)}" ${product.default_variant_uuid ? `data-variant-uuid="${escapeHtml(product.default_variant_uuid)}"` : ''} aria-label="Wishlist">♡</button>`
        : '';
    const detail = show('showViewFullDetail', config)
        ? `<a class="cx-store-qv__detail" href="${escapeHtml(product.url)}">${escapeHtml(i18n.viewFullDetails)}</a>`
        : '';

    const qty = show('showQuantitySelector', config)
        ? `<div class="cx-store-qv__qty" data-qv-qty>
                <button type="button" data-qv-qty-step="-1" aria-label="${escapeHtml(i18n.decrease)}">−</button>
                <input type="number" min="1" value="1" data-qv-qty-input aria-label="${escapeHtml(i18n.quantity)}">
                <button type="button" data-qv-qty-step="1" aria-label="${escapeHtml(i18n.increase)}">+</button>
           </div>`
        : `<input type="hidden" value="1" data-qv-qty-input>`;

    const actions = `
        <form method="POST" action="" data-qv-form>
            <input type="hidden" name="_token" value="${escapeHtml(csrfToken())}">
            <input type="hidden" name="purchasable_uuid" value="${escapeHtml(product.default_variant_uuid || '')}" data-qv-variant-input>
            <input type="hidden" name="quantity" value="1" data-qv-qty-hidden>
            ${qty}
            <div class="cx-store-qv__actions">
                ${show('showAddToCart', config) ? `<button type="submit" class="cx-store-qv__btn cx-store-qv__btn--cart">${escapeHtml(i18n.addToCart)}</button>` : ''}
                ${show('showBuyNow', config) ? `<button type="submit" class="cx-store-qv__btn cx-store-qv__btn--buy" name="redirect_to" value="checkout">${escapeHtml(i18n.buyNow)}</button>` : ''}
            </div>
        </form>`;

    return {
        body: `
            ${imageHtml}
            <div class="cx-store-qv__content">
                ${badge}
                ${name}
                ${priceHtml}
                ${shortDesc}
                ${fullDesc}
                ${meta.length ? `<p class="cx-store-qv__meta">${escapeHtml(meta.join(' · '))}</p>` : ''}
                ${tags}
                ${stockBits.length ? `<p class="cx-store-qv__stock">${escapeHtml(stockBits.join(' · '))}</p>` : ''}
                ${variants}
                ${wishlist}
                ${detail}
            </div>`,
        sticky: product.in_stock ? actions : `<p class="cx-store-qv__unavailable">${escapeHtml(i18n.unavailable)}</p>`,
    };
}

function initQuickView() {
    const root = document.querySelector('[data-quick-view]');
    if (!root) {
        return;
    }

    const config = readJson(root, 'quickViewConfig', {});
    const i18n = readJson(root, 'i18n', {});
    const endpointBase = root.dataset.quickViewUrl || '/api/v1/storefront/products';
    const cartUrl = root.dataset.cartUrl || '/cart/items';
    const body = root.querySelector('[data-quick-view-body]');
    const sticky = root.querySelector('[data-quick-view-sticky]');
    const cache = new Map();

    const close = () => {
        root.hidden = true;
        root.classList.remove('is-open');
        lockBody(false);
    };

    const bindPanel = (product) => {
        sticky.querySelector('[data-qv-form]')?.setAttribute('action', cartUrl);

        sticky.querySelectorAll('[data-qv-qty-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = sticky.querySelector('[data-qv-qty-input]');
                const hidden = sticky.querySelector('[data-qv-qty-hidden]');
                const next = Math.max(1, Number(input?.value || 1) + Number(button.dataset.qvQtyStep));
                if (input) {
                    input.value = String(next);
                }
                if (hidden) {
                    hidden.value = String(next);
                }
            });
        });

        sticky.querySelector('[data-qv-qty-input]')?.addEventListener('input', (event) => {
            const hidden = sticky.querySelector('[data-qv-qty-hidden]');
            if (hidden) {
                hidden.value = event.target.value;
            }
        });

        body.querySelectorAll('[data-qv-variant]').forEach((button) => {
            button.addEventListener('click', () => {
                body.querySelectorAll('[data-qv-variant]').forEach((node) => node.classList.toggle('is-active', node === button));
                const input = sticky.querySelector('[data-qv-variant-input]');
                if (input) {
                    input.value = button.dataset.qvVariant;
                }
            });
        });

        initWishlistScope(root);
        syncWishlistButtons(root);
    };

    const open = async (uuid) => {
        root.hidden = false;
        requestAnimationFrame(() => root.classList.add('is-open'));
        lockBody(true);
        body.innerHTML = '<div class="cx-quick-view__loading">…</div>';
        sticky.innerHTML = '';

        try {
            if (!cache.has(uuid)) {
                const response = await fetch(`${endpointBase}/${encodeURIComponent(uuid)}/quick-view`, {
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) {
                    throw new Error('Quick view failed');
                }
                const json = await response.json();
                cache.set(uuid, json.data);
            }

            const rendered = renderQuickView(cache.get(uuid), config, i18n);
            body.innerHTML = rendered.body;
            sticky.innerHTML = rendered.sticky;
            bindPanel(cache.get(uuid));
        } catch {
            body.innerHTML = `<p class="cx-store-qv__unavailable">${escapeHtml(i18n.unavailable || 'Unavailable')}</p>`;
        }
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-quick-view-open]');
        if (!trigger) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        open(trigger.dataset.quickViewOpen);
    });

    root.querySelectorAll('[data-quick-view-close]').forEach((node) => {
        node.addEventListener('click', close);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('is-open')) {
            close();
        }
    });
}

function initNotifications() {
    const host = document.querySelector('[data-notification-host]');
    if (!host) {
        return;
    }

    const config = readJson(host, 'notificationConfig', {});
    if (!config.enabled) {
        return;
    }

    const duration = Number(config.duration || 5) * 1000;
    const url = host.dataset.notificationUrl;
    const seenKey = 'cx:notifications:seen';

    const readSeen = () => {
        try {
            const parsed = JSON.parse(sessionStorage.getItem(seenKey) || '[]');

            return new Set(Array.isArray(parsed) ? parsed.map(String) : []);
        } catch {
            return new Set();
        }
    };

    const rememberSeen = (id) => {
        const seen = readSeen();
        seen.add(id);
        sessionStorage.setItem(seenKey, JSON.stringify([...seen]));
    };

    const itemId = (item) => String(item.id || `${item.type || 'item'}:${item.url || item.title || ''}`);

    const showToast = (item) => {
        const toast = document.createElement('div');
        toast.className = `cx-store-toast cx-store-toast--${config.position || 'bottom-right'}`;
        toast.innerHTML = `
            <div class="cx-store-toast__eyebrow">${escapeHtml(item.eyebrow)}</div>
            <div class="cx-store-toast__title">${escapeHtml(item.title)}</div>
            ${item.body ? `<div class="cx-store-toast__body">${escapeHtml(item.body)}</div>` : ''}
            ${item.action && item.url ? `<a class="cx-store-toast__action" href="${escapeHtml(item.url)}">${escapeHtml(item.action)}</a>` : ''}
            <div class="cx-store-toast__timer"><span style="animation-duration:${duration}ms"></span></div>
        `;
        host.replaceChildren(toast);
        window.setTimeout(() => toast.classList.add('is-gone'), duration);
        window.setTimeout(() => toast.remove(), duration + 280);
    };

    const play = (items, index) => {
        if (index >= items.length) {
            return;
        }

        const item = items[index];
        rememberSeen(itemId(item));
        showToast(item);
        window.setTimeout(() => play(items, index + 1), duration + 4000);
    };

    fetch(url, { headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? response.json() : null))
        .then((json) => {
            const seen = readSeen();
            const items = (json?.data || []).filter((item) => !seen.has(itemId(item)));
            if (!items.length) {
                return;
            }

            play(items, 0);
        })
        .catch(() => {});
}

function initBackToTop() {
    const button = document.querySelector('[data-back-to-top]');
    if (!button) {
        return;
    }

    const threshold = Number(button.dataset.showAfter || 500);
    const smooth = button.dataset.smooth === '1';
    const fade = button.dataset.fade === '1';
    if (fade) {
        button.classList.add('cx-back-to-top--fade');
    }

    const toggle = () => {
        const y = window.scrollY || document.documentElement.scrollTop;
        const screen = window.innerHeight || 0;
        button.hidden = !(y > threshold || y > screen);
    };

    window.addEventListener('scroll', toggle, { passive: true });
    toggle();

    button.addEventListener('click', () => {
        const target = button.dataset.target;
        let top = 0;

        if (target === 'filter') {
            const filter = document.querySelector('[data-shop-filters], .storefront-filters');
            top = filter ? filter.getBoundingClientRect().top + window.scrollY - 16 : 0;
        } else if (target === 'category') {
            const category = document.querySelector('.storefront-category-nav, [data-category-nav]');
            top = category ? category.getBoundingClientRect().top + window.scrollY - 16 : 0;
        }

        window.scrollTo({ top, behavior: smooth ? 'smooth' : 'auto' });
    });
}

export function initCustomerExperience() {
    initQuickView();
    initNotifications();
    initBackToTop();
}
