function openPalette() {
    const palette = document.getElementById('admin-command-palette');
    const input = document.getElementById('admin-command-input');
    if (!palette || !input) return;

    palette.classList.add('is-open');
    palette.setAttribute('aria-hidden', 'false');
    input.value = '';
    filterCommands('');
    input.focus();
}

function closePalette() {
    const palette = document.getElementById('admin-command-palette');
    if (!palette) return;

    palette.classList.remove('is-open');
    palette.setAttribute('aria-hidden', 'true');
}

function filterCommands(query) {
    const normalized = query.trim().toLowerCase();
    const items = document.querySelectorAll('[data-command-item]');
    let visible = 0;

    items.forEach((item) => {
        const haystack = (item.dataset.commandKeywords ?? '').toLowerCase();
        const show = normalized === '' || haystack.includes(normalized);
        item.hidden = !show;
        if (show) visible += 1;
    });

    const empty = document.getElementById('admin-command-empty');
    if (empty) empty.hidden = visible !== 0;
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

document.addEventListener('DOMContentLoaded', () => {
    const palette = document.getElementById('admin-command-palette');
    const input = document.getElementById('admin-command-input');
    if (!palette || !input) return;

    document.getElementById('admin-command-open')?.addEventListener('click', openPalette);

    palette.addEventListener('click', (event) => {
        if (event.target === palette) closePalette();
    });

    input.addEventListener('input', (event) => filterCommands(event.target.value));

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

    document.querySelectorAll('[data-command-item]').forEach((item, index) => {
        item.addEventListener('mouseenter', () => {
            document.querySelectorAll('[data-command-item]').forEach((node) => node.classList.remove('is-active'));
            item.classList.add('is-active');
        });

        item.addEventListener('click', () => {
            const href = item.dataset.commandHref;
            if (href) window.location.href = href;
        });

        if (index === 0) item.classList.add('is-active');
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
