function initPasswordToggles(root) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const control = button.closest('.storefront-auth-field__control--password');
        const input = control?.querySelector('[data-password-input]');
        const showIcon = button.querySelector('[data-password-toggle-show]');
        const hideIcon = button.querySelector('[data-password-toggle-hide]');

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            if (showIcon && hideIcon) {
                showIcon.hidden = isPassword;
                hideIcon.hidden = !isPassword;
            }

            const showLabel = button.dataset.showLabel || 'Show';
            const hideLabel = button.dataset.hideLabel || 'Hide';
            button.setAttribute('aria-label', isPassword ? hideLabel : showLabel);
        });
    });
}

function initAuthForms(root) {
    root.querySelectorAll('[data-auth-form]').forEach((form) => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('[type="submit"]');
            if (submit) {
                submit.disabled = true;
            }
        });
    });
}

function initAuth() {
    const root = document.querySelector('[data-auth]');
    if (!root) {
        return;
    }

    initPasswordToggles(root);
    initAuthForms(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuth);
} else {
    initAuth();
}
