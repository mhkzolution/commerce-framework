import { updateWishlistUi } from './wishlist.js';

const PROMO_DISMISS_KEY = 'commerce:promo-dismissed';
const RECENT_SEARCHES_KEY = 'commerce:recent-searches';

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function readRecentSearches() {
    try {
        const items = JSON.parse(localStorage.getItem(RECENT_SEARCHES_KEY) || '[]');

        return Array.isArray(items) ? items.filter((item) => typeof item === 'string' && item.trim() !== '') : [];
    } catch {
        return [];
    }
}

function saveRecentSearch(term) {
    const trimmed = term.trim();
    if (trimmed.length < 2) {
        return;
    }

    const items = readRecentSearches().filter((item) => item !== trimmed);
    items.unshift(trimmed);
    localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(items.slice(0, 8)));
}

function renderRecentSearches(overlay) {
    const section = overlay.querySelector('[data-search-recent-section]');
    const list = overlay.querySelector('[data-search-recent-list]');
    const shopUrl = overlay.dataset.shopUrl || '/shop';
    const items = readRecentSearches();

    if (!section || !list) {
        return;
    }

    if (items.length === 0) {
        section.hidden = true;
        list.innerHTML = '';

        return;
    }

    section.hidden = false;
    list.innerHTML = items.map((term) => `
        <li class="storefront-search-recent__item">
            <a href="${shopUrl}?search=${encodeURIComponent(term)}" class="storefront-search-recent__link">${escapeHtml(term)}</a>
            <button type="button" class="storefront-search-recent__remove" data-search-recent-remove="${encodeURIComponent(term)}" aria-label="Remove">×</button>
        </li>
    `).join('');
}

function lockBody(lock) {
    document.body.classList.toggle('storefront-drawer-open', lock);
}

function openDrawer(id) {
    if (id !== 'mobile-nav') {
        closeDrawer('mobile-nav');
    }

    const drawer = document.querySelector(`[data-drawer="${id}"]`);
    if (!drawer) {
        return;
    }

    if (id === 'mobile-nav') {
        document.querySelectorAll('[data-mobile-nav]').forEach((nav) => {
            nav.querySelectorAll('[data-mobile-nav-panel]').forEach((panel) => {
                if (panel.dataset.mobileNavPanel === 'root') {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', '');
                }
            });
        });
    }

    drawer.hidden = false;
    requestAnimationFrame(() => drawer.classList.add('storefront-drawer--open'));
    lockBody(true);

    const closeButton = drawer.querySelector('[data-drawer-close-trigger]');
    closeButton?.focus();
}

function closeDrawer(id) {
    const drawer = document.querySelector(`[data-drawer="${id}"]`);
    if (!drawer) {
        return;
    }

    drawer.classList.remove('storefront-drawer--open');
    lockBody(false);

    window.setTimeout(() => {
        if (!drawer.classList.contains('storefront-drawer--open')) {
            drawer.hidden = true;
        }
    }, 240);
}

function closeAllDrawers() {
    document.querySelectorAll('[data-drawer]').forEach((drawer) => {
        closeDrawer(drawer.dataset.drawer);
    });
}

function bindWishlistSync() {
    window.addEventListener('commerce:wishlist-changed', updateWishlistUi);
    updateWishlistUi();
}

function bindDrawers() {
    document.addEventListener('click', (event) => {
        const openTrigger = event.target.closest('[data-drawer-open]');
        if (openTrigger) {
            event.preventDefault();
            openDrawer(openTrigger.dataset.drawerOpen);
            return;
        }

        const closeTrigger = event.target.closest('[data-drawer-close]');
        if (closeTrigger) {
            event.preventDefault();
            closeDrawer(closeTrigger.dataset.drawerClose);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeAllDrawers();
            closeUserMenu();
            closeMegaMenus();
            closeSearchOverlay();
        }
    });
}

function bindMobileSearch() {
    // Mobile search opens the full-screen overlay via data-search-open.
}

