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
    });

    window.addEventListener('resize', syncAll);
    syncAll();
    requestAnimationFrame(syncAll);

    return syncAll;
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

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !sheet.hidden) {
            close();
        }
    });
}

function initShop() {
    const shop = document.querySelector('[data-shop]');
    if (!shop) {
        return;
    }

    bindFilterChips(shop);
    bindPricePresets(shop);
    const syncFilterExpand = bindFilterExpand(shop);
    bindFiltersSheet(shop, syncFilterExpand);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initShop);
} else {
    initShop();
}
