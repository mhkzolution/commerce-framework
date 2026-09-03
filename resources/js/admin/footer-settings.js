function clone(value) {
    return JSON.parse(JSON.stringify(value ?? {}));
}

function get(object, path) {
    return path.split('.').reduce((value, key) => (value == null ? undefined : value[key]), object);
}

function set(object, path, value) {
    const keys = path.split('.');
    let cursor = object;

    keys.forEach((key, index) => {
        if (index === keys.length - 1) {
            cursor[key] = value;
            return;
        }

        if (typeof cursor[key] !== 'object' || cursor[key] === null) {
            cursor[key] = {};
        }

        cursor = cursor[key];
    });
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function kebabCase(value) {
    return String(value ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function buttonMarkup(action, label, modifier = '') {
    const classes = ['footer-settings__mini-btn'];
    if (modifier) {
        classes.push(`footer-settings__mini-btn--${modifier}`);
    }

    return `<button type="button" class="${classes.join(' ')}" data-footer-action="${action}">${escapeHtml(label)}</button>`;
}

function parseDatasetJson(node, key, fallback) {
    try {
        return JSON.parse(node.dataset[key] || JSON.stringify(fallback));
    } catch {
        return fallback;
    }
}

function sectionLabel(section, editor) {
    const template = (editor.templates || []).find((item) => item.type === section.type);
    return template?.label || section.type;
}

function uniqueSectionId(type, sections) {
    const base = kebabCase(type.replaceAll('_', '-')) || 'section';
    const used = new Set((sections || []).map((section) => section.id));

    if (!used.has(base)) {
        return base;
    }

    let index = 2;
    while (used.has(`${base}-${index}`)) {
        index += 1;
    }

    return `${base}-${index}`;
}

function normalizeSectionIdInput(section, sections) {
    const desired = kebabCase(section.id);
    if (!desired) {
        return section.id;
    }

    const taken = new Set(sections.filter((candidate) => candidate !== section).map((candidate) => candidate.id));
    if (!taken.has(desired)) {
        return desired;
    }

    let index = 2;
    let candidate = `${desired}-${index}`;
    while (taken.has(candidate)) {
        index += 1;
        candidate = `${desired}-${index}`;
    }

    return candidate;
}

function createSectionFromTemplate(template, sections) {
    return {
        id: uniqueSectionId(template.type, sections),
        type: template.type,
        enabled: true,
        visibility: {
            guest: true,
            authenticated: true,
        },
        settings: clone(template.default_settings || {}),
    };
}

function setDirtyState(state, form) {
    state.isDirty = JSON.stringify(state.config) !== JSON.stringify(state.initialConfig);
    form.querySelector('[data-footer-save]')?.toggleAttribute('disabled', !state.isDirty);
    form.querySelector('[data-footer-discard]')?.toggleAttribute('disabled', !state.isDirty);

    const indicator = form.querySelector('[data-footer-dirty-indicator]');
    if (!indicator) {
        return;
    }

    indicator.textContent = state.isDirty ? state.i18n.dirty : state.i18n.saved;
    indicator.classList.toggle('is-dirty', state.isDirty);
}

function currentSection(state) {
    return (state.config.sections || []).find((section) => section.id === state.selectedSectionId) || null;
}

function hiddenReasonMap(state) {
    return new Map((state.previewMeta.hidden_reasons || []).map((item) => [item.section_id, item.reason]));
}

function sectionStatus(section, state) {
    const reason = hiddenReasonMap(state).get(section.id);

    if (!section.enabled || reason === 'disabled' || reason === 'not_rendered') {
        return { label: state.i18n.status_hidden, tone: 'muted' };
    }

    if (!reason) {
        return { label: state.i18n.status_visible, tone: 'success' };
    }

    if (['empty_cms_selection', 'empty_copyright_text', 'brand_content_disabled'].includes(reason)) {
        return { label: state.i18n.status_empty, tone: 'warning' };
    }

    if (['empty_navigation_links', 'cms_pages_unavailable', 'empty_social_links', 'brand_content_unavailable'].includes(reason)) {
        return { label: state.i18n.status_no_sources, tone: 'warning' };
    }

    if (['marketplace_unavailable', 'plan_restricted'].includes(reason)) {
        return { label: state.i18n.status_module_disabled, tone: 'danger' };
    }

    return { label: state.i18n.status_hidden, tone: 'muted' };
}

function syncHiddenInput(form, state) {
    form.querySelector('[data-footer-config-input]')?.setAttribute('value', JSON.stringify(state.config));
    const input = form.querySelector('[data-footer-config-input]');
    if (input) {
        input.value = JSON.stringify(state.config);
    }
}

function renderTemplateList(form, state) {
    const root = form.querySelector('[data-footer-template-list]');
    if (!root) {
        return;
    }

    root.innerHTML = (state.editor.templates || []).map((template) => {
        const blocked = template.supports_multiple === false
            && (state.config.sections || []).some((section) => section.type === template.type);

        return `
            <button
                type="button"
                class="footer-settings__template${blocked ? ' is-disabled' : ''}"
                data-footer-template="${escapeHtml(template.type)}"
                ${blocked ? 'disabled' : ''}
            >
                <strong>${escapeHtml(template.label)}</strong>
                <span>${blocked ? 'Already added' : 'Add section'}</span>
            </button>
        `;
    }).join('');

    root.querySelectorAll('[data-footer-template]').forEach((button) => {
        button.addEventListener('click', () => {
            const template = (state.editor.templates || []).find((item) => item.type === button.dataset.footerTemplate);
            if (!template) {
                return;
            }

            const section = createSectionFromTemplate(template, state.config.sections || []);
            state.config.sections.push(section);
            state.selectedSectionId = section.id;
            sync(form, state, { preview: true });
        });
    });
}

function renderSectionList(form, state) {
    const root = form.querySelector('[data-footer-section-list]');
    if (!root) {
        return;
    }

    root.innerHTML = (state.config.sections || []).map((section, index) => {
        const status = sectionStatus(section, state);
        const selected = section.id === state.selectedSectionId;

        return `
            <article
                class="footer-settings__section-card${selected ? ' is-selected' : ''}"
                data-footer-section-card="${escapeHtml(section.id)}"
                data-footer-index="${index}"
                draggable="true"
            >
                <div class="footer-settings__section-main">
                    <button type="button" class="footer-settings__section-select" data-footer-select-section="${escapeHtml(section.id)}">
                        <span class="footer-settings__section-title">${escapeHtml(sectionLabel(section, state.editor))}</span>
                        <span class="footer-settings__section-meta">${escapeHtml(section.id)}</span>
                    </button>
                    <span class="footer-settings__badge footer-settings__badge--${status.tone}">${escapeHtml(status.label)}</span>
                </div>
                <div class="footer-settings__section-controls">
                    <label class="footer-settings__toggle footer-settings__toggle--compact">
                        <input type="checkbox" data-footer-toggle-section="${escapeHtml(section.id)}" ${section.enabled ? 'checked' : ''}>
                        <span>${section.enabled ? escapeHtml(state.i18n.enabled) : escapeHtml(state.i18n.disabled)}</span>
                    </label>
                    <div class="footer-settings__section-actions">
                        ${buttonMarkup(`move-up:${section.id}`, state.i18n.move_up)}
                        ${buttonMarkup(`move-down:${section.id}`, state.i18n.move_down)}
                        ${buttonMarkup(`remove:${section.id}`, state.i18n.remove, 'danger')}
                    </div>
                </div>
            </article>
        `;
    }).join('');

    root.querySelectorAll('[data-footer-select-section]').forEach((button) => {
        button.addEventListener('click', () => {
            state.selectedSectionId = button.dataset.footerSelectSection;
            sync(form, state, { preview: false });
        });
    });

    root.querySelectorAll('[data-footer-toggle-section]').forEach((input) => {
        input.addEventListener('change', () => {
            const section = (state.config.sections || []).find((item) => item.id === input.dataset.footerToggleSection);
            if (!section) {
                return;
            }

            section.enabled = input.checked;
            sync(form, state, { preview: true });
        });
    });

    root.querySelectorAll('[data-footer-action]').forEach((button) => {
        button.addEventListener('click', () => {
            const [action, sectionId] = (button.dataset.footerAction || '').split(':');
            const sections = state.config.sections || [];
            const index = sections.findIndex((item) => item.id === sectionId);

            if (index < 0) {
                return;
            }

            if (action === 'move-up' && index > 0) {
                [sections[index - 1], sections[index]] = [sections[index], sections[index - 1]];
            } else if (action === 'move-down' && index < sections.length - 1) {
                [sections[index + 1], sections[index]] = [sections[index], sections[index + 1]];
            } else if (action === 'remove') {
                sections.splice(index, 1);
                if (state.selectedSectionId === sectionId) {
                    state.selectedSectionId = sections[Math.max(0, index - 1)]?.id || sections[0]?.id || null;
                }
            }

            sync(form, state, { preview: true });
        });
    });

    root.querySelectorAll('[data-footer-section-card]').forEach((card) => {
        card.addEventListener('dragstart', (event) => {
            state.dragIndex = Number(card.dataset.footerIndex);
            event.dataTransfer?.setData('text/plain', String(state.dragIndex));
        });

        card.addEventListener('dragover', (event) => {
            event.preventDefault();
        });

        card.addEventListener('drop', (event) => {
            event.preventDefault();
            const fromIndex = Number(event.dataTransfer?.getData('text/plain') ?? state.dragIndex);
            const toIndex = Number(card.dataset.footerIndex);
            if (Number.isNaN(fromIndex) || Number.isNaN(toIndex) || fromIndex === toIndex) {
                return;
            }

            const moved = state.config.sections.splice(fromIndex, 1)[0];
            state.config.sections.splice(toIndex, 0, moved);
            sync(form, state, { preview: true });
        });
    });
}

function availablePageMap(state) {
    return new Map((state.editor.sources?.cms_pages || []).map((page) => [Number(page.id), page]));
}

function renderCmsEditor(section, state) {
    const pageMap = availablePageMap(state);
    const selectedIds = Array.isArray(section.settings.page_ids) ? section.settings.page_ids.map(Number) : [];
    const availablePages = (state.editor.sources?.cms_pages || []).filter((page) => !selectedIds.includes(Number(page.id)));
    const selectedPages = selectedIds.map((id) => pageMap.get(Number(id))).filter(Boolean);

    return `
        <div class="footer-settings__field">
            <span class="footer-settings__label">${escapeHtml(state.i18n.cms_available)}</span>
            <div class="footer-settings__picker">
                <select class="cf-input" data-footer-cms-page-select>
                    <option value="">Select a page</option>
                    ${availablePages.map((page) => `<option value="${page.id}">${escapeHtml(page.title)}</option>`).join('')}
                </select>
                ${buttonMarkup('cms-add', 'Add', 'primary')}
            </div>
        </div>

        <div class="footer-settings__field">
            <span class="footer-settings__label">${escapeHtml(state.i18n.cms_selected)}</span>
            <div class="footer-settings__ordered-list" data-footer-cms-selected>
                ${selectedPages.length ? selectedPages.map((page, index) => `
                    <div class="footer-settings__ordered-item">
                        <div>
                            <strong>${escapeHtml(page.title)}</strong>
                            <div class="footer-settings__microcopy">${escapeHtml(page.slug)}</div>
                        </div>
                        <div class="footer-settings__section-actions">
                            ${buttonMarkup(`cms-up:${index}`, state.i18n.move_up)}
                            ${buttonMarkup(`cms-down:${index}`, state.i18n.move_down)}
                            ${buttonMarkup(`cms-remove:${page.id}`, state.i18n.remove, 'danger')}
                        </div>
                    </div>
                `).join('') : `<p class="footer-settings__empty">${escapeHtml(state.i18n.cms_empty)}</p>`}
            </div>
        </div>
    `;
}

function renderSocialEditor(state) {
    const configured = state.editor.sources?.social?.configured || [];
    const missing = state.editor.sources?.social?.missing || [];
    const siteIdentityUrl = state.editor.routes?.site_identity;

    return `
        <div class="footer-settings__read-only">
            <p>${escapeHtml(state.i18n.social_read_only)}</p>
            ${siteIdentityUrl ? `<p><a href="${escapeHtml(siteIdentityUrl)}" class="footer-settings__link">${escapeHtml(state.i18n.open_site_identity)}</a></p>` : ''}
        </div>
        <div class="footer-settings__source-grid">
            <div>
                <span class="footer-settings__label">Configured</span>
                <ul class="footer-settings__plain-list">
                    ${configured.length ? configured.map((item) => `<li>${escapeHtml(item.label)}</li>`).join('') : '<li>None</li>'}
                </ul>
            </div>
            <div>
                <span class="footer-settings__label">Missing</span>
                <ul class="footer-settings__plain-list">
                    ${missing.length ? missing.map((item) => `<li>${escapeHtml(item.label)}</li>`).join('') : '<li>None</li>'}
                </ul>
            </div>
        </div>
    `;
}

function renderSectionDetail(form, state) {
    const root = form.querySelector('[data-footer-detail-panel]');
    if (!root) {
        return;
    }

    const section = currentSection(state);
    if (!section) {
        root.innerHTML = `<p class="footer-settings__empty">${escapeHtml(state.i18n.select_section)}</p>`;
        return;
    }

    const status = sectionStatus(section, state);
    const navSource = (state.editor.sources?.navigation || []).find((item) => item.value === section.settings?.source);
    const detailMeta = [];

    if (section.type === 'navigation') {
        detailMeta.push(`Source: ${section.settings?.source || 'main'}`);
        detailMeta.push(`Max links: ${section.settings?.max_links || 0}`);
        if (typeof navSource?.count === 'number') {
            detailMeta.push(`Available: ${navSource.count}`);
        }
    } else if (section.type === 'cms') {
        detailMeta.push(`Selected pages: ${(section.settings?.page_ids || []).length}`);
    } else if (section.type === 'social') {
        detailMeta.push(`Configured links: ${(state.editor.sources?.social?.configured || []).length}`);
    }

    root.innerHTML = `
        <div class="footer-settings__detail-header">
            <div>
                <div class="footer-settings__detail-title-row">
                    <h3 class="footer-settings__detail-title">${escapeHtml(sectionLabel(section, state.editor))}</h3>
                    <span class="footer-settings__badge footer-settings__badge--${status.tone}">${escapeHtml(status.label)}</span>
                </div>
                ${detailMeta.length ? `<p class="footer-settings__microcopy">${escapeHtml(detailMeta.join(' · '))}</p>` : ''}
            </div>
        </div>

        <div class="footer-settings__grid">
            <div class="footer-settings__field">
                <label class="footer-settings__label" for="footer-section-id">Section ID</label>
                <input id="footer-section-id" class="cf-input" type="text" value="${escapeHtml(section.id)}" data-footer-section-field="id">
            </div>
            <div class="footer-settings__field">
                <label class="footer-settings__label" for="footer-section-type">Section type</label>
                <input id="footer-section-type" class="cf-input" type="text" value="${escapeHtml(section.type)}" disabled>
            </div>
        </div>

        <label class="footer-settings__toggle">
            <input type="checkbox" data-footer-section-field="enabled" ${section.enabled ? 'checked' : ''}>
            <span>Enabled</span>
        </label>

        <div class="footer-settings__subsection">
            <span class="footer-settings__label">Visibility</span>
            <div class="footer-settings__checks">
                <label class="footer-settings__toggle footer-settings__toggle--compact">
                    <input type="checkbox" data-footer-section-visibility="guest" ${(section.visibility?.guest ?? true) ? 'checked' : ''}>
                    <span>${escapeHtml(state.i18n.guest_visibility)}</span>
                </label>
                <label class="footer-settings__toggle footer-settings__toggle--compact">
                    <input type="checkbox" data-footer-section-visibility="authenticated" ${(section.visibility?.authenticated ?? true) ? 'checked' : ''}>
                    <span>${escapeHtml(state.i18n.auth_visibility)}</span>
                </label>
            </div>
        </div>

        <div class="footer-settings__subsection" data-footer-driver-fields>
            ${renderDriverFields(section, state)}
        </div>
    `;

    root.querySelector('[data-footer-section-field="id"]')?.addEventListener('change', (event) => {
        section.id = normalizeSectionIdInput({ ...section, id: event.target.value }, state.config.sections || []);
        state.selectedSectionId = section.id;
        sync(form, state, { preview: true });
    });

    root.querySelector('[data-footer-section-field="enabled"]')?.addEventListener('change', (event) => {
        section.enabled = event.target.checked;
        sync(form, state, { preview: true });
    });

    root.querySelectorAll('[data-footer-section-visibility]').forEach((input) => {
        input.addEventListener('change', () => {
            section.visibility = section.visibility || {};
            section.visibility[input.dataset.footerSectionVisibility] = input.checked;
            sync(form, state, { preview: true });
        });
    });

    bindDriverFieldEvents(root, form, state, section);
}

function renderDriverFields(section, state) {
    if (section.type === 'brand') {
        return `
            <span class="footer-settings__label">${escapeHtml(state.i18n.brand_group)}</span>
            <div class="footer-settings__checks">
                <label class="footer-settings__toggle footer-settings__toggle--compact">
                    <input type="checkbox" data-footer-setting="show_logo" ${section.settings?.show_logo !== false ? 'checked' : ''}>
                    <span>${escapeHtml(state.i18n.show_logo)}</span>
                </label>
                <label class="footer-settings__toggle footer-settings__toggle--compact">
                    <input type="checkbox" data-footer-setting="show_store_name" ${section.settings?.show_store_name !== false ? 'checked' : ''}>
                    <span>${escapeHtml(state.i18n.show_store_name)}</span>
                </label>
                <label class="footer-settings__toggle footer-settings__toggle--compact">
                    <input type="checkbox" data-footer-setting="show_description" ${section.settings?.show_description !== false ? 'checked' : ''}>
                    <span>${escapeHtml(state.i18n.show_description)}</span>
                </label>
            </div>
        `;
    }

    if (section.type === 'navigation') {
        const sources = state.editor.sources?.navigation || [];
        const navigationUrl = state.editor.routes?.navigation;

        return `
            <span class="footer-settings__label">${escapeHtml(state.i18n.nav_group)}</span>
            <div class="footer-settings__grid">
                <div class="footer-settings__field">
                    <label class="footer-settings__label" for="footer-nav-source">${escapeHtml(state.i18n.nav_source)}</label>
                    <select id="footer-nav-source" class="cf-input" data-footer-setting="source">
                        ${sources.map((source) => `<option value="${escapeHtml(source.value)}" ${source.value === section.settings?.source ? 'selected' : ''}>${escapeHtml(source.label)}</option>`).join('')}
                    </select>
                </div>
                <div class="footer-settings__field">
                    <label class="footer-settings__label" for="footer-nav-max">${escapeHtml(state.i18n.nav_max_links)}</label>
                    <input id="footer-nav-max" class="cf-input" type="number" min="1" max="20" value="${escapeHtml(section.settings?.max_links ?? 6)}" data-footer-setting="max_links" data-footer-type="integer">
                </div>
                <div class="footer-settings__field">
                    <label class="footer-settings__label" for="footer-nav-visibility">${escapeHtml(state.i18n.nav_visibility_mode)}</label>
                    <select id="footer-nav-visibility" class="cf-input" data-footer-setting="visibility_mode">
                        <option value="footer_enabled_only" ${section.settings?.visibility_mode === 'footer_enabled_only' ? 'selected' : ''}>${escapeHtml(state.i18n.visibility_footer_only)}</option>
                        <option value="public_only" ${section.settings?.visibility_mode === 'public_only' ? 'selected' : ''}>${escapeHtml(state.i18n.visibility_public_only)}</option>
                        <option value="all" ${section.settings?.visibility_mode === 'all' ? 'selected' : ''}>${escapeHtml(state.i18n.visibility_all)}</option>
                    </select>
                </div>
                <div class="footer-settings__field">
                    <span class="footer-settings__label">${escapeHtml(state.i18n.nav_count)}</span>
                    <p class="footer-settings__microcopy">${escapeHtml(String(sources.find((source) => source.value === section.settings?.source)?.count ?? 0))}</p>
                </div>
            </div>
            ${navigationUrl ? `<p><a href="${escapeHtml(navigationUrl)}" class="footer-settings__link">${escapeHtml(state.i18n.open_navigation)}</a></p>` : ''}
        `;
    }

    if (section.type === 'cms') {
        return renderCmsEditor(section, state);
    }

    if (section.type === 'social') {
        return renderSocialEditor(state);
    }

    if (section.type === 'copyright') {
        return `
            <div class="footer-settings__field">
                <label class="footer-settings__label" for="footer-copyright-template">${escapeHtml(state.i18n.copyright_template)}</label>
                <input id="footer-copyright-template" class="cf-input" type="text" value="${escapeHtml(section.settings?.template ?? '')}" data-footer-setting="template">
            </div>
        `;
    }

    if (section.type === 'marketplace') {
        return `<p class="footer-settings__microcopy">${escapeHtml(state.i18n.driver_note_marketplace)}</p>`;
    }

    if (section.type === 'powered_by') {
        return `<p class="footer-settings__microcopy">${escapeHtml(state.i18n.driver_note_powered_by)}</p>`;
    }

    return '';
}

function bindDriverFieldEvents(root, form, state, section) {
    root.querySelectorAll('[data-footer-setting]').forEach((input) => {
        const eventName = input.tagName === 'SELECT' || input.type === 'number' || input.type === 'text' ? 'input' : 'change';
        input.addEventListener(eventName, () => {
            section.settings = section.settings || {};
            const value = input.type === 'checkbox'
                ? input.checked
                : (input.dataset.footerType === 'integer' ? Number(input.value || 0) : input.value);
            section.settings[input.dataset.footerSetting] = value;
            sync(form, state, { preview: true });
        });
    });

    root.querySelector('[data-footer-action="cms-add"]')?.addEventListener('click', () => {
        const select = root.querySelector('[data-footer-cms-page-select]');
        const pageId = Number(select?.value || 0);
        if (!pageId) {
            return;
        }

        section.settings.page_ids = Array.isArray(section.settings.page_ids) ? section.settings.page_ids : [];
        if (!section.settings.page_ids.includes(pageId)) {
            section.settings.page_ids.push(pageId);
        }
        sync(form, state, { preview: true });
    });

    root.querySelectorAll('[data-footer-action^="cms-up:"]').forEach((button) => {
        button.addEventListener('click', () => {
            const index = Number((button.dataset.footerAction || '').split(':')[1]);
            if (index <= 0) {
                return;
            }

            [section.settings.page_ids[index - 1], section.settings.page_ids[index]] = [section.settings.page_ids[index], section.settings.page_ids[index - 1]];
            sync(form, state, { preview: true });
        });
    });

    root.querySelectorAll('[data-footer-action^="cms-down:"]').forEach((button) => {
        button.addEventListener('click', () => {
            const index = Number((button.dataset.footerAction || '').split(':')[1]);
            if (index >= section.settings.page_ids.length - 1) {
                return;
            }

            [section.settings.page_ids[index + 1], section.settings.page_ids[index]] = [section.settings.page_ids[index], section.settings.page_ids[index + 1]];
            sync(form, state, { preview: true });
        });
    });

    root.querySelectorAll('[data-footer-action^="cms-remove:"]').forEach((button) => {
        button.addEventListener('click', () => {
            const pageId = Number((button.dataset.footerAction || '').split(':')[1]);
            section.settings.page_ids = (section.settings.page_ids || []).filter((id) => Number(id) !== pageId);
            sync(form, state, { preview: true });
        });
    });
}

function updatePreviewMeta(form, state) {
    form.querySelector('[data-footer-preview-total]')?.replaceChildren(document.createTextNode(`${state.i18n.preview_meta_total}: ${state.previewMeta.total_sections || 0}`));
    form.querySelector('[data-footer-preview-visible]')?.replaceChildren(document.createTextNode(`${state.i18n.preview_meta_visible}: ${state.previewMeta.visible_sections || 0}`));
    form.querySelector('[data-footer-preview-hidden]')?.replaceChildren(document.createTextNode(`${state.i18n.preview_meta_hidden}: ${state.previewMeta.hidden_sections || 0}`));
}

async function requestPreview(form, state) {
    const root = form.querySelector('[data-footer-preview-root]');
    if (!root) {
        return;
    }

    root.innerHTML = '<div class="footer-settings__preview-loading">Loading preview…</div>';

    try {
        const response = await fetch(state.editor.routes.preview, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ config: state.config }),
        });

        if (!response.ok) {
            throw new Error(`Preview failed with ${response.status}`);
        }

        const payload = await response.json();
        state.previewMeta = payload.meta || {};
        root.innerHTML = payload.html || '<div class="footer-settings__preview-loading">No preview available.</div>';
        updatePreviewMeta(form, state);
        renderSectionList(form, state);
        renderSectionDetail(form, state);
    } catch {
        root.innerHTML = '<div class="footer-settings__preview-loading">Preview unavailable. Save still works.</div>';
    }
}