function closeUserMenu() {
    document.querySelectorAll('[data-user-menu]').forEach((menu) => {
        menu.removeAttribute('open');
    });
}

function bindUserMenu() {
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest('[data-user-menu-toggle]');
        if (toggle) {
            event.preventDefault();
            const menu = toggle.closest('[data-user-menu]');
            const isOpen = menu?.hasAttribute('open');

            closeUserMenu();
            closeMegaMenus();

            if (menu && !isOpen) {
                menu.setAttribute('open', '');
            }

            return;
        }

        if (!event.target.closest('[data-user-menu]')) {
            closeUserMenu();
        }
    });
}

let megaMenuCloseTimer = null;

function cancelMegaMenuClose() {
    if (megaMenuCloseTimer !== null) {
        window.clearTimeout(megaMenuCloseTimer);
        megaMenuCloseTimer = null;
    }
}

function scheduleMegaMenuClose() {
    cancelMegaMenuClose();
    megaMenuCloseTimer = window.setTimeout(() => {
        closeMegaMenus();
        megaMenuCloseTimer = null;
    }, 220);
}

function closeMegaMenus() {
    cancelMegaMenuClose();

    document.querySelectorAll('[data-mega-menu-panel]').forEach((panel) => {
        panel.hidden = true;
    });

    document.querySelectorAll('[data-mega-menu-item]').forEach((item) => {
        item.classList.remove('storefront-primary-nav__item--open');
    });

    document.querySelectorAll('[data-mega-menu-trigger]').forEach((trigger) => {
        trigger.setAttribute('aria-expanded', 'false');
    });

    document.querySelectorAll('[data-mega-menu-backdrop]').forEach((backdrop) => {
        backdrop.hidden = true;
    });
}

function openMegaMenu(id) {
    closeMegaMenus();

    const panel = document.querySelector(`[data-mega-menu-panel="${id}"]`);
    const item = document.querySelector(`[data-mega-menu-item="${id}"]`);
    const trigger = document.querySelector(`[data-mega-menu-trigger="${id}"]`);
    const backdrop = document.querySelector('[data-mega-menu-backdrop]');

    if (!panel || !item || !trigger) {
        return;
    }

    panel.hidden = false;
    item.classList.add('storefront-primary-nav__item--open');
    trigger.setAttribute('aria-expanded', 'true');
    backdrop?.removeAttribute('hidden');
}

function bindMegaMenu() {
    const canHover = window.matchMedia('(hover: hover) and (min-width: 1024px)').matches;

    document.querySelectorAll('[data-mega-menu-trigger]').forEach((trigger) => {
        const id = trigger.dataset.megaMenuTrigger;
        const item = trigger.closest('[data-mega-menu-item]');
        const panel = document.querySelector(`[data-mega-menu-panel="${id}"]`);

        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            const isOpen = item?.classList.contains('storefront-primary-nav__item--open');

            if (isOpen) {
                closeMegaMenus();
            } else {
                openMegaMenu(id);
            }
        });

        if (canHover) {
            const keepOpen = () => {
                cancelMegaMenuClose();
                openMegaMenu(id);
            };

            item?.addEventListener('mouseenter', keepOpen);
            item?.addEventListener('mouseleave', scheduleMegaMenuClose);
            panel?.addEventListener('mouseenter', cancelMegaMenuClose);
            panel?.addEventListener('mouseleave', scheduleMegaMenuClose);
        }
    });

    document.querySelectorAll('[data-mega-menu-backdrop]').forEach((backdrop) => {
        backdrop.addEventListener('click', closeMegaMenus);
        backdrop.addEventListener('mouseenter', cancelMegaMenuClose);
        backdrop.addEventListener('mouseleave', scheduleMegaMenuClose);
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-primary-nav]') && !event.target.closest('[data-mega-menu-panel]')) {
            closeMegaMenus();
        }
    });
}

