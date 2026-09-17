const localeIsThai = () => (document.documentElement.lang || '').toLowerCase().startsWith('th');

const locationLabel = (item) => (localeIsThai() ? item.name_th : item.name_en) || item.name_th || item.name_en || '';

const fetchLocations = async (url) => {
    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            return [];
        }

        const payload = await response.json();

        return Array.isArray(payload?.data) ? payload.data : [];
    } catch {
        return [];
    }
};

const fillSelect = (select, items, selected) => {
    if (!select) {
        return;
    }

    const current = selected || select.dataset.selected || select.value || '';
    const placeholder = select.querySelector('option[value=""]')?.cloneNode(true);

    select.replaceChildren();

    if (placeholder) {
        select.append(placeholder);
    }

    items.forEach((item) => {
        const option = document.createElement('option');
        option.value = item.name_en || item.name_th || '';
        option.dataset.id = String(item.id);
        option.dataset.th = item.name_th || '';
        option.dataset.en = item.name_en || '';

        if (item.postal_code) {
            option.dataset.postal = String(item.postal_code);
        }

        option.textContent = locationLabel(item);

        if (current && (String(item.id) === String(current) || item.name_en === current || item.name_th === current)) {
            option.selected = true;
        }

        select.append(option);
    });

    enhanceCombobox(select);
};

const optionSearchText = (option) =>
    [option.textContent, option.value, option.dataset.th, option.dataset.en]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

const selectedLabel = (select) => select.selectedOptions[0]?.textContent || '';

let comboboxUid = 0;

const comboboxParts = (select) => {
    const wrap = select?.closest('[data-thailand-combobox]');

    return {
        wrap,
        input: wrap?.querySelector('[data-thailand-combobox-input]'),
        list: wrap?.querySelector('[data-thailand-combobox-list]'),
    };
};

const comboboxOptions = (list) => [...(list?.querySelectorAll('.storefront-combobox__option') || [])];

const highlightComboboxOption = (select, option) => {
    const { input, list } = comboboxParts(select);

    if (!input || !list || !option) {
        return;
    }

    comboboxOptions(list).forEach((item) => {
        if (item === option) {
            item.setAttribute('aria-selected', 'true');
        } else {
            item.removeAttribute('aria-selected');
        }
    });

    input.setAttribute('aria-activedescendant', option.id);
    option.scrollIntoView({ block: 'nearest' });
};

const moveComboboxHighlight = (select, key) => {
    const { list } = comboboxParts(select);
    const options = comboboxOptions(list);

    if (!options.length) {
        return;
    }

    const current = options.findIndex((option) => option.getAttribute('aria-selected') === 'true');
    let next = current;

    if (key === 'Home') {
        next = 0;
    } else if (key === 'End') {
        next = options.length - 1;
    } else if (key === 'ArrowDown') {
        next = current < 0 ? 0 : Math.min(options.length - 1, current + 1);
    } else if (key === 'ArrowUp') {
        next = current < 0 ? options.length - 1 : Math.max(0, current - 1);
    }

    highlightComboboxOption(select, options[next]);
};

const pickComboboxOption = (select, item) => {
    const { input } = comboboxParts(select);

    if (!item) {
        return;
    }

    select.value = item.dataset.value ?? '';

    if (input) {
        input.value = item.textContent;
    }

    select.dispatchEvent(new Event('change', { bubbles: true }));
    closeCombobox(select);
};

const associatedLabel = (select) => select.labels?.[0]
    || (select.id ? document.querySelector(`label[for="${CSS.escape(select.id)}"]`) : null);