function schedulePreview(form, state) {
    window.clearTimeout(state.previewTimer);
    state.previewTimer = window.setTimeout(() => {
        requestPreview(form, state);
    }, 400);
}

function sync(form, state, options = { preview: true }) {
    if (!currentSection(state)) {
        state.selectedSectionId = state.config.sections?.[0]?.id || null;
    }

    syncHiddenInput(form, state);
    setDirtyState(state, form);
    renderTemplateList(form, state);
    renderSectionList(form, state);
    renderSectionDetail(form, state);

    if (options.preview) {
        schedulePreview(form, state);
    }
}

export function initFooterSettings() {
    const form = document.querySelector('[data-footer-settings]');
    if (!form) {
        return;
    }

    const state = {
        config: parseDatasetJson(form, 'footerConfig', {}),
        initialConfig: parseDatasetJson(form, 'footerConfig', {}),
        editor: parseDatasetJson(form, 'footerEditor', { templates: [], sources: {}, routes: {} }),
        i18n: parseDatasetJson(form, 'footerI18n', {}),
        previewMeta: {},
        selectedSectionId: null,
        isDirty: false,
        previewTimer: null,
        dragIndex: null,
    };

    state.selectedSectionId = state.config.sections?.[0]?.id || null;

    form.querySelectorAll('[data-footer-path]').forEach((input) => {
        const eventName = input.tagName === 'SELECT' || input.type === 'number' || input.type === 'text' ? 'input' : 'change';
        input.addEventListener(eventName, () => {
            const value = input.type === 'checkbox'
                ? input.checked
                : (input.dataset.footerType === 'integer' ? Number(input.value || 0) : input.value);
            set(state.config, input.dataset.footerPath, value);
            sync(form, state, { preview: true });
        });
    });

    form.querySelectorAll('[data-footer-device]').forEach((button) => {
        button.addEventListener('click', () => {
            form.querySelectorAll('[data-footer-device]').forEach((node) => node.classList.toggle('is-active', node === button));
            form.querySelector('[data-footer-device-frame]')?.setAttribute('data-device', button.dataset.footerDevice);
        });
    });

    form.querySelector('[data-footer-add-toggle]')?.addEventListener('click', () => {
        const library = form.querySelector('[data-footer-library]');
        library.hidden = !library.hidden;
    });

    form.querySelector('[data-footer-discard]')?.addEventListener('click', () => {
        state.config = clone(state.initialConfig);
        state.selectedSectionId = state.config.sections?.[0]?.id || null;
        sync(form, state, { preview: true });
    });

    form.addEventListener('submit', () => {
        syncHiddenInput(form, state);
    });

    window.addEventListener('beforeunload', (event) => {
        if (!state.isDirty) {
            return;
        }

        event.preventDefault();
        event.returnValue = '';
    });

    sync(form, state, { preview: false });
    requestPreview(form, state);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFooterSettings);
} else {
    initFooterSettings();
}