function openSearchOverlay() {
    const overlay = document.querySelector('[data-search-overlay]');
    if (!overlay) {
        return;
    }

    closeMegaMenus();
    closeUserMenu();
    closeAllDrawers();

    overlay.hidden = false;
    document.body.classList.add('storefront-search-open');
    renderRecentSearches(overlay);

    const input = overlay.querySelector('#header-search-input');
    requestAnimationFrame(() => input?.focus());
}

function closeSearchOverlay() {
    const overlay = document.querySelector('[data-search-overlay]');
    if (!overlay || overlay.hidden) {
        return;
    }

    overlay.hidden = true;
    document.body.classList.remove('storefront-search-open');

    const results = overlay.querySelector('[data-search-results]');
    const hints = overlay.querySelector('[data-search-hints]');
    if (results) {
        results.hidden = true;
        results.innerHTML = '';
    }
    hints?.removeAttribute('hidden');
    renderRecentSearches(overlay);
}

function bindSearchAutocomplete() {
    const overlay = document.querySelector('[data-search-overlay]');
    if (!overlay) {
        return;
    }

    const endpoint = overlay.dataset.searchUrl;
    const input = overlay.querySelector('#header-search-input');
    const results = overlay.querySelector('[data-search-results]');
    const hints = overlay.querySelector('[data-search-hints]');

    if (!endpoint || !input || !results) {
        return;
    }

    let debounceTimer = null;
    let activeController = null;

    const labels = {
        products: overlay.dataset.searchLabelProducts || 'Products',
        categories: overlay.dataset.searchLabelCategories || 'Categories',
        collections: overlay.dataset.searchLabelCollections || 'Collections',
        brands: overlay.dataset.searchLabelBrands || 'Brands',
        viewAll: overlay.dataset.searchLabelViewAll || 'View all results',
        empty: overlay.dataset.searchLabelEmpty || 'No matches found',
    };

    const resetResults = () => {
        results.hidden = true;
        results.innerHTML = '';
        hints?.removeAttribute('hidden');
    };

    const renderSection = (title, items, renderItem) => {
        if (!items?.length) {
            return '';
        }

        const links = items.map(renderItem).join('');

        return `
            <section class="storefront-search-results__section">
                <h3 class="storefront-search-results__title">${title}</h3>
                <ul class="storefront-search-results__list">${links}</ul>
            </section>
        `;
    };

    const renderResults = (payload, query) => {
        const productSection = renderSection(labels.products, payload.products, (item) => `
            <li>
                <a href="${item.url}" class="storefront-search-results__product">
                    ${item.image_url ? `<img src="${item.image_url}" alt="" class="storefront-search-results__thumb" loading="lazy">` : '<span class="storefront-search-results__thumb storefront-search-results__thumb--placeholder"></span>'}
                    <span class="storefront-search-results__copy">
                        <span class="storefront-search-results__name">${item.name}</span>
                        <span class="storefront-search-results__price">${item.price_label}</span>
                    </span>
                </a>
            </li>
        `);

        const catalogLink = (item) => `
            <li>
                <a href="${item.url}" class="storefront-search-results__link">${item.name}</a>
            </li>
        `;

        const html = [
            productSection,
            renderSection(labels.categories, payload.categories, catalogLink),
            renderSection(labels.collections, payload.collections, catalogLink),
            renderSection(labels.brands, payload.brands, catalogLink),
        ].join('');

        if (!html) {
            results.innerHTML = `<p class="storefront-search-results__empty">${labels.empty}</p>`;
        } else {
            results.innerHTML = `
                ${html}
                <a href="${payload.shop_url}" class="storefront-search-results__view-all">${labels.viewAll} →</a>
            `;
        }

        hints?.setAttribute('hidden', '');
        results.hidden = false;
    };

    const fetchSuggestions = async (query) => {
        if (query.length < 2) {
            resetResults();
            return;
        }

        activeController?.abort();
        activeController = new AbortController();

        const params = new URLSearchParams({ q: query, limit: '8' });

        try {
            const response = await fetch(`${endpoint}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
                signal: activeController.signal,
            });

            if (!response.ok) {
                return;
            }

            const json = await response.json();
            renderResults(json.data ?? {}, query);
        } catch (error) {
            if (error.name !== 'AbortError') {
                resetResults();
            }
        }
    };

    input.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            fetchSuggestions(input.value.trim());
        }, 300);
    });

    overlay.querySelector('[data-search-form]')?.addEventListener('submit', () => {
        const term = input.value.trim();
        if (term !== '') {
            saveRecentSearch(term);
        }
    });

    overlay.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-search-recent-remove]');
        if (!removeButton) {
            return;
        }

        event.preventDefault();
        const term = decodeURIComponent(removeButton.dataset.searchRecentRemove || '');
        const next = readRecentSearches().filter((item) => item !== term);
        localStorage.setItem(RECENT_SEARCHES_KEY, JSON.stringify(next));
        renderRecentSearches(overlay);
    });
}

function bindSearchOverlay() {
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-search-open]')) {
            event.preventDefault();
            openSearchOverlay();
            return;
        }

        if (event.target.closest('[data-search-close]')) {
            event.preventDefault();
            closeSearchOverlay();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === '/' && !event.target.closest('input, textarea, select')) {
            event.preventDefault();
            openSearchOverlay();
        }
    });
}

function bindMobileNav() {
    document.querySelectorAll('[data-mobile-nav]').forEach((nav) => {
        nav.addEventListener('click', (event) => {
            const openTrigger = event.target.closest('[data-mobile-nav-open]');
            if (openTrigger) {
                event.preventDefault();
                const panelId = openTrigger.dataset.mobileNavOpen;
                nav.querySelector('[data-mobile-nav-panel="root"]')?.setAttribute('hidden', '');
                nav.querySelector(`[data-mobile-nav-panel="${panelId}"]`)?.removeAttribute('hidden');
                return;
            }

            const backTrigger = event.target.closest('[data-mobile-nav-back]');
            if (backTrigger) {
                event.preventDefault();
                nav.querySelectorAll('[data-mobile-nav-panel]').forEach((panel) => {
                    if (panel.dataset.mobileNavPanel === 'root') {
                        panel.removeAttribute('hidden');
                    } else {
                        panel.setAttribute('hidden', '');
                    }
                });
            }
        });
    });
}

function bindPromoBar() {
    document.querySelectorAll('[data-promo-bar]').forEach((bar) => {
        if (!bar.dataset.promoDismissible) {
            return;
        }

        const message = bar.querySelector('.storefront-promo-bar__message')?.textContent?.trim() ?? '';
        const dismissKey = `${PROMO_DISMISS_KEY}:${message}`;

        if (message && localStorage.getItem(dismissKey) === '1') {
            bar.hidden = true;
        }

        bar.querySelector('[data-promo-dismiss]')?.addEventListener('click', () => {
            bar.hidden = true;
            if (message) {
                localStorage.setItem(dismissKey, '1');
            }
        });
    });
}

function bindStickyHeader() {
    const headers = document.querySelectorAll('[data-storefront-header]');
    if (headers.length === 0) {
        return;
    }

    let lastScrollY = window.scrollY;

    const update = () => {
        const scrolled = window.scrollY > 24;
        const compact = window.scrollY > 120 && window.scrollY > lastScrollY;

        headers.forEach((header) => {
            header.classList.toggle('storefront-header--scrolled', scrolled);
            header.classList.toggle('storefront-header--compact', compact);
        });

        lastScrollY = window.scrollY;
    };

    window.addEventListener('scroll', update, { passive: true });
    update();
}

function initHeader() {
    bindDrawers();
    bindMobileSearch();
    bindUserMenu();
    bindWishlistSync();
    bindMegaMenu();
    bindSearchOverlay();
    bindSearchAutocomplete();
    bindMobileNav();
    bindPromoBar();
    bindStickyHeader();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHeader);
} else {
    initHeader();
}
