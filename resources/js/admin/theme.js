const storageKey = 'commerce.admin.theme';

function systemPrefersDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyTheme(mode) {
    const root = document.documentElement;
    const resolved = mode === 'system' ? (systemPrefersDark() ? 'dark' : 'light') : mode;

    root.classList.toggle('dark', resolved === 'dark');
    root.dataset.theme = mode;
    localStorage.setItem(storageKey, mode);

    document.querySelectorAll('[data-theme-label]').forEach((element) => {
        element.textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
    });
}

function cycleTheme() {
    const current = localStorage.getItem(storageKey) ?? 'system';
    const order = ['light', 'dark', 'system'];
    const next = order[(order.indexOf(current) + 1) % order.length];
    applyTheme(next);
}

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(localStorage.getItem(storageKey) ?? 'system');

    document.getElementById('admin-theme-toggle')?.addEventListener('click', cycleTheme);

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if ((localStorage.getItem(storageKey) ?? 'system') === 'system') {
            applyTheme('system');
        }
    });
});

export { applyTheme };
