function closeAllDropdowns(except = null) {
    document.querySelectorAll('[data-admin-dropdown]').forEach((dropdown) => {
        if (dropdown === except) return;
        dropdown.hidden = true;
        dropdown.previousElementSibling?.setAttribute('aria-expanded', 'false');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-admin-dropdown-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const dropdown = toggle.parentElement?.querySelector('[data-admin-dropdown]');
            if (!dropdown) return;

            const willOpen = dropdown.hidden;
            closeAllDropdowns();
            dropdown.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', () => closeAllDropdowns());
});
