const STORAGE_UNTIL = 'hide-until';
const STORAGE_SESSION = 'session';
const SEVEN_DAYS_MS = 7 * 24 * 60 * 60 * 1000;
const SLIDE_MS = 5500;

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

const readUntil = (key) => {
    try {
        const raw = window.localStorage.getItem(`${key}:${STORAGE_UNTIL}`);
        const until = raw ? Number.parseInt(raw, 10) : 0;

        return Number.isFinite(until) ? until : 0;
    } catch {
        return 0;
    }
};

const isSessionHidden = (key) => {
    try {
        return window.sessionStorage.getItem(`${key}:${STORAGE_SESSION}`) === '1';
    } catch {
        return false;
    }
};

const persistDismiss = (root, hideSevenDays) => {
    const key = root.dataset.storageKey || 'commerce:home-popup';

    try {
        if (hideSevenDays) {
            window.localStorage.setItem(`${key}:${STORAGE_UNTIL}`, String(Date.now() + SEVEN_DAYS_MS));
        } else {
            window.sessionStorage.setItem(`${key}:${STORAGE_SESSION}`, '1');
        }
    } catch {
        // Private mode can block storage; still close the overlay.
    }
};

const goTo = (root, index) => {
    const slides = [...root.querySelectorAll('[data-home-popup-slide]')];
    const track = root.querySelector('[data-home-popup-track]');
    if (!track || slides.length === 0) {
        return 0;
    }

    const next = ((index % slides.length) + slides.length) % slides.length;
    track.style.transform = `translateX(-${next * 100}%)`;
    root.dataset.index = String(next);
    root.querySelectorAll('[data-home-popup-dots] button').forEach((dot, dotIndex) => {
        dot.classList.toggle('is-active', dotIndex === next);
        dot.setAttribute('aria-current', dotIndex === next ? 'true' : 'false');
    });

    return next;
};

const renderDots = (root) => {
    const dots = root.querySelector('[data-home-popup-dots]');
    const slides = root.querySelectorAll('[data-home-popup-slide]');
    if (!dots || slides.length < 2) {
        return;
    }

    dots.replaceChildren();
    slides.forEach((_, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'storefront-home-popup__dot';
        button.setAttribute('aria-label', `Slide ${index + 1}`);
        button.addEventListener('click', () => goTo(root, index));
        dots.append(button);
    });
};

const closePopup = (root) => {
    const optout = root.querySelector('[data-home-popup-optout]');
    persistDismiss(root, Boolean(optout?.checked));
    root.hidden = true;
    document.body.classList.remove('is-home-popup-open');
};

export const initHomePopups = () => {
    document.querySelectorAll('[data-home-popup]').forEach((root) => {
        const key = root.dataset.storageKey || 'commerce:home-popup';
        if (readUntil(key) > Date.now() || isSessionHidden(key)) {
            return;
        }

        const delay = Math.max(0, Number.parseInt(root.dataset.showDelay || '0', 10)) * 1000;
        const autoClose = Math.max(0, Number.parseInt(root.dataset.autoClose || '0', 10)) * 1000;
        const slides = root.querySelectorAll('[data-home-popup-slide]');
        if (slides.length === 0) {
            return;
        }

        renderDots(root);
        goTo(root, 0);

        window.setTimeout(() => {
            root.hidden = false;
            document.body.classList.add('is-home-popup-open');
            goTo(root, 0);

            if (slides.length > 1 && !prefersReducedMotion()) {
                window.setInterval(() => {
                    if (root.hidden) {
                        return;
                    }

                    goTo(root, Number.parseInt(root.dataset.index || '0', 10) + 1);
                }, SLIDE_MS);
            }

            if (autoClose > 0) {
                window.setTimeout(() => {
                    if (!root.hidden) {
                        closePopup(root);
                    }
                }, autoClose);
            }
        }, delay);

        root.querySelector('[data-home-popup-close]')?.addEventListener('click', () => closePopup(root));
        root.querySelector('[data-home-popup-scrim]')?.addEventListener('click', () => {
            if (root.querySelector('[data-home-popup-close]')) {
                closePopup(root);
            }
        });
        root.querySelector('[data-home-popup-prev]')?.addEventListener('click', () => {
            goTo(root, Number.parseInt(root.dataset.index || '0', 10) - 1);
        });
        root.querySelector('[data-home-popup-next]')?.addEventListener('click', () => {
            goTo(root, Number.parseInt(root.dataset.index || '0', 10) + 1);
        });
    });
};
