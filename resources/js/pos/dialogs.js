export function initDialogs() {
    document.addEventListener('click', async (event) => {
        const openTrigger = event.target.closest('[data-pos-dialog-open]');
        if (openTrigger) {
            const dialogId = openTrigger.dataset.posDialogOpen;

            if (dialogId === 'pos-payment-dialog') {
                const { openPaymentDialog } = await import('./checkout.js');
                openPaymentDialog();
                return;
            }

            const target = document.getElementById(dialogId);
            if (target) {
                target.hidden = false;
                target.querySelector('input, button, [tabindex]')?.focus();
            }
            return;
        }

        const closeTrigger = event.target.closest('[data-pos-dialog-close]');
        if (closeTrigger) {
            const dialog = closeTrigger.closest('[data-pos-dialog]');
            if (dialog) dialog.hidden = true;
        }
    });

    document.querySelectorAll('[data-pos-dialog]').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) dialog.hidden = true;
        });
    });
}

export function closeAllDialogs() {
    document.querySelectorAll('[data-pos-dialog]').forEach((dialog) => {
        dialog.hidden = true;
    });
}

export function openDialog(id) {
    const dialog = document.getElementById(id);
    if (dialog) {
        dialog.hidden = false;
        dialog.querySelector('input, button')?.focus();
    }
}
