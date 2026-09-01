function setAuthMode(root, mode) {
    const modeInput = root.querySelector('[data-auth-mode-input]');
    if (modeInput) {
        modeInput.value = mode;
    }

    root.querySelectorAll('[data-auth-mode]').forEach((tab) => {
        const active = tab.dataset.authMode === mode;
        tab.classList.toggle('storefront-auth-tabs__tab--active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    root.querySelectorAll('[data-auth-panel]').forEach((panel) => {
        const active = panel.dataset.authPanel === mode;
        panel.classList.toggle('storefront-auth-form__panel--active', active);
        panel.setAttribute('aria-hidden', active ? 'false' : 'true');

        panel.querySelectorAll('input, select, textarea, button').forEach((field) => {
            if (field.hasAttribute('data-otp-send')) {
                return;
            }

            if (!active) {
                field.setAttribute('disabled', 'disabled');
            } else {
                field.removeAttribute('disabled');
            }
        });
    });
}

function initAuthModeTabs(root) {
    root.querySelectorAll('[data-auth-mode]').forEach((tab) => {
        tab.addEventListener('click', () => {
            setAuthMode(root, tab.dataset.authMode);
        });
    });

    const initial = root.querySelector('[data-auth-mode-input]')?.value || 'email';
    setAuthMode(root, initial);
}

function initPasswordToggles(root) {
    root.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const control = button.closest('.storefront-auth-field__control--password');
        const input = control?.querySelector('[data-password-input]');
        const showLabel = button.querySelector('[data-password-toggle-show]');
        const hideLabel = button.querySelector('[data-password-toggle-hide]');

        if (!input) {
            return;
        }

        button.addEventListener('click', () => {
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            if (showLabel && hideLabel) {
                showLabel.hidden = isPassword;
                hideLabel.hidden = !isPassword;
            }

            const showLabelText = button.dataset.showLabel || 'Show';
            const hideLabelText = button.dataset.hideLabel || 'Hide';
            button.setAttribute('aria-label', isPassword ? hideLabelText : showLabelText);
        });
    });
}

function csrfToken(root) {
    return root.querySelector('input[name="_token"]')?.value
        ?? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        ?? '';
}

function showOtpStatus(root, message, isError = false) {
    const status = root.querySelector('[data-otp-status]');
    if (!status) {
        return;
    }

    status.textContent = message;
    status.hidden = false;
    status.classList.toggle('storefront-auth-form__otp-status--error', isError);
}

function initOtpSend(root) {
    const form = root.querySelector('[data-auth-form]');
    const button = root.querySelector('[data-otp-send]');
    if (!form || !button) {
        return;
    }

    const sendUrl = form.dataset.otpSendUrl;
    const sendLabel = button.dataset.otpSendLabel || button.textContent?.trim() || 'Send code';
    const sendingLabel = button.dataset.otpSendingLabel || 'Sending…';

    button.addEventListener('click', async () => {
        if (button.disabled || !sendUrl) {
            return;
        }

        const identifier = root.querySelector('#login-otp-identifier')?.value?.trim() ?? '';
        if (identifier === '') {
            showOtpStatus(root, 'Please enter your email or phone.', true);
            return;
        }

        button.disabled = true;
        button.textContent = sendingLabel;

        try {
            const response = await fetch(sendUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(root),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ identifier }),
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message
                    ?? payload.errors?.identifier?.[0]
                    ?? 'Could not send the verification code.';
                showOtpStatus(root, message, true);
                return;
            }

            showOtpStatus(root, payload.message ?? 'Verification code sent.');
            window.setTimeout(() => {
                button.disabled = false;
                button.textContent = sendLabel;
            }, 30000);
        } catch {
            showOtpStatus(root, 'Could not send the verification code.', true);
            button.disabled = false;
            button.textContent = sendLabel;
        }
    });
}

function initRecaptchaForms(root) {
    const enabled = root.dataset.recaptchaEnabled === '1';
    const siteKey = root.dataset.recaptchaSiteKey;

    if (!enabled || !siteKey) {
        return;
    }

    const attachHandlers = () => {
        if (typeof window.grecaptcha === 'undefined') {
            window.setTimeout(attachHandlers, 100);
            return;
        }

        root.querySelectorAll('[data-auth-form]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const tokenInput = form.querySelector('[data-recaptcha-token]');
                if (!tokenInput || tokenInput.value) {
                    return;
                }

                event.preventDefault();

                window.grecaptcha.ready(() => {
                    window.grecaptcha.execute(siteKey, { action: 'submit' }).then((token) => {
                        tokenInput.value = token;
                        form.requestSubmit();
                    }).catch(() => {
                        showOtpStatus(root, 'Security verification failed. Please try again.', true);
                    });
                });
            });
        });
    };

    attachHandlers();
}

function initAuth() {
    const root = document.querySelector('[data-auth]');
    if (!root) {
        return;
    }

    initAuthModeTabs(root);
    initPasswordToggles(root);
    initOtpSend(root);
    initRecaptchaForms(root);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAuth);
} else {
    initAuth();
}
