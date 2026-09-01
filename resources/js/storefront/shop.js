import { initWishlistScope, syncWishlistButtons } from './wishlist.js';

async function fetchShopPartial(url) {
    const requestUrl = new URL(url, window.location.origin);
    requestUrl.searchParams.set('partial', '1');

    const response = await fetch(requestUrl, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (!response.ok) {
        throw new Error('Shop request failed');
    }

    return response.json();
}

function updateShopResults(shop, data) {
    const results = shop.querySelector('[data-shop-results]');
    if (results) {
        results.innerHTML = data.html;
    }

    const count = shop.querySelector('[data-shop-count]');
    if (count && typeof data.total === 'number') {
        const template = count.dataset.template;
        if (template) {
            count.textContent = template.replace(':count', String(data.total));
        }
    }

    syncWishlistButtons(shop);
    bindLoadMore(shop);
    bindInfiniteScroll(shop);
}

function bindInstantSearch(shop) {
    const form = shop.querySelector('[data-shop-search-form]');
    const input = shop.querySelector('[data-shop-search-input]');
    if (!form || !input) {
        return;
    }

    let timer = null;

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(async () => {
            const url = new URL(form.action, window.location.origin);
            const formData = new FormData(form);
            formData.forEach((value, key) => url.searchParams.set(key, String(value)));

            window.history.replaceState({}, '', url);

            try {
                const data = await fetchShopPartial(url);
                updateShopResults(shop, data);
            } catch {
                form.requestSubmit();
            }
        }, 320);
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
    });
}

function bindFilterChips(root) {
    root.querySelectorAll('[data-shop-filters]').forEach((form) => {
        form.querySelectorAll('.storefront-filters__chip input[type="radio"]').forEach((input) => {
            if (input.checked) {
                input.dataset.lastChecked = 'true';
            }

            input.addEventListener('click', (event) => {
                if (input.dataset.lastChecked === 'true') {
                    input.checked = false;
                    input.dataset.lastChecked = 'false';
                    event.preventDefault();
                    return;
                }

                form.querySelectorAll(`input[type="radio"][name="${input.name}"]`).forEach((peer) => {
                    peer.dataset.lastChecked = 'false';
                });
                input.dataset.lastChecked = 'true';
            });
        });
    });
}

function bindPricePresets(root) {
    root.querySelectorAll('[data-price-filter]').forEach((fieldset) => {
        const minInput = fieldset.querySelector('[data-price-min-input]');
        const maxInput = fieldset.querySelector('[data-price-max-input]');
        const buttons = fieldset.querySelectorAll('[data-price-preset]');

        if (!minInput || !maxInput) {
            return;
        }

        const setActive = (activeButton) => {
            buttons.forEach((button) => {
                const isActive = button === activeButton;
                button.classList.toggle('storefront-filters__badge--active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        };

        const clearActive = () => setActive(null);

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const min = button.dataset.priceMin ?? '';
                const max = button.dataset.priceMax ?? '';
                const isActive = button.classList.contains('storefront-filters__badge--active');

                if (isActive) {
                    minInput.value = '';
                    maxInput.value = '';
                    clearActive();
                    return;
                }

                minInput.value = min;
                maxInput.value = max;
                setActive(button);
            });
        });

        [minInput, maxInput].forEach((input) => {
            input.addEventListener('input', clearActive);
        });
    });
}

function measureFilterOptionsFullHeight(options) {
    const width = options.getBoundingClientRect().width;

    if (width > 0 && options.offsetParent !== null) {
        const previousMaxHeight = options.style.maxHeight;
        options.style.maxHeight = 'none';
        const height = options.scrollHeight;
        options.style.maxHeight = previousMaxHeight;

        return height;
    }

    const container =
        options.closest('.storefront-shop__sidebar-inner')
        || options.closest('.storefront-filters-sheet__panel')
        || options.parentElement;

    const containerWidth = container?.clientWidth || 280;
    const contentWidth = Math.max(containerWidth - 48, 200);

    const wrapper = document.createElement('div');
    wrapper.style.cssText = `position:absolute;left:-9999px;top:0;visibility:hidden;pointer-events:none;width:${contentWidth}px;`;

    const clone = options.cloneNode(true);
    clone.style.maxHeight = 'none';
    clone.dataset.collapsed = 'false';
    wrapper.appendChild(clone);
    document.body.appendChild(wrapper);

    const height = clone.scrollHeight;
    document.body.removeChild(wrapper);

    return height;
}