const enhanceCombobox = (select) => {
    if (!select) {
        return;
    }

    let wrap = select.closest('[data-thailand-combobox]');
    select.tabIndex = -1;

    if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'storefront-combobox';
        wrap.dataset.thailandCombobox = '';
        select.parentNode.insertBefore(wrap, select);
        wrap.append(select);
        select.classList.add('storefront-combobox__select');

        const listId = select.id
            ? `${select.id}-combobox-list`
            : `thailand-combobox-list-${++comboboxUid}`;

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.spellcheck = false;
        input.className = select.className.replace('storefront-combobox__select', '').trim();
        input.classList.add('storefront-combobox__input');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', listId);
        input.dataset.thailandComboboxInput = '';

        const label = associatedLabel(select);

        if (label?.id) {
            input.setAttribute('aria-labelledby', label.id);
        } else if (label) {
            input.setAttribute('aria-label', (label.textContent || '').trim());
        }

        const list = document.createElement('ul');
        list.id = listId;
        list.hidden = true;
        list.setAttribute('role', 'listbox');
        list.className = 'storefront-combobox__list';
        list.dataset.thailandComboboxList = '';

        wrap.append(input, list);

        input.addEventListener('focus', () => {
            input.value = '';
            renderComboboxList(select, '');
            openCombobox(select);
        });
        input.addEventListener('input', () => {
            renderComboboxList(select, input.value);
            openCombobox(select);
        });
        input.addEventListener('blur', () => {
            closeCombobox(select);
        });
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeCombobox(select);
                input.blur();
                return;
            }

            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();

                if (list.hidden) {
                    renderComboboxList(select, input.value);
                    openCombobox(select);
                }

                moveComboboxHighlight(select, event.key);
                return;
            }

            if (event.key === 'Enter') {
                const highlighted = list.querySelector('.storefront-combobox__option[aria-selected="true"]');

                if (highlighted) {
                    event.preventDefault();
                    pickComboboxOption(select, highlighted);
                }

                return;
            }

            if (event.key === ' ' && input.value === '') {
                const highlighted = list.querySelector('.storefront-combobox__option[aria-selected="true"]');

                if (highlighted) {
                    event.preventDefault();
                    pickComboboxOption(select, highlighted);
                }
            }
        });
        list.addEventListener('mousedown', (event) => event.preventDefault());
    }

    syncComboboxInput(select);
    renderComboboxList(select, '');
};

const syncComboboxInput = (select) => {
    const input = select.closest('[data-thailand-combobox]')?.querySelector('[data-thailand-combobox-input]');

    if (!input) {
        return;
    }

    input.disabled = select.disabled;

    if (document.activeElement !== input) {
        input.value = selectedLabel(select);
        input.placeholder = select.querySelector('option[value=""]')?.textContent || '';
    }
};

const renderComboboxList = (select, query) => {
    const { input, list } = comboboxParts(select);

    if (!list) {
        return;
    }

    const needle = query.trim().toLowerCase();
    list.replaceChildren();
    input?.removeAttribute('aria-activedescendant');

    [...select.options]
        .filter((option) => option.value !== '')
        .filter((option) => !needle || optionSearchText(option).includes(needle))
        .forEach((option, index) => {
            const item = document.createElement('li');
            item.id = `${list.id || 'thailand-combobox-list'}-option-${index}`;
            item.dataset.value = option.value;
            item.setAttribute('role', 'option');
            item.className = 'storefront-combobox__option';
            item.textContent = option.textContent;

            if (option.selected) {
                item.setAttribute('aria-selected', 'true');
                input?.setAttribute('aria-activedescendant', item.id);
            }

            item.addEventListener('click', () => pickComboboxOption(select, item));
            list.append(item);
        });
};

const openCombobox = (select) => {
    const { input, list } = comboboxParts(select);

    if (!list || !input) {
        return;
    }

    list.hidden = false;
    input.setAttribute('aria-expanded', 'true');

    const selected = list.querySelector('.storefront-combobox__option[aria-selected="true"]');

    if (selected) {
        input.setAttribute('aria-activedescendant', selected.id);
    }
};

const closeCombobox = (select) => {
    const { input, list } = comboboxParts(select);

    if (!list || !input) {
        return;
    }

    list.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    syncComboboxInput(select);
};

const setDisabled = (element, disabled) => {
    if (!element) {
        return;
    }

    const editor = element.closest('[data-address-editor]');

    if (editor?.hidden) {
        element.disabled = true;

        return;
    }

    element.disabled = disabled;
};

const syncSubdistricts = async (root) => {
    const baseUrl = root.dataset.locationsUrl;
    const district = root.querySelector('[data-thailand-district]');
    const subdistrict = root.querySelector('[data-thailand-subdistrict]');
    const cityHidden = root.querySelector('[data-thailand-city]');
    const option = district?.selectedOptions[0];
    const districtId = option?.dataset.id;

    if (cityHidden && option?.value) {
        cityHidden.value = option.value;
    }

    if (!districtId || !subdistrict || !baseUrl) {
        return;
    }

    const subdistricts = await fetchLocations(`${baseUrl}/subdistricts/${districtId}`);
    fillSelect(subdistrict, subdistricts, subdistrict.dataset.selected);

    const selectedSub = subdistrict.selectedOptions[0];
    const postal = root.querySelector('[data-thailand-postal]');

    if (postal && selectedSub?.dataset.postal) {
        postal.value = selectedSub.dataset.postal;
    }
};

