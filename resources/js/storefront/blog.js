function initBlogSearch() {
    const root = document.querySelector('[data-blog]');
    if (!root) {
        return;
    }

    const form = root.querySelector('[data-blog-search-form]');
    const input = root.querySelector('[data-blog-search-input]');
    if (!form || !input) {
        return;
    }

    let timer = null;
    input.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => form.requestSubmit(), 360);
    });
}

function initTableOfContents() {
    const article = document.querySelector('[data-article]');
    if (!article) {
        return;
    }

    const links = article.querySelectorAll('[data-toc-link]');
    const headings = [...links].map((link) => document.getElementById(link.dataset.tocLink)).filter(Boolean);

    if (headings.length === 0) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }
            links.forEach((link) => {
                link.classList.toggle('storefront-toc__link--active', link.dataset.tocLink === entry.target.id);
            });
        });
    }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });

    headings.forEach((heading) => observer.observe(heading));
}

function initShareLinks() {
    document.querySelectorAll('[data-copy-link]').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.dataset.copyLink;
            const label = button.querySelector('[data-copy-label]');
            const original = label?.textContent;

            try {
                await navigator.clipboard.writeText(url);
            } catch {
                window.prompt('Copy link:', url);
            }

            button.classList.add('is-copied');
            if (label) {
                label.textContent = label.dataset.copiedLabel || 'Copied';
            }
            window.setTimeout(() => {
                button.classList.remove('is-copied');
                if (label && original) {
                    label.textContent = original;
                }
            }, 1600);
        });
    });
}

function initBlog() {
    initBlogSearch();
    initTableOfContents();
    initShareLinks();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBlog);
} else {
    initBlog();
}
