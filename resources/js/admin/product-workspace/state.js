/**
 * Product Workspace — client-side state store.
 * UI-only; serializes to workspace_payload for future backend integration.
 */

const FALLBACK_OPTION_PRESETS = {};

function slugify(value) {
    return String(value ?? '')
        .toLowerCase()
        .trim()
        .replace(/[^\w\s-]/g, '')
        .replace(/[\s_-]+/g, '-')
        .replace(/^-+|-+$/g, '') || 'product';
}

function randomId() {
    return `var_${Math.random().toString(36).slice(2, 10)}`;
}

function randomSku() {
    return `SKU-${Math.random().toString(36).slice(2, 8).toUpperCase()}`;
}

function cartesianProduct(arrays) {
    if (!arrays.length) {
        return [[]];
    }

    return arrays.reduce(
        (acc, current) => acc.flatMap((prefix) => current.map((item) => [...prefix, item])),
        [[]],
    );
}

function createDefaultVariant(productName = 'Default') {
    return {
        id: randomId(),
        uuid: null,
        name: productName || 'Default',
        sku: '',
        price: '',
        cost: '',
        comparePrice: '',
        weight: '',
        status: 'active',
        imageMediaUuid: null,
        options: {},
        stock: { onHand: 0, reserved: 0, available: 0, incoming: 0 },
        isDefault: true,
    };
}

export function createInitialState(overrides = {}) {
    const productName = overrides.product?.name ?? '';

    return {
        mode: overrides.mode ?? 'create',
        dirty: false,
        activeTab: overrides.activeTab ?? 'general',
        skuPattern: overrides.skuPattern ?? '{PRODUCT}-{COLOR}-{SIZE}',
        inventoryBaseUrl: overrides.inventoryBaseUrl ?? '/admin/inventory/purchasable',
        product: {
            name: productName,
            slug: overrides.product?.slug ?? slugify(productName),
            description: overrides.product?.description ?? '',
            brandUuid: overrides.product?.brandUuid ?? '',
            categoryIds: overrides.product?.categoryIds ?? [],
            collectionIds: overrides.product?.collectionIds ?? [],
            status: overrides.product?.status ?? 'draft',
            visibility: overrides.product?.visibility ?? 'public',
            publishAt: overrides.product?.publishAt ?? '',
            sellerUuid: overrides.product?.sellerUuid ?? '',
            attributeSetId: overrides.product?.attributeSetId ?? '',
        },
        media: {
            productUuids: overrides.media?.productUuids ?? [],
            variantMap: overrides.media?.variantMap ?? {},
        },
        options: overrides.options ?? [],
        variants: overrides.variants?.length
            ? overrides.variants
            : [createDefaultVariant(productName || 'Default')],
        selection: [],
        optionPresets: overrides.optionPresets ?? FALLBACK_OPTION_PRESETS,
        labels: overrides.labels ?? {},
    };
}

export function generateSku(pattern, productSlug, optionValues) {
    if (pattern === 'random') {
        return randomSku();
    }

    const tokens = {
        PRODUCT: String(productSlug || 'product').toUpperCase().replace(/-/g, ''),
        INDEX: String(Math.floor(Math.random() * 900) + 100),
    };

    Object.entries(optionValues).forEach(([key, value]) => {
        tokens[key.toUpperCase()] = String(value).toUpperCase().replace(/\s+/g, '');
    });

    return pattern.replace(/\{([A-Z_]+)\}/g, (_, token) => tokens[token] ?? token);
}

export class ProductWorkspaceState {
    constructor(initial = {}) {
        this.data = createInitialState(initial);
        this.listeners = new Set();
    }

    subscribe(listener) {
        this.listeners.add(listener);
        return () => this.listeners.delete(listener);
    }

    notify() {
        this.listeners.forEach((listener) => listener(this.data));
    }

    markDirty() {
        if (!this.data.dirty) {
            this.data.dirty = true;
            this.notify();
        }
    }

    setDirty(value) {
        this.data.dirty = value;
        this.notify();
    }

    getState() {
        return this.data;
    }

    setProductField(field, value) {
        this.data.product[field] = value;

        if (field === 'name') {
            this.data.product.slug = slugify(value);
        }

        this.markDirty();
    }

    setSkuPattern(pattern) {
        this.data.skuPattern = pattern;
        this.markDirty();
    }

    addOption(name, presetValues = []) {
        const normalized = String(name).trim();
        if (!normalized) {
            return;
        }

        if (this.data.options.some((opt) => opt.name.toLowerCase() === normalized.toLowerCase())) {
            return;
        }

        this.data.options.push({
            id: randomId(),
            name: normalized,
            values: [...presetValues],
        });

        this.markDirty();
    }