function getFilterCollapsedHeight(options) {
    const gap = parseFloat(getComputedStyle(options).rowGap)
        || parseFloat(getComputedStyle(options).gap)
        || 8;
    const chip = options.querySelector('.storefront-filters__chip span');

    if (chip) {
        const styles = getComputedStyle(chip);
        const lineHeight = parseFloat(styles.lineHeight) || 20;
        const paddingBlock = parseFloat(styles.paddingTop) + parseFloat(styles.paddingBottom);
        const chipHeight = chip.getBoundingClientRect().height || paddingBlock + lineHeight;

        return chipHeight * 2 + gap;
    }

    return 72;
}

function bindFilterExpand(root) {
    const groups = [...root.querySelectorAll('[data-filter-collapsible]')];

    const syncGroup = (group) => {
        const options = group.querySelector('[data-filter-options]');
        const toggle = group.querySelector('[data-filter-toggle]');
        const moreLabel = group.querySelector('[data-filter-toggle-more]');
        const lessLabel = group.querySelector('[data-filter-toggle-less]');

        if (!options || !toggle) {
            return;
        }

        const collapsedHeight = getFilterCollapsedHeight(options);
        const fullHeight = measureFilterOptionsFullHeight(options);
        const needsToggle = fullHeight > collapsedHeight + 1;
        const isCollapsed = options.dataset.collapsed !== 'false';

        options.style.setProperty('--filter-collapsed-height', `${collapsedHeight}px`);
        options.style.maxHeight = isCollapsed ? `${collapsedHeight}px` : 'none';
        toggle.hidden = !needsToggle;

        if (moreLabel) {
            moreLabel.hidden = !needsToggle || !isCollapsed;
        }

        if (lessLabel) {
            lessLabel.hidden = !needsToggle || isCollapsed;
        }
    };

    const syncAll = () => {
        groups.forEach(syncGroup);
    };

    groups.forEach((group) => {
        const options = group.querySelector('[data-filter-options]');
        const toggle = group.querySelector('[data-filter-toggle]');

        if (!options || !toggle) {
            return;
        }

        toggle.addEventListener('click', () => {
            const willCollapse = options.dataset.collapsed !== 'true';
            options.dataset.collapsed = willCollapse ? 'true' : 'false';
            syncGroup(group);
        });

        if (typeof ResizeObserver !== 'undefined') {
            const observer = new ResizeObserver(() => syncGroup(group));
            observer.observe(options);
        }
    });

    window.addEventListener('resize', syncAll);
    syncAll();
    requestAnimationFrame(syncAll);
    document.fonts?.ready?.then(syncAll);

    return syncAll;
}

function bindBrandFilterSearch(root) {
    root.querySelectorAll('[data-brand-filter]').forEach((group) => {
        const search = group.querySelector('[data-brand-filter-search]');
        if (!search) {
            return;
        }

        const items = [...group.querySelectorAll('[data-brand-filter-item]')];

        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();

            items.forEach((item) => {
                const name = item.dataset.brandName || '';
                item.hidden = query !== '' && !name.includes(query);
            });
        });
    });
}

function bindCategoryFilterSearch(root) {
    root.querySelectorAll('[data-category-filter]').forEach((group) => {
        const search = group.querySelector('[data-category-filter-search]');
        if (!search) {
            return;
        }

        const items = [...group.querySelectorAll('[data-category-filter-item]')];

        search.addEventListener('input', () => {
            const query = search.value.trim().toLowerCase();

            items.forEach((item) => {
                const name = item.dataset.categoryName || '';
                item.hidden = query !== '' && !name.includes(query);
            });
        });
    });
}

