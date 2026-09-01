function get(object, path) {
    return path.split('.').reduce((value, key) => (value == null ? undefined : value[key]), object);
}

function set(object, path, value) {
    const keys = path.split('.');
    let cursor = object;

    keys.forEach((key, index) => {
        if (index === keys.length - 1) {
            cursor[key] = value;
            return;
        }

        if (typeof cursor[key] !== 'object' || cursor[key] === null) {
            cursor[key] = {};
        }

        cursor = cursor[key];
    });
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function formatMoney(amount) {
    return `฿${Number(amount).toLocaleString('en-US')}`;
}

function renderQuickView(config, product, i18n, device) {
    const qv = config.quickView;
    const price = product.sale_price && qv.showSalePrice ? product.sale_price : product.price;
    const showCompare = qv.showSalePrice && product.sale_price && product.compare_at_price;

    if (!qv.enabled) {
        return `<div class="cx-preview-empty">Quick View is off</div>`;
    }

    const images = qv.showImages
        ? `<div class="cx-qv__images"><div class="cx-qv__image"></div></div>`
        : '';

    const badge = qv.showPromotionBadge && product.promotion_badge
        ? `<span class="cx-qv__badge">${escapeHtml(product.promotion_badge)}</span>`
        : '';

    const name = qv.showName ? `<h3 class="cx-qv__name">${escapeHtml(product.name)}</h3>` : '';
    const priceRow = qv.showPrice
        ? `<div class="cx-qv__price">
                <strong>${escapeHtml(formatMoney(price))}</strong>
                ${showCompare ? `<s>${escapeHtml(formatMoney(product.compare_at_price))}</s>` : ''}
           </div>`
        : '';

    const shortDesc = qv.showShortDescription && product.short_description
        ? `<p class="cx-qv__desc">${escapeHtml(product.short_description)}</p>`
        : '';
    const fullDesc = qv.showFullDescription && product.description
        ? `<p class="cx-qv__desc cx-qv__desc--full">${escapeHtml(product.description)}</p>`
        : '';

    const meta = [
        qv.showBrand && product.brand ? product.brand : null,
        qv.showCategory && product.category ? product.category : null,
        qv.showSku && product.sku ? `SKU ${product.sku}` : null,
    ].filter(Boolean);

    const tags = qv.showTags && Array.isArray(product.tags)
        ? product.tags.map((tag) => `<span class="cx-qv__tag">${escapeHtml(tag)}</span>`).join('')
        : '';

    const stock = qv.showStockStatus
        ? `<p class="cx-qv__stock">${escapeHtml(i18n.inStock)}${qv.showRemainingStock ? ` · ${escapeHtml(i18n.left.replace(':count', String(product.remaining_stock)))}` : ''}</p>`
        : (qv.showRemainingStock ? `<p class="cx-qv__stock">${escapeHtml(i18n.left.replace(':count', String(product.remaining_stock)))}</p>` : '');

    const variants = qv.showVariants
        ? `<div class="cx-qv__variants">${(product.variants || []).map((variant) => `<button type="button" class="cx-qv__chip">${escapeHtml(variant.name)}</button>`).join('')}</div>`
        : '';

    const wishlist = qv.showWishlist ? `<button type="button" class="cx-qv__wish" aria-label="Wishlist">♡</button>` : '';
    const detail = qv.showViewFullDetail ? `<a href="#" class="cx-qv__link">${escapeHtml(i18n.viewFullDetail)}</a>` : '';

    const qty = qv.showQuantitySelector
        ? `<div class="cx-qv__qty"><button type="button">−</button><span>1</span><button type="button">+</button></div>`
        : '';

    const actions = [
        qv.showAddToCart ? `<button type="button" class="cx-qv__btn cx-qv__btn--primary">${escapeHtml(i18n.addToCart)}</button>` : '',
        qv.showBuyNow ? `<button type="button" class="cx-qv__btn cx-qv__btn--buy">${escapeHtml(i18n.buyNow)}</button>` : '',
    ].join('');

    return `
        <div class="cx-qv cx-qv--${device}">
            <div class="cx-qv__scroll">
                ${images}
                <div class="cx-qv__body">
                    ${badge}
                    ${name}
                    ${priceRow}
                    ${shortDesc}
                    ${fullDesc}
                    ${meta.length ? `<p class="cx-qv__meta">${escapeHtml(meta.join(' · '))}</p>` : ''}
                    ${tags ? `<div class="cx-qv__tags">${tags}</div>` : ''}
                    ${stock}
                    ${variants}
                    ${wishlist}
                    ${detail}
                </div>
            </div>
            <div class="cx-qv__sticky">
                ${qty}
                <div class="cx-qv__actions">${actions}</div>
            </div>
        </div>`;
}

function renderNotification(config, preview, activeType) {
    const n = config.notifications;
    const type = activeType || ['newProduct', 'promotion', 'lowStock', 'review', 'recentPurchase'].find((key) => n[key]) || 'newProduct';
    const item = preview.notifications[type] || preview.notifications.newProduct;

    if (!n.enabled) {
        return `<div class="cx-preview-empty">Notifications are off</div>`;
    }

    return `
        <div class="cx-notify-stage">
            <div class="cx-notify-stage__page"></div>
            <div class="cx-toast cx-toast--${n.position}" data-cx-toast-preview>
                <div class="cx-toast__eyebrow">${escapeHtml(item.eyebrow)}</div>
                <div class="cx-toast__title">${escapeHtml(item.title)}</div>
                ${item.body ? `<div class="cx-toast__body">${escapeHtml(item.body)}</div>` : ''}
                ${item.action ? `<div class="cx-toast__action">${escapeHtml(item.action)}</div>` : ''}
                <div class="cx-toast__timer"><span data-cx-toast-bar></span></div>
            </div>
        </div>`;
}

function renderNavigation(config, device) {
    const nav = config.navigation;

    if (!nav.backToTop) {
        return `<div class="cx-preview-empty">Back To Top is off</div>`;
    }

    const mobileClass = device === 'mobile' ? ' is-mobile' : '';

    return `
        <div class="cx-nav-stage${mobileClass}">
            <div class="cx-nav-stage__page">
                <div class="cx-nav-stage__hero"></div>
                <div class="cx-nav-stage__lines"></div>
                <div class="cx-nav-stage__lines"></div>
            </div>
            <button type="button" class="cx-back-top cx-back-top--${nav.style} cx-back-top--${nav.position}${nav.fadeIn ? ' is-fading' : ''}" aria-label="Back to top">↑</button>
            ${device === 'mobile' ? '<div class="cx-nav-stage__bottom-nav"></div>' : ''}
        </div>`;
}

function renderPlaceholder(section, config, product, i18n) {
    const enabled = get(config, `${section}.enabled`) !== false;

    if (!enabled) {
        return `<div class="cx-preview-empty">${escapeHtml(section)} is off</div>`;
    }

    if (section === 'productCard') {
        return `
            <div class="cx-card-preview">
                <article class="cx-card">
                    <div class="cx-card__media">
                        ${config.quickView.enabled ? '<button type="button" class="cx-card__qv">Quick View</button>' : ''}
                    </div>
                    <div class="cx-card__name">${escapeHtml(product.name)}</div>
                    <div class="cx-card__price">${escapeHtml(formatMoney(product.sale_price || product.price))}</div>
                </article>
            </div>`;
    }

    return `<div class="cx-placeholder"><h3>${escapeHtml(i18n.placeholder.replace(':section', section))}</h3><p>${escapeHtml(i18n.comingSoon)}</p></div>`;
}

function renderPreview(root, state) {
    const { config, preview, section, device, i18n, activeNotification } = state;
    const product = preview.product;
    let html = '';

    if (section === 'quickView') {
        html = renderQuickView(config, product, i18n, device);
    } else if (section === 'notifications') {
        html = renderNotification(config, preview, activeNotification);
    } else if (section === 'navigation') {
        html = renderNavigation(config, device);
    } else {
        html = renderPlaceholder(section, config, product, i18n);
    }

    root.innerHTML = html;
}

function bindValue(input, config) {
    const path = input.dataset.cxPath;
    if (!path) {
        return;
    }

    if (input.type === 'checkbox') {
        set(config, path, input.checked);
        return;
    }

    if (input.type === 'radio' && !input.checked) {
        return;
    }

    const value = input.dataset.cxType === 'integer' ? Number(input.value) : input.value;
    set(config, path, value);
}

export function initCustomerExperienceSettings() {
    const form = document.querySelector('[data-cx-settings]');
    if (!form) {
        return;
    }

    const config = JSON.parse(form.dataset.cxConfig || '{}');
    const preview = JSON.parse(form.dataset.cxPreview || '{}');
    const i18n = JSON.parse(form.dataset.cxI18n || '{}');
    const configInput = form.querySelector('[data-cx-config-input]');
    const previewRoot = form.querySelector('[data-cx-preview-root]');
    const frame = form.querySelector('[data-cx-device-frame]');

    const state = {
        config,
        preview,
        i18n,
        section: 'quickView',
        device: 'desktop',
        activeNotification: 'newProduct',
        toastTimer: null,
    };

    const sync = () => {
        if (configInput) {
            configInput.value = JSON.stringify(config);
        }

        renderPreview(previewRoot, state);
    };

    form.querySelectorAll('[data-cx-section-tab]').forEach((tab) => {
        tab.addEventListener('click', () => {
            state.section = tab.dataset.cxSectionTab;
            form.querySelectorAll('[data-cx-section-tab]').forEach((node) => {
                const active = node === tab;
                node.classList.toggle('is-active', active);
                node.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            form.querySelectorAll('[data-cx-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.cxPanel !== state.section;
            });
            sync();
        });
    });

    form.querySelectorAll('[data-cx-device]').forEach((button) => {
        button.addEventListener('click', () => {
            state.device = button.dataset.cxDevice;
            form.querySelectorAll('[data-cx-device]').forEach((node) => node.classList.toggle('is-active', node === button));
            frame.dataset.device = state.device;
            sync();
        });
    });

    form.querySelectorAll('[data-cx-path]').forEach((input) => {
        const eventName = input.tagName === 'SELECT' || input.type === 'number' ? 'input' : 'change';
        input.addEventListener(eventName, () => {
            bindValue(input, config);

            if (input.dataset.cxEvent) {
                state.activeNotification = input.dataset.cxEvent;
                state.section = 'notifications';
            }

            sync();
        });
    });

    form.querySelector('[data-cx-simulate]')?.addEventListener('click', () => {
        state.section = 'notifications';
        sync();

        const toast = previewRoot.querySelector('[data-cx-toast-preview]');
        const bar = previewRoot.querySelector('[data-cx-toast-bar]');
        if (!toast) {
            return;
        }

        const duration = Number(config.notifications.duration || 5) * 1000;
        toast.classList.add('is-live');
        if (bar) {
            bar.style.animation = 'none';
            bar.offsetHeight; // eslint-disable-line no-unused-expressions
            bar.style.animation = `cx-toast-countdown ${duration}ms linear forwards`;
        }

        window.clearTimeout(state.toastTimer);
        state.toastTimer = window.setTimeout(() => {
            toast.classList.add('is-gone');
        }, duration);
    });

    sync();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCustomerExperienceSettings);
} else {
    initCustomerExperienceSettings();
}
