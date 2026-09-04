const initPasswordToggles = (root) => {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const field = button.closest('.storefront-password')?.querySelector('[data-password-input], input');
        if (!field) {
            return;
        }

        button.addEventListener('click', () => {
            const show = field.type === 'password';
            field.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
            button.textContent = show ? (button.dataset.hide || 'Hide') : (button.dataset.show || 'Show');
        });
    });
};

const initAuthForms = (root) => {
    root.querySelectorAll('[data-storefront-auth-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
            }
        });
    });
};

document.querySelectorAll('[data-storefront-auth]').forEach((root) => {
    initPasswordToggles(root);
    initAuthForms(root);
});