    removeOption(optionId) {
        this.data.options = this.data.options.filter((opt) => opt.id !== optionId);
        this.markDirty();
    }

    addOptionValue(optionId, value) {
        const option = this.data.options.find((opt) => opt.id === optionId);
        const normalized = String(value).trim();

        if (!option || !normalized || option.values.includes(normalized)) {
            return;
        }

        option.values.push(normalized);
        this.markDirty();
    }

    removeOptionValue(optionId, value) {
        const option = this.data.options.find((opt) => opt.id === optionId);
        if (!option) {
            return;
        }

        option.values = option.values.filter((item) => item !== value);
        this.markDirty();
    }

    matrixCount() {
        if (!this.data.options.length) {
            return 1;
        }

        const counts = this.data.options.map((opt) => Math.max(opt.values.length, 0));
        if (counts.some((count) => count === 0)) {
            return 0;
        }

        return counts.reduce((total, count) => total * count, 1);
    }

    generateMatrix() {
        const slug = this.data.product.slug || 'product';

        if (!this.data.options.length) {
            if (!this.data.variants.length) {
                this.data.variants = [createDefaultVariant(this.data.product.name)];
            }
            this.markDirty();
            return;
        }

        const validOptions = this.data.options.filter((opt) => opt.values.length > 0);
        if (!validOptions.length) {
            return;
        }

        const combinations = cartesianProduct(validOptions.map((opt) => opt.values));
        const existingMap = new Map(
            this.data.variants.map((variant) => [JSON.stringify(variant.options), variant]),
        );

        this.data.variants = combinations.map((combo, index) => {
            const optionMap = {};
            validOptions.forEach((opt, optIndex) => {
                optionMap[opt.name.toLowerCase()] = combo[optIndex];
            });

            const key = JSON.stringify(optionMap);
            const existing = existingMap.get(key);

            if (existing) {
                return { ...existing, name: combo.join(' / '), options: optionMap };
            }

            return {
                id: randomId(),
                uuid: null,
                name: combo.join(' / '),
                sku: generateSku(this.data.skuPattern, slug, optionMap),
                price: '',
                cost: '',
                comparePrice: '',
                weight: '',
                status: 'active',
                imageMediaUuid: null,
                options: optionMap,
                stock: { onHand: 0, reserved: 0, available: 0, incoming: 0 },
                isDefault: index === 0,
            };
        });

        this.markDirty();
    }

    updateVariant(variantId, field, value) {
        const variant = this.data.variants.find((item) => item.id === variantId);
        if (!variant) {
            return;
        }

        variant[field] = value;
        this.markDirty();
    }

    deleteVariant(variantId) {
        if (this.data.variants.length <= 1) {
            return;
        }

        this.data.variants = this.data.variants.filter((item) => item.id !== variantId);
        this.data.selection = this.data.selection.filter((id) => id !== variantId);
        this.markDirty();
    }

    toggleSelection(variantId, selected) {
        if (selected) {
            if (!this.data.selection.includes(variantId)) {
                this.data.selection.push(variantId);
            }
        } else {
            this.data.selection = this.data.selection.filter((id) => id !== variantId);
        }

        this.notify();
    }

    selectAll(selected) {
        this.data.selection = selected ? this.data.variants.map((item) => item.id) : [];
        this.notify();
    }

    applyBulk(field, value) {
        this.data.selection.forEach((variantId) => {
            const variant = this.data.variants.find((item) => item.id === variantId);
            if (!variant) {
                return;
            }

            if (field === 'sku') {
                variant.sku = generateSku(this.data.skuPattern, this.data.product.slug, variant.options);
            } else {
                variant[field] = value;
            }
        });

        this.markDirty();
    }

    applyBulkImage(mediaUuid, previewUrl) {
        this.data.selection.forEach((variantId) => {
            const variant = this.data.variants.find((item) => item.id === variantId);
            if (!variant) {
                return;
            }

            variant.imageMediaUuid = mediaUuid;
            variant.imagePreviewUrl = previewUrl;
        });

        this.markDirty();
    }

    setMediaUuids(uuids) {
        this.data.media.productUuids = [...uuids];
    }

    bulkDelete() {
        if (this.data.variants.length - this.data.selection.length < 1) {
            return;
        }

        this.data.variants = this.data.variants.filter((item) => !this.data.selection.includes(item.id));
        this.data.selection = [];
        this.markDirty();
    }

    serialize() {
        return JSON.stringify({
            product: this.data.product,
            media: this.data.media,
            options: this.data.options,
            variants: this.data.variants,
            skuPattern: this.data.skuPattern,
        });
    }
}

export { slugify, randomSku, createDefaultVariant, FALLBACK_OPTION_PRESETS as OPTION_PRESETS };
