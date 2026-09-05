import { formatMoneyMinor } from './money.js';
import { initWishlistScope } from './wishlist.js';

const RECENT_KEY = 'commerce:recently-viewed';
const DESKTOP_CARD_LIMIT = 6;
const MOBILE_CARD_BATCH = 4;

function readRecentlyViewed() {
    try {
        return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
    } catch {
        return [];
    }
}

function writeRecentlyViewed(items) {
    localStorage.setItem(RECENT_KEY, JSON.stringify(items.slice(0, 24)));
}

function trackRecentlyViewed(page) {
    const item = {
        uuid: page.dataset.productUuid,
        slug: page.dataset.productSlug,
        name: page.dataset.productName,
        image: page.dataset.productImage,
        price: page.dataset.productPrice,
        currency: page.dataset.productCurrency,
        url: window.location.pathname,
    };

    if (!item.uuid) {
        return;
    }

    const items = readRecentlyViewed().filter((entry) => entry.uuid !== item.uuid);
    items.unshift(item);
    writeRecentlyViewed(items);
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
        <article class="storefront-product-card">
            <div class="storefront-product-card__media">
                <a href="${escapeHtml(item.url)}" class="storefront-product-card__media-link" aria-label="${escapeHtml(item.name)}">
                    ${item.image
                        ? `<img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" class="storefront-product-card__image storefront-product-card__image--primary" loading="lazy">`
                        : `<div class="storefront-product-card__placeholder"></div>`}
                </a>
            </div>
            <div class="storefront-product-card__body">
                <a href="${escapeHtml(item.url)}" class="storefront-product-card__name">${escapeHtml(item.name)}</a>
                <div class="storefront-product-card__meta">
                    <span class="storefront-product-card__price">${formatMoneyMinor(item.price, item.currency)}</span>
                </div>
            </div>
        </article>`;
}

function isMobilePdpView() {
    return window.matchMedia('(max-width: 1023px)').matches;
}

function revealCards(cards, startIndex, count) {
    let revealed = 0;
    cards.forEach((card) => {
        const index = Number(card.dataset.pdpIndex ?? card.dataset.recentIndex ?? 0);
        if (index >= startIndex && index < startIndex + count) {
            card.hidden = false;
            revealed += 1;
        }
    });

    return revealed;
}

function initPdpPagination(page) {
    page.querySelectorAll('[data-pdp-pagination]').forEach((pagination) => {
        const grid = pagination.parentElement?.querySelector('[data-recommended-grid], [data-recently-viewed-grid]');

        if (!grid) {
            return;
        }

        const cards = [...grid.querySelectorAll('[data-pdp-card]')];
        const loadMoreBtn = pagination.querySelector('[data-pdp-load-more]');
        const sentinel = pagination.querySelector('[data-pdp-load-sentinel]');

        if (cards.length === 0) {
            return;
        }

        if (!loadMoreBtn && !sentinel) {
            return;
        }

        const batchSize = Number(pagination.dataset.pdpBatchSize || MOBILE_CARD_BATCH);
        let visible = Number(pagination.dataset.pdpVisible || DESKTOP_CARD_LIMIT);
        const total = Number(pagination.dataset.pdpTotal || cards.length);

        const syncDesktop = () => {
            if (isMobilePdpView()) {
                return;
            }

            cards.forEach((card) => {
                const index = Number(card.dataset.pdpIndex ?? card.dataset.recentIndex ?? 0);
                card.hidden = index >= visible;
            });

            if (loadMoreBtn) {
                loadMoreBtn.hidden = visible >= total;
            }
        };

        const revealMobileBatch = () => {
            if (!isMobilePdpView() || visible >= total) {
                return;
            }

            visible = Math.min(visible + batchSize, total);
            pagination.dataset.pdpVisible = String(visible);
            revealCards(cards, 0, visible);

            if (visible >= total && pagination.dataset.pdpPagination === 'recently-viewed') {
                pagination.hidden = true;
            }

            if (loadMoreBtn) {
                loadMoreBtn.hidden = true;
            }
        };

        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                visible = Math.min(visible + DESKTOP_CARD_LIMIT, total);
                pagination.dataset.pdpVisible = String(visible);
                syncDesktop();
            });
        }

        if (sentinel) {
            let observer = null;

            const setupObserver = () => {
                if (observer) {
                    observer.disconnect();
                    observer = null;
                }

                if (!isMobilePdpView()) {
                    return;
                }

                observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            revealMobileBatch();
                        }
                    });
                }, { rootMargin: '240px' });

                observer.observe(sentinel);
            };

            window.matchMedia('(max-width: 1023px)').addEventListener('change', () => {
                if (isMobilePdpView()) {
                    revealMobileBatch();
                } else {
                    syncDesktop();
                }
                setupObserver();
            });

            setupObserver();
        }

        syncDesktop();
    });
}

function renderRecentlyViewed(page) {
    const section = page.querySelector('[data-recently-viewed-section]');
    const grid = page.querySelector('[data-recently-viewed-grid]');
    const pagination = page.querySelector('[data-pdp-pagination="recently-viewed"]');

    if (!section || !grid) {
        return;
    }

    const currentUuid = page.dataset.productUuid;
    const items = readRecentlyViewed().filter((entry) => entry.uuid !== currentUuid);

    if (items.length === 0) {
        section.hidden = true;
        return;
    }

    grid.innerHTML = items.map((item, index) => {
        const hidden = isMobilePdpView()
            ? index >= MOBILE_CARD_BATCH
            : index >= DESKTOP_CARD_LIMIT;

        return `
        <div class="storefront-pdp-card" data-pdp-card data-recent-index="${index}"${hidden ? ' hidden' : ''}>
            ${renderRecentlyViewedCard(item)}
        </div>`;
    }).join('');

    if (pagination) {
        pagination.hidden = items.length <= (isMobilePdpView() ? MOBILE_CARD_BATCH : DESKTOP_CARD_LIMIT);
        pagination.dataset.pdpTotal = String(items.length);
        pagination.dataset.pdpVisible = String(isMobilePdpView() ? MOBILE_CARD_BATCH : DESKTOP_CARD_LIMIT);
    }

    section.hidden = false;
    initPdpPagination(page);
}

function getGalleryItems(gallery) {
    try {
        return JSON.parse(gallery.querySelector('[data-gallery-items]')?.textContent || '[]');
    } catch {
        return [];
    }
}

function escapeHtmlAttribute(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('"', '&quot;')
        .replaceAll('<', '&lt;');
}

function getGalleryActiveIndex(gallery) {
    const activeThumb = gallery.querySelector('[data-gallery-thumb].storefront-gallery__thumb--active');

    if (activeThumb?.dataset.galleryIndex !== undefined) {
        return Number(activeThumb.dataset.galleryIndex);
    }

    return Number(gallery.dataset.galleryActiveIndex || 0);
}

function getGalleryImageIndexes(items) {
    return items
        .map((entry, index) => ({ entry, index }))
        .filter(({ entry }) => entry.type !== 'video')
        .map(({ index }) => index);
}

function syncMainGalleryImage(gallery, index) {
    const items = getGalleryItems(gallery);
    const item = items[index];
    const stage = gallery.querySelector('[data-gallery-stage]');

    if (!item || !stage) {
        return;
    }

    gallery.dataset.galleryActiveIndex = String(index);
    gallery.querySelectorAll('[data-gallery-thumb]').forEach((thumb) => {
        thumb.classList.toggle(
            'storefront-gallery__thumb--active',
            Number(thumb.dataset.galleryIndex) === index,
        );
    });

    if (item.type === 'video') {
        stage.innerHTML = `
            <video class="storefront-gallery__video" data-gallery-main data-gallery-type="video" controls playsinline poster="${escapeHtmlAttribute(item.thumbnail || '')}">
                <source src="${escapeHtmlAttribute(item.url)}" type="video/mp4">
            </video>`;
        return;
    }

    stage.innerHTML = `
        <button
            type="button"
            class="storefront-gallery__zoom"
            data-gallery-zoom
            data-gallery-lightbox-trigger
            aria-label="Enlarge image"
        >
            <img src="${escapeHtmlAttribute(item.url)}" alt="${escapeHtmlAttribute(item.alt || '')}" class="storefront-gallery__image" data-gallery-main data-gallery-type="image"${item.srcset ? ` srcset="${escapeHtmlAttribute(item.srcset)}"` : ''}${item.sizes ? ` sizes="${escapeHtmlAttribute(item.sizes)}"` : ''}>
        </button>`;
}

function renderLightboxThumbs(lightbox, items, activeIndex, onSelect) {
    const container = lightbox.querySelector('[data-gallery-lightbox-thumbs]');
    if (!container) {
        return;
    }

    const imageIndexes = getGalleryImageIndexes(items);

    if (imageIndexes.length <= 1) {
        container.hidden = true;
        container.innerHTML = '';
        return;
    }

    container.hidden = false;
    container.innerHTML = imageIndexes.map((index) => {
        const item = items[index];

        return `
            <button
                type="button"
                class="storefront-gallery-lightbox__thumb ${index === activeIndex ? 'storefront-gallery-lightbox__thumb--active' : ''}"
                data-gallery-lightbox-thumb
                data-gallery-index="${index}"
                aria-label="${escapeHtmlAttribute(item.alt || '')}"
            >
                <img src="${escapeHtmlAttribute(item.thumbnail || item.url)}" alt="" class="storefront-gallery-lightbox__thumb-image" loading="lazy">
            </button>`;
    }).join('');

    container.querySelectorAll('[data-gallery-lightbox-thumb]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            onSelect(Number(button.dataset.galleryIndex));
        });
    });

    const activeThumb = container.querySelector('.storefront-gallery-lightbox__thumb--active');
    activeThumb?.scrollIntoView({ behavior: 'auto', block: 'nearest', inline: 'nearest' });
}

function bindGalleryLightbox(gallery) {
    const lightbox = gallery.querySelector('[data-gallery-lightbox]');
    if (!lightbox || lightbox.dataset.bound === 'true') {
        return;
    }

    lightbox.dataset.bound = 'true';

    if (lightbox.parentElement !== document.body) {
        document.body.appendChild(lightbox);
    }

    const lightboxImage = lightbox.querySelector('[data-gallery-lightbox-image]');
    const page = gallery.closest('[data-product-page]');
    const isMarket = page?.classList.contains('storefront-pdp--market');
    let activeIndex = 0;

    const setLightboxIndex = (index) => {
        const items = getGalleryItems(gallery);
        const item = items[index];

        if (!item || item.type === 'video' || !lightboxImage) {
            return;
        }

        activeIndex = index;
        lightboxImage.src = item.url;
        lightboxImage.alt = item.alt || '';
        syncMainGalleryImage(gallery, index);
        renderLightboxThumbs(lightbox, items, index, setLightboxIndex);
    };

    const stepLightbox = (direction) => {
        const items = getGalleryItems(gallery);
        const imageIndexes = getGalleryImageIndexes(items);

        if (imageIndexes.length <= 1) {
            return;
        }

        const currentPosition = imageIndexes.indexOf(activeIndex);
        const basePosition = currentPosition === -1 ? 0 : currentPosition;
        const nextPosition = (basePosition + direction + imageIndexes.length) % imageIndexes.length;
        setLightboxIndex(imageIndexes[nextPosition]);
    };

    const open = () => {
        const items = getGalleryItems(gallery);
        let index = getGalleryActiveIndex(gallery);
        const item = items[index];

        if (!item || item.type === 'video') {
            const fallback = getGalleryImageIndexes(items)[0];
            if (fallback === undefined) {
                return;
            }
            index = fallback;
        }

        setLightboxIndex(index);
        lightbox.hidden = false;
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.dataset.scrollLockY = String(window.scrollY);
        document.body.style.top = `-${window.scrollY}px`;
        document.body.classList.add('storefront-gallery-lightbox-open');
        window.__activeGalleryLightbox = { step: stepLightbox, close };
    };

    const close = () => {
        const scrollY = Number(document.body.dataset.scrollLockY || 0);
        lightbox.hidden = true;
        lightbox.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('storefront-gallery-lightbox-open');
        document.body.style.top = '';
        delete document.body.dataset.scrollLockY;
        window.scrollTo(0, scrollY);
        if (window.__activeGalleryLightbox?.close === close) {
            window.__activeGalleryLightbox = null;
        }
    };

    gallery.addEventListener('click', (event) => {
        if (!event.target.closest('[data-gallery-lightbox-trigger]')) {
            return;
        }

        event.preventDefault();
        open();
    });

    lightbox.querySelectorAll('[data-gallery-lightbox-close]').forEach((button) => {
        button.addEventListener('click', close);
    });

    const thumbsScroller = lightbox.querySelector('[data-gallery-lightbox-thumbs]');
    thumbsScroller?.addEventListener('touchstart', (event) => {
        event.stopPropagation();
    }, { passive: true });
    thumbsScroller?.addEventListener('touchmove', (event) => {
        event.stopPropagation();
    }, { passive: true });

    if (!document.body.dataset.galleryLightboxKeyBound) {
        document.body.dataset.galleryLightboxKeyBound = 'true';
        document.addEventListener('keydown', (event) => {
            const active = window.__activeGalleryLightbox;
            if (!active) {
                return;
            }

            if (event.key === 'Escape') {
                active.close();
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                active.step(1);
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                active.step(-1);
            }
        });
    }

    if (!isMarket) {
        return;
    }

    gallery.querySelectorAll('[data-gallery-lightbox-trigger]').forEach((trigger) => {
        trigger.style.cursor = 'zoom-in';
    });
}

function initGallery(root) {
    const gallery = root.querySelector('[data-product-gallery]');
    if (!gallery) {
        return;
    }

    const stage = gallery.querySelector('[data-gallery-stage]');
    const thumbs = gallery.querySelectorAll('[data-gallery-thumb]');
    const page = root.closest?.('[data-product-page]') ?? root;
    const isMarket = page?.classList?.contains('storefront-pdp--market') ?? root.classList?.contains('storefront-pdp--market');

    const renderMain = (type, url, alt, poster, index = 0, srcset = '', sizes = '') => {
        if (!stage) {
            return;
        }

        gallery.dataset.galleryActiveIndex = String(index);

        if (type === 'video') {
            stage.innerHTML = `
                <video class="storefront-gallery__video" data-gallery-main data-gallery-type="video" controls playsinline poster="${poster || ''}">
                    <source src="${url}" type="video/mp4">
                </video>`;
            return;
        }

        const srcsetAttr = srcset ? ` srcset="${escapeHtmlAttribute(srcset)}"` : '';
        const sizesAttr = sizes ? ` sizes="${escapeHtmlAttribute(sizes)}"` : '';

        stage.innerHTML = `
            <button
                type="button"
                class="storefront-gallery__zoom"
                data-gallery-zoom
                data-gallery-lightbox-trigger
                aria-label="Enlarge image"
            >
                <img src="${url}" alt="${alt}" class="storefront-gallery__image" data-gallery-main data-gallery-type="image"${srcsetAttr}${sizesAttr}>
            </button>`;

        if (!isMarket) {
            initZoom(stage);
        }
    };

    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            thumbs.forEach((node) => node.classList.remove('storefront-gallery__thumb--active'));
            thumb.classList.add('storefront-gallery__thumb--active');
            renderMain(
                thumb.dataset.galleryType,
                thumb.dataset.galleryUrl,
                thumb.dataset.galleryAlt,
                thumb.dataset.galleryPoster,
                Number(thumb.dataset.galleryIndex || 0),
                thumb.dataset.gallerySrcset || '',
                thumb.dataset.gallerySizes || '',
            );
        });
    });

    if (!isMarket) {
        initZoom(gallery);
    }

    bindGalleryLightbox(gallery);
}

function updateGalleryImage(page, imageUrl, srcset) {
    if (!imageUrl) {
        return;
    }

    const gallery = page.querySelector('[data-product-gallery]');
    const mainImage = gallery?.querySelector('[data-gallery-main][data-gallery-type="image"]');
    const lightboxImage = gallery?.querySelector('[data-gallery-lightbox-image]');

    if (mainImage) {
        mainImage.src = imageUrl;
        if (srcset) {
            mainImage.setAttribute('srcset', srcset);
        } else {
            mainImage.removeAttribute('srcset');
        }
    }

    if (lightboxImage && gallery?.querySelector('[data-gallery-lightbox]') && !gallery.querySelector('[data-gallery-lightbox]').hidden) {
        lightboxImage.src = imageUrl;
    }
}

function initZoom(root) {
    const zoom = root.querySelector('[data-gallery-zoom]');
    const image = root.querySelector('[data-gallery-main][data-gallery-type="image"]');
    if (!zoom || !image) {
        return;
    }

    zoom.addEventListener('mousemove', (event) => {
        const rect = zoom.getBoundingClientRect();
        const x = ((event.clientX - rect.left) / rect.width) * 100;
        const y = ((event.clientY - rect.top) / rect.height) * 100;
        image.style.transformOrigin = `${x}% ${y}%`;
        image.style.transform = 'scale(1.75)';
    });

    zoom.addEventListener('mouseleave', () => {
        image.style.transform = 'scale(1)';
    });
}

function formatPrice(amount, currency) {
    return formatMoneyMinor(amount, currency);
}

function normalizeOptionKey(key) {
    return String(key).toLowerCase();
}

function getOptionValue(options, axisKey) {
    if (!options || typeof options !== 'object') {
        return undefined;
    }

    if (options[axisKey] !== undefined) {
        return options[axisKey];
    }

    const normalized = normalizeOptionKey(axisKey);
    return Object.entries(options).find(([key]) => normalizeOptionKey(key) === normalized)?.[1];
}

function buildInitialSelections(variant, axes) {
    const selections = {};

    axes.forEach((axis) => {
        const value = getOptionValue(variant?.options, axis.key);
        if (value !== undefined && value !== null && value !== '') {
            selections[axis.key] = String(value);
        }
    });

    return selections;
}

function resolveVariant(variants, selections) {
    const entries = Object.entries(selections).filter(([, value]) => value !== undefined && value !== '');

    if (entries.length === 0) {
        return variants[0] ?? null;
    }

    const exact = variants.find((variant) => entries.every(([key, value]) => String(getOptionValue(variant.options, key)) === String(value)));
    if (exact) {
        return exact;
    }

    const partialMatches = variants.filter((variant) => entries.every(([key, value]) => {
        const optionValue = getOptionValue(variant.options, key);
        return optionValue === undefined || String(optionValue) === String(value);
    }));

    return partialMatches.find((variant) => variant.available > 0) ?? partialMatches[0] ?? null;
}

function initVariants(page) {
    const variants = JSON.parse(page.dataset.variants || '[]');
    const axes = JSON.parse(page.dataset.variantAxes || '[]');
    const currency = page.dataset.productCurrency || '';
    const buyBox = page.querySelector('[data-buy-box]');
    if (!buyBox || variants.length === 0) {
        return;
    }

    const amountEl = buyBox.querySelector('[data-buy-amount]');
    const compareEl = buyBox.querySelector('[data-buy-compare]');
    const discountEl = buyBox.querySelector('[data-buy-discount]');
    const stockNoteEl = buyBox.querySelector('[data-buy-stock-note]');
    const variantInput = buyBox.querySelector('[data-buy-variant-input]');
    const quantityInput = buyBox.querySelector('[data-buy-quantity]');
    const mobilePrice = page.querySelector('[data-mobile-buy-price]');
    const variantAxesRoot = page.querySelector('[data-variant-axes]');

    let selections = buildInitialSelections(
        variants.find((entry) => entry.uuid === variantInput?.value) ?? variants[0],
        axes,
    );

    const syncAxisButtons = () => {
        if (!variantAxesRoot) {
            return;
        }

        variantAxesRoot.querySelectorAll('[data-variant-axis-value]').forEach((button) => {
            const axisKey = button.dataset.axisKey;
            const axisValue = button.dataset.axisValue;
            button.classList.toggle(
                'storefront-variant-axes__option--active',
                selections[axisKey] === axisValue,
            );
        });
    };

    const applyVariant = (uuid) => {
        const variant = variants.find((entry) => entry.uuid === uuid);
        if (!variant) {
            return;
        }

        selections = buildInitialSelections(variant, axes);

        if (amountEl) {
            amountEl.textContent = formatPrice(variant.price, currency);
        }
        if (mobilePrice) {
            mobilePrice.textContent = formatPrice(variant.price, currency);
        }
        if (compareEl) {
            if (variant.compare_at_price && variant.compare_at_price > variant.price) {
                compareEl.textContent = formatPrice(variant.compare_at_price, currency);
                compareEl.hidden = false;
            } else {
                compareEl.hidden = true;
            }
        }
        if (discountEl && variant.compare_at_price && variant.compare_at_price > variant.price) {
            const percent = Math.round((1 - (variant.price / variant.compare_at_price)) * 100);
            discountEl.textContent = `-${percent}%`;
            discountEl.hidden = false;
        } else if (discountEl) {
            discountEl.hidden = true;
        }
        if (variantInput) {
            variantInput.value = variant.uuid;
        }
        if (quantityInput) {
            quantityInput.max = String(Math.max(variant.available, 1));
            quantityInput.value = '1';
        }
        if (stockNoteEl) {
            stockNoteEl.textContent = variant.available > 0
                ? stockNoteEl.dataset.inStockLabel || stockNoteEl.textContent
                : stockNoteEl.dataset.outOfStockLabel || 'Out of stock';
        }

        updateGalleryImage(page, variant.image_thumbnail, variant.image_srcset);

        page.querySelectorAll('[data-wishlist-toggle]').forEach((button) => {
            button.dataset.variantUuid = uuid;
        });

        page.querySelectorAll('[data-variant-option]').forEach((button) => {
            button.classList.toggle('storefront-variant-selector__option--active', button.dataset.variantUuid === uuid);
        });

        syncAxisButtons();
    };

    if (variantAxesRoot) {
        variantAxesRoot.addEventListener('click', (event) => {
            const button = event.target.closest('[data-variant-axis-value]');
            if (!button || button.disabled) {
                return;
            }

            selections[button.dataset.axisKey] = button.dataset.axisValue;
            const resolved = resolveVariant(variants, selections);
            if (resolved) {
                applyVariant(resolved.uuid);
            }
        });
    }

    page.querySelectorAll('[data-variant-option]').forEach((button) => {
        button.addEventListener('click', () => applyVariant(button.dataset.variantUuid));
    });

    if (variantInput?.value) {
        applyVariant(variantInput.value);
    }
}

function initQuantityStepper(page) {
    page.querySelectorAll('[data-qty-stepper]').forEach((stepper) => {
        const input = stepper.querySelector('[data-buy-quantity]');
        const decrease = stepper.querySelector('[data-qty-decrease]');
        const increase = stepper.querySelector('[data-qty-increase]');

        if (!input) {
            return;
        }

        const clamp = () => {
            const min = Number(input.min || 1);
            const max = Number(input.max || min);
            let value = Number(input.value || min);
            value = Math.min(Math.max(value, min), max);
            input.value = String(value);
        };

        decrease?.addEventListener('click', () => {
            input.stepDown();
            clamp();
        });

        increase?.addEventListener('click', () => {
            input.stepUp();
            clamp();
        });

        input.addEventListener('change', clamp);
    });
}

function initShare(root) {
    root.querySelectorAll('[data-share-button]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.dataset.shareUrl;
            const title = button.dataset.shareTitle;

            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch {
                    // fall through
                }
            }

            try {
                await navigator.clipboard.writeText(url);
                button.classList.add('storefront-share-btn--copied');
                window.setTimeout(() => button.classList.remove('storefront-share-btn--copied'), 1500);
            } catch {
                window.prompt('Copy link:', url);
            }
        });
    });
}

function initMobileBuyBar(page) {
    const bar = page.querySelector('[data-mobile-buy-bar]');
    const form = page.querySelector('[data-buy-form]');
    if (!bar || !form) {
        return;
    }

    bar.querySelectorAll('[data-mobile-buy-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const redirectInput = form.querySelector('input[name="redirect_to"]');
            if (redirectInput) {
                redirectInput.remove();
            }

            if (trigger.dataset.mobileBuyTrigger === 'checkout') {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'redirect_to';
                input.value = 'checkout';
                form.appendChild(input);
            }

            form.requestSubmit();
        });
    });
}

function initProductPage() {
    const page = document.querySelector('[data-product-page]');
    if (!page) {
        return;
    }

    trackRecentlyViewed(page);
    renderRecentlyViewed(page);
    initWishlistScope(page);
    initGallery(page);
    initVariants(page);
    initQuantityStepper(page);
    initShare(page);
    initMobileBuyBar(page);
    initPdpPagination(page);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initProductPage);
} else {
    initProductPage();
}
