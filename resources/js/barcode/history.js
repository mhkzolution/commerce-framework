document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bc-history-reprint]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.dataset.reprintUrl;
            if (!url) {
                return;
            }

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('reprint failed');
                }

                const payload = await response.json();
                sessionStorage.setItem('bc_reprint_payload', JSON.stringify(payload));
                window.location.href = '/admin/barcode';
            } catch {
                // silent fail — user can retry
            }
        });
    });
});
