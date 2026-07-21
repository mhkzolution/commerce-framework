const storageKey = 'commerce.admin.sidebar.collapsed';
const groupStorageKey = 'commerce.admin.sidebar.groups';

function readJson(key, fallback) {
    try {
        const value = localStorage.getItem(key);
        return value ? JSON.parse(value) : fallback;
    } catch {
        return fallback;
    }
}

function writeJson(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function setCollapsed(collapsed) {
    const sidebar = document.getElementById('admin-sidebar');
    if (!sidebar) return;

    sidebar.classList.toggle('is-collapsed', collapsed);
    sidebar.dataset.collapsed = collapsed ? 'true' : 'false';
    localStorage.setItem(storageKey, collapsed ? '1' : '0');
}

function toggleMobileSidebar(open) {
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.getElementById('admin-sidebar-backdrop');
    if (!sidebar || !backdrop) return;

    const shouldOpen = open ?? !sidebar.classList.contains('is-mobile-open');
    sidebar.classList.toggle('is-mobile-open', shouldOpen);
    backdrop.classList.toggle('is-visible', shouldOpen);
}

function toggleGroup(groupId) {
    const groups = readJson(groupStorageKey, {});
    groups[groupId] = !groups[groupId];
    writeJson(groupStorageKey, groups);

    const panel = document.querySelector(`[data-nav-group-panel="${groupId}"]`);
    const trigger = document.querySelector(`[data-nav-group-trigger="${groupId}"]`);
    if (!panel || !trigger) return;

    const isOpen = groups[groupId] !== false;
    panel.hidden = !isOpen;
    trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
}

function filterMenu(query) {
    const normalized = query.trim().toLowerCase();
    document.querySelectorAll('[data-nav-item]').forEach((item) => {
        const label = (item.dataset.navLabel ?? '').toLowerCase();
        item.hidden = normalized !== '' && !label.includes(normalized);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('admin-sidebar');
    if (sidebar) {
        const collapsed = localStorage.getItem(storageKey) === '1';
        setCollapsed(collapsed);
    }

    document.querySelectorAll('[data-nav-group-trigger]').forEach((trigger) => {
        const groupId = trigger.dataset.navGroupTrigger;
        const groups = readJson(groupStorageKey, {});
        const panel = document.querySelector(`[data-nav-group-panel="${groupId}"]`);
        const defaultOpen = trigger.dataset.defaultOpen === 'true';
        const isOpen = groups[groupId] ?? defaultOpen;
        if (panel) panel.hidden = !isOpen;
        trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    document.getElementById('admin-sidebar-toggle')?.addEventListener('click', () => {
        if (window.innerWidth < 1024) {
            toggleMobileSidebar();
            return;
        }

        const sidebar = document.getElementById('admin-sidebar');
        setCollapsed(sidebar?.classList.contains('is-collapsed') !== true);
    });

    document.getElementById('admin-sidebar-backdrop')?.addEventListener('click', () => toggleMobileSidebar(false));

    document.querySelectorAll('[data-nav-group-trigger]').forEach((trigger) => {
        trigger.addEventListener('click', () => toggleGroup(trigger.dataset.navGroupTrigger));
    });

    document.getElementById('admin-menu-search')?.addEventListener('input', (event) => {
        filterMenu(event.target.value);
    });
});

export { toggleMobileSidebar };
