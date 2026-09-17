function fieldValue(form, name) {
    const field = form.querySelector(`[name="${name}"]`);
    return field ? String(field.value ?? '') : '';
}

function checkboxChecked(form, name) {
    return Boolean(form.querySelector(`[name="${name}"][type="checkbox"]`)?.checked);
}

function initPopupPreview() {
    const form = document.querySelector('[data-popup-form]');
    const root = form?.querySelector('[data-popup-preview]');
    if (!form || !root) {
        return;
    }

    const slide = root.querySelector('[data-preview-slide]');
    const image = root.querySelector('[data-preview-image]');
    const placeholder = root.querySelector('[data-preview-placeholder]');
    const copy = root.querySelector('[data-preview-copy]');
    const headlineEl = root.querySelector('[data-preview-headline]');
    const subheadlineEl = root.querySelector('[data-preview-subheadline]');
    const cta = root.querySelector('[data-preview-cta]');
    const closeBtn = root.querySelector('[data-preview-close]');
    const optout = root.querySelector('[data-preview-optout]');
    const statusChip = root.querySelector('[data-preview-status]');
    const activeChip = root.querySelector('[data-preview-active]');
    const meta = root.querySelector('[data-preview-meta]');
    const pickerPreview = form.querySelector('[data-picker-preview]');

    const labels = {
        draft: root.dataset.statusDraft || 'Draft',
        published: root.dataset.statusPublished || 'Published',
        active: root.dataset.activeLabel || 'Active',
        inactive: root.dataset.inactiveLabel || 'Inactive',
        delay: root.dataset.delayTemplate || 'Shows after :seconds seconds',
        autoClose: root.dataset.autoCloseTemplate || 'Closes after :seconds seconds',
    };

    let imageUrl = image?.getAttribute('src') || pickerPreview?.querySelector('img')?.src || '';

    const setHidden = (node, hidden) => {
        if (!node) {
            return;
        }
        node.hidden = hidden;
    };

    const render = () => {
        const type = fieldValue(form, 'popup_type') || 'image';
        const headline = fieldValue(form, 'headline').trim();
        const subheadline = fieldValue(form, 'subheadline').trim();
        const buttonText = fieldValue(form, 'button_text').trim();
        const status = fieldValue(form, 'status') || 'draft';
        const delay = Number.parseInt(fieldValue(form, 'show_delay') || '0', 10) || 0;
        const autoCloseRaw = fieldValue(form, 'auto_close').trim();
        const autoClose = Number.parseInt(autoCloseRaw || '0', 10) || 0;
        const closable = checkboxChecked(form, 'closable');
        const isActive = checkboxChecked(form, 'is_active');
        const hasCopy = headline !== '' || subheadline !== '' || buttonText !== '';
        const isImageOnly = type === 'image' || !hasCopy;

        if (slide) {
            slide.className = `cms-popup-preview__slide is-type-${type}${isImageOnly ? ' is-image-only' : ''}`;
        }

        if (imageUrl) {
            if (image) {
                image.src = imageUrl;
            }
            setHidden(image, false);
            setHidden(placeholder, true);
        } else {
            if (image) {
                image.removeAttribute('src');
            }
            setHidden(image, true);
            setHidden(placeholder, false);
        }

        setHidden(copy, isImageOnly);
        if (headlineEl) {
            headlineEl.textContent = headline;
        }
        setHidden(headlineEl, headline === '');
        if (subheadlineEl) {
            subheadlineEl.textContent = subheadline;
        }
        setHidden(subheadlineEl, subheadline === '');
        if (cta) {
            cta.textContent = buttonText;
        }
        setHidden(cta, isImageOnly || buttonText === '');
        setHidden(closeBtn, !closable);
        setHidden(optout, !closable);

        if (statusChip) {
            statusChip.textContent = status === 'published' ? labels.published : labels.draft;
        }
        if (activeChip) {
            activeChip.textContent = isActive ? labels.active : labels.inactive;
            activeChip.classList.toggle('is-off', !isActive);
        }
        if (meta) {
            const parts = [labels.delay.replace(':seconds', String(delay))];
            if (autoClose > 0) {
                parts.push(labels.autoClose.replace(':seconds', String(autoClose)));
            }
            meta.textContent = parts.join(' · ');
        }
    };

    const syncImageFromPicker = () => {
        imageUrl = pickerPreview?.querySelector('img')?.src || '';
        render();
    };

    form.addEventListener('input', render);
    form.addEventListener('change', render);

    if (pickerPreview && typeof MutationObserver === 'function') {
        new MutationObserver(syncImageFromPicker).observe(pickerPreview, {
            childList: true,
            subtree: true,
            attributes: true,
        });
    }

    render();
}

document.addEventListener('DOMContentLoaded', initPopupPreview);
