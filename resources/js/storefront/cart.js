const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const updateCartCount = (count) => {
    document.querySelectorAll('[data-cart-count]').forEach((badge) => {
        badge.textContent = String(count);
        badge.hidden = count < 1;
    });
    document.querySelectorAll('[data-mini-cart-count]').forEach((el) => {
        el.textContent = String(count);
    });
};

const requestCart = async (form) => {
    const response = await fetch(form.action, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: new FormData(form),
        credentials: 'same-origin',
    });

    if (!response.ok) {
        return null;
    }

    return response.json();
};

const bindSteppers = (scope = document) => {
    scope.querySelectorAll('form[data-qty-stepper][data-cart-qty]').forEach((form) => {
        const input = form.querySelector('input[name="quantity"]');

        if (!input) {
            return;
        }

        const submitQty = () => {
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();

                return;
            }

            form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
        };

        form.querySelector('[data-qty-dec]')?.addEventListener('click', () => {
            input.value = String(Math.max(0, (parseInt(input.value, 10) || 0) - 1));
            submitQty();
        });
        form.querySelector('[data-qty-inc]')?.addEventListener('click', () => {
            const max = parseInt(input.max || '99', 10);
            input.value = String(Math.min(max, (parseInt(input.value, 10) || 0) + 1));
            submitQty();
        });
    });
};

const bindAjaxCartForms = (scope = document) => {
    scope.querySelectorAll('[data-cart-qty], [data-cart-remove]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const json = await requestCart(form);

            if (!json) {
                form.submit();

                return;
            }

            const cart = json.data || json;
            updateCartCount(cart.item_count || 0);
            window.location.reload();
        });
    });
};

bindSteppers();
bindAjaxCartForms();