function bindFiltersSheet(shop, syncFilterExpand) {
    const sheet = shop.querySelector('[data-filters-sheet]');
    if (!sheet) {
        return;
    }

    const open = () => {
        sheet.hidden = false;
        document.body.classList.add('storefront-filters-sheet-open');

        if (typeof syncFilterExpand === 'function') {
            requestAnimationFrame(() => {
                syncFilterExpand();
                requestAnimationFrame(syncFilterExpand);
            });
        }
    };

    const close = () => {
        sheet.hidden = true;
        document.body.classList.remove('storefront-filters-sheet-open');
    };

    shop.querySelectorAll('[data-filters-sheet-open]').forEach((button) => {
        button.addEventListener('click', open);
    });

    sheet.querySelectorAll('[data-filters-sheet-close]').forEach((button) => {
        button.addEventListener('click', close);
    });
}

async function loadMore(shop, pagination) {
    const nextUrl = pagination?.dataset.nextUrl;
    if (!nextUrl || pagination.dataset.loading === 'true') {
        return;
    }

    const button = pagination.querySelector('[data-shop-load-more-btn]');
    const loading = pagination.querySelector('[data-shop-loading]');
    const infiniteLoading = pagination.querySelector('[data-shop-infinite-loading]');
    pagination.dataset.loading = 'true';

    if (button) {
        button.disabled = true;
    }

    if (loading) {
        loading.hidden = false;
    }

    if (infiniteLoading) {
        infiniteLoading.setAttribute('aria-busy', 'true');
    }

    try {
        const url = new URL(nextUrl, window.location.origin);
        url.searchParams.set('append', '1');
        const data = await fetchShopPartial(url);
        const grid = shop.querySelector('[data-shop-grid]');

        if (grid) {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = data.html;
            wrapper.childNodes.forEach((node) => grid.appendChild(node));
        }

        if (data.next_page_url) {
            pagination.dataset.nextUrl = data.next_page_url;
        } else {
            pagination.remove();
        }

        syncWishlistButtons(shop);
    } finally {
        pagination.dataset.loading = 'false';

        if (button) {
            button.disabled = false;
        }

        if (loading) {
            loading.hidden = true;
        }

        if (infiniteLoading) {
            infiniteLoading.setAttribute('aria-busy', 'false');
        }
    }
}

function isMobileShopView() {
    return window.matchMedia('(max-width: 1023px)').matches;
}

function bindLoadMore(shop) {
    const pagination = shop.querySelector('[data-shop-pagination]');
    const button = pagination?.querySelector('[data-shop-load-more-btn]');

    if (!pagination || !button || button.dataset.bound === 'true') {
        return;
    }

    button.dataset.bound = 'true';
    button.addEventListener('click', () => loadMore(shop, pagination));
}

function bindInfiniteScroll(shop) {
    if (shop.dataset.infiniteScroll !== 'true') {
        return;
    }

    const pagination = shop.querySelector('[data-shop-pagination]');
    const sentinel = pagination?.querySelector('[data-shop-load-more-sentinel]');

    if (!pagination || !sentinel) {
        return;
    }

    let observer = null;

    const teardown = () => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    };

    const setup = () => {
        teardown();

        if (!isMobileShopView()) {
            return;
        }

        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    loadMore(shop, pagination);
                }
            });
        }, { rootMargin: '320px' });

        observer.observe(sentinel);
    };

    if (!shop.dataset.infiniteScrollBound) {
        shop.dataset.infiniteScrollBound = 'true';
        window.matchMedia('(max-width: 1023px)').addEventListener('change', setup);
    }

    setup();
}

function initShop() {
    const shop = document.querySelector('[data-shop]');
    if (!shop) {
        return;
    }

    const count = shop.querySelector('[data-shop-count]');
    if (count) {
        count.dataset.template = count.textContent;
    }

    initWishlistScope(shop);
    bindInstantSearch(shop);
    bindFilterChips(shop);
    bindPricePresets(shop);
    const syncFilterExpand = bindFilterExpand(shop);
    bindBrandFilterSearch(shop);
    bindCategoryFilterSearch(shop);
    bindFiltersSheet(shop, syncFilterExpand);
    bindLoadMore(shop);
    bindInfiniteScroll(shop);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initShop);
} else {
    initShop();
}
