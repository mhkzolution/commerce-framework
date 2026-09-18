const DEBOUNCE_MS = 150;

function fold(value) {
    return String(value).toLocaleLowerCase('th');
}

function score(name, slug, query) {
    const foldedName = fold(name);
    const foldedSlug = fold(slug);
    const foldedQuery = fold(query);

    if (foldedName.startsWith(foldedQuery) || foldedSlug.startsWith(foldedQuery)) {
        return 0;
    }

    if (foldedName.includes(foldedQuery) || foldedSlug.includes(foldedQuery)) {
        return 1;
    }

    return -1;
}

function initBrands() {
    const root = document.querySelector('[data-brands]');
    if (!root) {
        return;
    }

    const input = root.querySelector('[data-brands-search-input]');
    const count = root.querySelector('[data-brands-search-count]');
    const alpha = root.querySelector('[data-brands-alpha]');
    const empty = root.querySelector('[data-brands-empty]');
    const directory = root.querySelector('[data-brands-directory]');
    const results = root.querySelector('[data-brands-results]');
    const cards = [...root.querySelectorAll('[data-brand-card]')];
    const sections = [...root.querySelectorAll('[data-brands-section]')];
    const letters = [...root.querySelectorAll('[data-brands-alpha-letter]')];
    const countTemplate = root.dataset.brandsCount || ':count';
    const idleCount = count ? count.textContent : '';
    const activeClass = 'storefront-shop-category-strip__link--active';

    if (!input || cards.length === 0) {
        return;
    }

    const items = cards.map((card) => {
        const item = card.closest('li') ?? card;

        return {
            card,
            item,
            home: item.parentElement,
        };
    });

    let timer = 0;
    let activeLetter = '';

    const restore = () => {
        items.forEach(({ item, home }) => {
            if (home) {
                home.append(item);
            }
            item.hidden = false;
        });
        if (results) {
            results.hidden = true;
            results.replaceChildren();
        }
        if (directory) {
            directory.hidden = false;
        }
        sections.forEach((section) => {
            section.hidden = false;
        });
        if (alpha) {
            alpha.hidden = false;
        }
        if (empty) {
            empty.hidden = true;
        }
        if (count) {
            count.hidden = false;
            count.textContent = idleCount;
        }
    };

    const setActiveLetter = (glyph) => {
        letters.forEach((letter) => {
            const active = letter.dataset.brandsAlphaLetter === glyph;
            letter.toggleAttribute('aria-current', active);
            letter.classList.toggle(activeClass, active);
        });
    };

    const applyFilter = () => {
        const query = input.value.trim();
        if (query === '') {
            restore();

            return;
        }

        const ranked = items
            .map((entry) => ({
                ...entry,
                rank: score(entry.card.dataset.brandName || '', entry.card.dataset.brandSlug || '', query),
            }))
            .filter((entry) => entry.rank >= 0)
            .sort((left, right) => left.rank - right.rank || (left.card.dataset.brandName || '').localeCompare(right.card.dataset.brandName || '', 'th'));

        items.forEach(({ item }) => {
            item.hidden = true;
        });

        if (directory) {
            directory.hidden = true;
        }
        if (alpha) {
            alpha.hidden = true;
        }

        if (results) {
            ranked.forEach(({ item }) => {
                item.hidden = false;
                results.append(item);
            });
            results.hidden = ranked.length === 0;
        }

        if (empty) {
            empty.hidden = ranked.length !== 0;
        }

        if (count) {
            count.hidden = false;
            count.textContent = countTemplate.replaceAll('__COUNT__', String(ranked.length));
        }
    };

    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(applyFilter, DEBOUNCE_MS);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        applyFilter();
        const remaining = items.filter(({ item }) => !item.hidden).map(({ card }) => card);
        if (remaining.length === 1) {
            window.location.assign(remaining[0].href);
        }
    });

    root.querySelectorAll('[data-brands-clear]').forEach((button) => {
        button.addEventListener('click', () => {
            input.value = '';
            restore();
            input.focus();
        });
    });

    letters.forEach((letter) => {
        letter.addEventListener('click', (event) => {
            const glyph = letter.dataset.brandsAlphaLetter;
            const section = root.querySelector(`[data-brands-section][data-brand-letter="${CSS.escape(glyph || '')}"]`);
            if (!section) {
                return;
            }

            event.preventDefault();
            activeLetter = glyph || '';
            setActiveLetter(activeLetter);
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    if ('IntersectionObserver' in window && sections.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            if (input.value.trim() !== '') {
                return;
            }

            const visible = entries
                .filter((entry) => entry.isIntersecting)
                .sort((left, right) => right.intersectionRatio - left.intersectionRatio)[0];

            if (!visible) {
                return;
            }

            const glyph = visible.target.getAttribute('data-brand-letter') || '';
            if (glyph === activeLetter) {
                return;
            }

            activeLetter = glyph;
            setActiveLetter(glyph);
            const current = letters.find((letter) => letter.dataset.brandsAlphaLetter === glyph);
            current?.scrollIntoView({ inline: 'nearest', block: 'nearest', behavior: 'auto' });
        }, {
            rootMargin: '-20% 0px -65% 0px',
            threshold: [0.1, 0.25],
        });

        sections.forEach((section) => observer.observe(section));
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBrands);
} else {
    initBrands();
}
