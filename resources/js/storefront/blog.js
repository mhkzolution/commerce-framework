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

function initNewsletter() {
    document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const success = form.parentElement?.querySelector('[data-newsletter-success]');
            form.hidden = true;
            if (success) {
                success.hidden = false;
            }
        });
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

function initBlog() {
    initBlogSearch();
    initNewsletter();
    initTableOfContents();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBlog);
} else {
    initBlog();
}
