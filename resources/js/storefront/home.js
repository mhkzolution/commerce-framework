function prefersReducedMotion() {
    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

function pageWidth(track) {
    return Math.max(track.clientWidth, 1);
}

function currentPage(track) {
    return Math.round(track.scrollLeft / pageWidth(track));
}

function pageCount(track) {
    const max = track.scrollWidth - track.clientWidth;
    if (max <= 4) {
        return 1;
    }

    return Math.max(1, Math.round(max / pageWidth(track)) + 1);
}

function goToPage(track, page, instant = false) {
    const maxPage = pageCount(track) - 1;
    const next = Math.min(Math.max(page, 0), maxPage);
    track.scrollTo({
        left: next * pageWidth(track),
        behavior: instant || prefersReducedMotion() ? 'auto' : 'smooth',
    });
}

function renderDots(root, track) {
    const dots = root.querySelector('[data-slider-dots]');
    if (! dots) {
        return;
    }

    const count = pageCount(track);
    dots.hidden = count <= 1;
    dots.replaceChildren();

    for (let index = 0; index < count; index += 1) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'storefront-slider__dot';
        button.setAttribute('aria-label', `Slide ${index + 1}`);
        button.addEventListener('click', () => goToPage(track, index));
        dots.append(button);
    }

    syncDots(root, track);
}

function syncDots(root, track) {
    const dots = root.querySelectorAll('[data-slider-dots] .storefront-slider__dot');
    const active = currentPage(track);
    dots.forEach((dot, index) => {
        dot.classList.toggle('is-active', index === active);
        dot.setAttribute('aria-current', index === active ? 'true' : 'false');
    });
}

function startSlider(root) {
    const track = root.querySelector('[data-slider-track]');
    if (! track || root.dataset.sliderReady === 'true') {
        return;
    }

    root.dataset.sliderReady = 'true';
    renderDots(root, track);

    track.addEventListener('scroll', () => syncDots(root, track), { passive: true });
    window.addEventListener('resize', () => renderDots(root, track));

    const autoplay = root.dataset.autoplay === 'true' && ! prefersReducedMotion();
    const loop = root.dataset.loop === 'true';
    const interval = Number(root.dataset.interval || 5000);
    let timer = null;

    const stop = () => {
        if (timer) {
            window.clearInterval(timer);
            timer = null;
        }
    };

    const play = () => {
        if (! autoplay) {
            return;
        }
        stop();
        timer = window.setInterval(() => {
            const last = pageCount(track) - 1;
            const next = currentPage(track) + 1;
            if (next > last) {
                goToPage(track, loop ? 0 : last);
            } else {
                goToPage(track, next);
            }
        }, interval);
    };

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', play);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', play);
    track.addEventListener('pointerdown', stop);

    play();
}

function initSlider(root) {
    if (root.dataset.lazy === 'true' && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                observer.disconnect();
                startSlider(root);
            }
        }, { rootMargin: '240px' });
        observer.observe(root);
        return;
    }

    startSlider(root);
}

function refreshSlider(root) {
    const track = root.querySelector('[data-slider-track]');
    if (! track) {
        return;
    }

    renderDots(root, track);
    track.scrollTo({ left: 0, behavior: 'auto' });
}

function initAccordion(root) {
    const items = [...root.querySelectorAll('[data-accordion-item]')];
    const triggers = items.map((item) => item.querySelector('[data-accordion-trigger]')).filter(Boolean);

    const setOpen = (item, open) => {
        const trigger = item.querySelector('[data-accordion-trigger]');
        const panel = item.querySelector('[data-accordion-panel]');
        if (! trigger || ! panel) {
            return;
        }

        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            panel.removeAttribute('hidden');
            requestAnimationFrame(() => panel.classList.add('is-open'));
        } else {
            panel.classList.remove('is-open');
            if (prefersReducedMotion()) {
                panel.setAttribute('hidden', '');
            } else {
                window.setTimeout(() => {
                    if (trigger.getAttribute('aria-expanded') !== 'true') {
                        panel.setAttribute('hidden', '');
                    }
                }, 220);
            }
        }
    };

    triggers.forEach((trigger, index) => {
        trigger.addEventListener('click', () => {
            const item = items[index];
            const expanded = trigger.getAttribute('aria-expanded') === 'true';
            items.forEach((other) => setOpen(other, false));
            setOpen(item, ! expanded);
        });

        trigger.addEventListener('keydown', (event) => {
            if (! ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                return;
            }

            event.preventDefault();
            let next = index;
            if (event.key === 'ArrowDown') {
                next = (index + 1) % triggers.length;
            } else if (event.key === 'ArrowUp') {
                next = (index - 1 + triggers.length) % triggers.length;
            } else if (event.key === 'Home') {
                next = 0;
            } else {
                next = triggers.length - 1;
            }
            triggers[next].focus();
        });
    });
}

function initArrivals(home) {
    const tabs = [...home.querySelectorAll('[data-arrival-tab]')];
    const slider = home.querySelector('[data-arrivals-slider]');
    const skeletons = home.querySelector('[data-arrivals-skeletons]');
    const url = home.dataset.arrivalsUrl;
    if (! slider || ! url) {
        return;
    }

    const track = slider.querySelector('[data-slider-track]');
    let abortController = null;

    const setLoading = (loading) => {
        if (skeletons) {
            skeletons.hidden = ! loading;
        }
        slider.hidden = loading;
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', async () => {
            if (tab.classList.contains('is-active')) {
                return;
            }

            tabs.forEach((other) => {
                const active = other === tab;
                other.classList.toggle('is-active', active);
                other.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            abortController?.abort();
            abortController = new AbortController();
            setLoading(true);

            const requestUrl = new URL(url, window.location.origin);
            const category = tab.dataset.category || '';
            if (category) {
                requestUrl.searchParams.set('category', category);
            }

            try {
                const response = await fetch(requestUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: abortController.signal,
                });
                const payload = await response.json();
                track.innerHTML = payload.html || '';
                refreshSlider(slider);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    track.innerHTML = '';
                }
            } finally {
                setLoading(false);
            }
        });
    });
}

function initHome() {
    document.querySelectorAll('[data-storefront-slider]').forEach(initSlider);
    document.querySelectorAll('[data-storefront-accordion]').forEach(initAccordion);
    document.querySelectorAll('[data-storefront-home]').forEach(initArrivals);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initHome);
} else {
    initHome();
}
