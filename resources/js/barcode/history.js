document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bc-history-reprint]').forEach((button) => {
        button.addEventListener('click', () => {
            const url = button.dataset.reprintUrl;
            if (!url) {
                return;
            }

            window.location.href = url;
        });
    });
});
