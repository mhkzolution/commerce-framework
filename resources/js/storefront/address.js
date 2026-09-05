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

        if (item.postal_code) {
            option.dataset.postal = String(item.postal_code);
        }

        option.textContent = locationLabel(item);

        if (current && (String(item.id) === String(current) || item.name_en === current || item.name_th === current)) {
            option.selected = true;
        }

        select.append(option);
    });
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
