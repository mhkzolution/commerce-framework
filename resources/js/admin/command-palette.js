function openPalette() {
    const palette = document.getElementById('admin-command-palette');
    const input = document.getElementById('admin-command-input');
    if (!palette || !input) return;

    palette.classList.add('is-open');
    palette.setAttribute('aria-hidden', 'false');
    input.value = '';
    filterCommands('');
    renderDynamicResults([]);
    input.focus();
}

function closePalette() {
    const palette = document.getElementById('admin-command-palette');
    if (!palette) return;

    palette.classList.remove('is-open');
    palette.setAttribute('aria-hidden', 'true');
}

function allCommandItems() {
    return [...document.querySelectorAll('[data-command-item]')];
}

function filterCommands(query) {
    const normalized = query.trim().toLowerCase();
    let visible = 0;

    allCommandItems().forEach((item) => {
        const haystack = (item.dataset.commandKeywords ?? '').toLowerCase();
        const show = normalized === '' || haystack.includes(normalized);
        item.hidden = !show;
        if (show) visible += 1;
    });

    const empty = document.getElementById('admin-command-empty');
    const dynamic = document.getElementById('admin-command-dynamic');
    const dynamicCount = dynamic?.querySelectorAll('[data-command-item]').length ?? 0;

    if (empty) {
        empty.hidden = visible !== 0 || dynamicCount > 0;
    }
}

function renderDynamicResults(results) {
    const container = document.getElementById('admin-command-dynamic');
    if (!container) return;

    container.innerHTML = '';

    results.forEach((result) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.commandItem = '';
        button.dataset.commandHref = result.url;
        button.dataset.commandKeywords = result.keywords ?? result.label.toLowerCase();
        button.className = 'cf-command-item flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm';
        button.innerHTML = `<span>${result.label}</span><span class="text-xs text-muted">${result.group ?? ''}</span>`;
        button.addEventListener('click', () => {
            if (result.url) window.location.href = result.url;
        });
        container.appendChild(button);
    });

    filterCommands(document.getElementById('admin-command-input')?.value ?? '');
}

let searchTimer = null;

function fetchSearchResults(query, url) {
    if (!url || query.trim() === '') {
        renderDynamicResults([]);
        return;
    }

    clearTimeout(searchTimer);
    searchTimer = setTimeout(async () => {
        try {
            const response = await fetch(`${url}?q=${encodeURIComponent(query.trim())}`);
            if (!response.ok) return;
            const data = await response.json();
            renderDynamicResults(data.results ?? []);
        } catch {
            renderDynamicResults([]);
        }
    }, 200);
}

function navigateHighlighted(direction) {
    const items = [...document.querySelectorAll('[data-command-item]:not([hidden])')];
    if (items.length === 0) return;

    const currentIndex = items.findIndex((item) => item.classList.contains('is-active'));
    let nextIndex = currentIndex + direction;
    if (nextIndex < 0) nextIndex = items.length - 1;
    if (nextIndex >= items.length) nextIndex = 0;

    items.forEach((item) => item.classList.remove('is-active'));
    items[nextIndex].classList.add('is-active');
    items[nextIndex].scrollIntoView({ block: 'nearest' });
}

function activateHighlighted() {
    const active = document.querySelector('[data-command-item].is-active:not([hidden])');
    if (!active) return;

    const href = active.dataset.commandHref;
    if (href) window.location.href = href;
}

function bindStaticCommandItems() {
    document.querySelectorAll('#admin-command-static [data-command-item]').forEach((item, index) => {
        item.addEventListener('mouseenter', () => {
            allCommandItems().forEach((node) => node.classList.remove('is-active'));
            item.classList.add('is-active');
        });

        item.addEventListener('click', () => {
            const href = item.dataset.commandHref;
            if (href) window.location.href = href;
        });

        if (index === 0) item.classList.add('is-active');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const palette = document.getElementById('admin-command-palette');
    const input = document.getElementById('admin-command-input');
    const globalSearch = document.getElementById('admin-global-search');
    const searchUrl = input?.dataset.searchUrl ?? globalSearch?.dataset.searchUrl ?? '';

    if (!palette || !input) return;

    bindStaticCommandItems();

    document.getElementById('admin-command-open')?.addEventListener('click', openPalette);

    globalSearch?.addEventListener('focus', openPalette);
    globalSearch?.addEventListener('input', (event) => {
        input.value = event.target.value;
        filterCommands(input.value);
        fetchSearchResults(input.value, searchUrl);
    });

    palette.addEventListener('click', (event) => {
        if (event.target === palette) closePalette();
    });

    input.addEventListener('input', (event) => {
        const value = event.target.value;
        if (globalSearch) globalSearch.value = value;
        filterCommands(value);
        fetchSearchResults(value, searchUrl);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            navigateHighlighted(1);
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            navigateHighlighted(-1);
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            activateHighlighted();
        }

        if (event.key === 'Escape') {
            closePalette();
        }
    });
});

document.addEventListener('keydown', (event) => {
    const isMeta = event.metaKey || event.ctrlKey;
    if (isMeta && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        openPalette();
    }

    if (event.key === 'Escape') {
        closePalette();
    }
});

export { openPalette, closePalette };
