function markDirty(form) {
    form.dataset.dirty = 'true';
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-admin-form]').forEach((form) => {
        form.addEventListener('input', () => markDirty(form));
        form.addEventListener('change', () => markDirty(form));
    });

    window.addEventListener('beforeunload', (event) => {
        const dirtyForm = document.querySelector('[data-admin-form][data-dirty="true"]');
        if (!dirtyForm) return;

        event.preventDefault();
        event.returnValue = '';
    });

    document.querySelectorAll('[data-admin-tabs]').forEach((tabs) => {
        const buttons = tabs.querySelectorAll('[data-admin-tab]');
        const panels = tabs.parentElement?.querySelectorAll('[data-admin-tab-panel]') ?? [];

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                const target = button.dataset.adminTab;
                buttons.forEach((node) => node.classList.toggle('is-active', node === button));
                panels.forEach((panel) => {
                    panel.hidden = panel.dataset.adminTabPanel !== target;
                });
            });
        });
    });
});