const syncDistricts = async (root) => {
    const baseUrl = root.dataset.locationsUrl;
    const province = root.querySelector('[data-thailand-province]');
    const district = root.querySelector('[data-thailand-district]');
    const stateInput = root.querySelector('[data-thailand-state]');
    const option = province?.selectedOptions[0];
    const provinceId = option?.dataset.id;

    if (stateInput && option?.value) {
        stateInput.value = option.value;
    }

    if (!provinceId || !district || !baseUrl) {
        return;
    }

    const districts = await fetchLocations(`${baseUrl}/districts/${provinceId}`);
    fillSelect(district, districts, district.dataset.selected);
    await syncSubdistricts(root);
};

const syncThailandGroup = async (root) => {
    const baseUrl = root.dataset.locationsUrl;
    const country = root.querySelector('[data-address-country]')?.value || 'TH';
    const isThailand = country === 'TH';
    const thGroup = root.querySelector('[data-location-thailand]');
    const intlGroup = root.querySelector('[data-location-international]');
    const province = root.querySelector('[data-thailand-province]');
    const district = root.querySelector('[data-thailand-district]');
    const subdistrict = root.querySelector('[data-thailand-subdistrict]');
    const stateInput = root.querySelector('[data-thailand-state]');
    const cityHidden = root.querySelector('[data-thailand-city]');
    const cityFree = root.querySelector('[data-location-international] [data-address-field="city"]');
    const stateFree = root.querySelector('input[data-location-state-free]');
    const postal = root.querySelector('[data-thailand-postal]');

    thGroup?.classList.toggle('storefront-is-hidden', !isThailand);
    intlGroup?.classList.toggle('storefront-is-hidden', isThailand);

    const labelScope = root.closest('[data-checkout-address]') ?? root;
    labelScope.querySelectorAll('[data-label-th]').forEach((el) => el.classList.toggle('storefront-is-hidden', !isThailand));
    labelScope.querySelectorAll('[data-label-intl]').forEach((el) => el.classList.toggle('storefront-is-hidden', isThailand));

    [province, district, subdistrict, stateInput, cityHidden].forEach((el) => setDisabled(el, !isThailand));
    [province, district, subdistrict].forEach((el) => enhanceCombobox(el));
    [cityFree, stateFree].forEach((el) => setDisabled(el, isThailand));

    if (!isThailand || !baseUrl || !province) {
        return;
    }

    const provinces = await fetchLocations(`${baseUrl}/provinces`);
    fillSelect(province, provinces, province.dataset.selected || stateInput?.value);
    await syncDistricts(root);

    if (postal && !postal.value) {
        const selectedSub = subdistrict?.selectedOptions[0];

        if (selectedSub?.dataset.postal) {
            postal.value = selectedSub.dataset.postal;
        }
    }
};

export const initThailandAddresses = (scope = document) => {
    scope.querySelectorAll('[data-thailand-address]').forEach((root) => {
        if (root.dataset.thailandReady === '1') {
            syncThailandGroup(root);

            return;
        }

        root.dataset.thailandReady = '1';
        root.querySelector('[data-address-country]')?.addEventListener('change', () => syncThailandGroup(root));
        root.querySelector('[data-thailand-province]')?.addEventListener('change', () => {
            const district = root.querySelector('[data-thailand-district]');
            const subdistrict = root.querySelector('[data-thailand-subdistrict]');

            if (district) {
                district.dataset.selected = '';
            }

            if (subdistrict) {
                subdistrict.dataset.selected = '';
            }

            syncDistricts(root);
        });
        root.querySelector('[data-thailand-district]')?.addEventListener('change', () => {
            const subdistrict = root.querySelector('[data-thailand-subdistrict]');

            if (subdistrict) {
                subdistrict.dataset.selected = '';
            }

            syncSubdistricts(root);
        });
        root.querySelector('[data-thailand-subdistrict]')?.addEventListener('change', () => {
            const option = root.querySelector('[data-thailand-subdistrict]')?.selectedOptions[0];
            const postal = root.querySelector('[data-thailand-postal]');

            if (postal && option?.dataset.postal) {
                postal.value = option.dataset.postal;
            }
        });
        syncThailandGroup(root);
    });
};

document.addEventListener('storefront:address-sync', () => initThailandAddresses());

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initThailandAddresses());
} else {
    initThailandAddresses();
}
