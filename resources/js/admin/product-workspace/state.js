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

export function variantIdentityKey(valueIds) {
    return [...valueIds].map(Number).sort((a, b) => a - b).join('-');
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
        trackInventory: true,
        skuIsAuto: false,
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
        uiEpoch: overrides.uiEpoch ?? 0,
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
            type: overrides.product?.type ?? 'simple',
            trackInventory: overrides.product?.trackInventory ?? true,
            backorderPolicy: overrides.product?.backorderPolicy ?? 'deny',
            sku: overrides.product?.sku ?? '',
            skuPrefix: overrides.product?.skuPrefix ?? '',
            price: overrides.product?.price ?? '',
            onHand: overrides.product?.onHand ?? 0,
            reserved: overrides.product?.reserved ?? 0,
            available: overrides.product?.available ?? 0,
        },
        media: {
            productUuids: overrides.media?.productUuids ?? [],
            variantMap: overrides.media?.variantMap ?? {},
        },
        options: overrides.options ?? [],
        productAttributes: overrides.productAttributes ?? [],
        generateVariants: false,
        attributeSets: overrides.attributeSets ?? [],
        variants: overrides.variants?.length
            ? overrides.variants
            : [createDefaultVariant(productName || 'Default')],
        selection: [],
        optionPresets: overrides.optionPresets ?? FALLBACK_OPTION_PRESETS,
        labels: overrides.labels ?? {},
    };
}

function skuPart(value) {
    return String(value ?? '')
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

export function generateSku(pattern, productSlug, optionValues, prefix = '') {
    const prefixPart = skuPart(prefix);
    if (prefixPart) {
        const optionParts = Object.values(optionValues ?? {}).map(skuPart).filter(Boolean);
        return [prefixPart, ...optionParts].join('-');
    }

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

    markDirty({ rebuild = false } = {}) {
        this.data.dirty = true;
        if (rebuild) {
            this.data.uiEpoch = (this.data.uiEpoch ?? 0) + 1;
        }
        this.notify();
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

    setType(nextType) {
        if (nextType === 'simple') {
            const first = this.data.variants[0];
            if (first) {
                this.data.product.sku = first.sku ?? this.data.product.sku;
                this.data.product.price = first.price ?? this.data.product.price;
                this.data.product.onHand = first.stock?.onHand ?? this.data.product.onHand;
            }
        }

        this.data.product.type = nextType;
        this.markDirty();
    }

    skuInputValue() {
        return this.data.product.type === 'simple'
            ? (this.data.product.sku ?? '')
            : (this.data.product.skuPrefix ?? '');
    }

    setSkuInput(value) {
        this.setProductField(this.data.product.type === 'simple' ? 'sku' : 'skuPrefix', value);
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

        this.markDirty({ rebuild: true });
    }

    removeOption(optionId) {
        this.data.options = this.data.options.filter((opt) => opt.id !== optionId);
        this.markDirty({ rebuild: true });
    }

    addOptionValue(optionId, value) {
        const option = this.data.options.find((opt) => opt.id === optionId);
        const normalized = String(value).trim();

        if (!option || !normalized || option.values.includes(normalized)) {
            return;
        }

        option.values.push(normalized);
        this.markDirty({ rebuild: true });
    }

    removeOptionValue(optionId, value) {
        const option = this.data.options.find((opt) => opt.id === optionId);
        if (!option) {
            return;
        }

        option.values = option.values.filter((item) => item !== value);
        this.markDirty({ rebuild: true });
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
            this.markDirty({ rebuild: true });
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
                sku: generateSku(this.data.skuPattern, slug, optionMap, this.data.product.skuPrefix),
                price: '',
                cost: '',
                comparePrice: '',
                weight: '',
                status: 'active',
                imageMediaUuid: null,
                options: optionMap,
                stock: { onHand: 0, reserved: 0, available: 0, incoming: 0 },
                trackInventory: this.data.product.trackInventory,
                skuIsAuto: true,
                isDefault: index === 0,
            };
        });

        this.markDirty({ rebuild: true });
    }

    variationAxes() {
        return (this.data.productAttributes ?? []).filter((row) => row.usedForVariations);
    }

    axisValueIds(row) {
        return (row.valueIds ?? []).map(Number).filter((id) => id > 0);
    }

    axisHasValues(row) {
        return this.axisValueIds(row).length > 0
            || (row.newLabels ?? []).some((label) => String(label).trim() !== '');
    }

    canGenerateVariants() {
        if (this.data.product.type !== 'variable') {
            return false;
        }

        const axes = this.variationAxes();
        if (!axes.length) {
            return false;
        }

        return axes.every((row) => this.axisHasValues(row));
    }

    hasGeneratedMatrix() {
        return (this.data.variants ?? []).some((variant) => (
            (variant.valueIds ?? []).length > 0 || Boolean(variant.uuid)
        ));
    }

    setAttributeValues(attributeId, valueIds) {
        const row = (this.data.productAttributes ?? []).find(
            (item) => Number(item.attributeId) === Number(attributeId),
        );
        if (!row) {
            return;
        }

        row.valueIds = [...valueIds].map(Number).filter((id) => id > 0);
        this.markDirty();
    }

    addAttributeLabel(attributeId, label) {
        const row = (this.data.productAttributes ?? []).find(
            (item) => Number(item.attributeId) === Number(attributeId),
        );
        const normalized = String(label ?? '').trim();
        if (!row || !normalized) {
            return;
        }

        row.newLabels = [...(row.newLabels ?? []), normalized];
        this.markDirty();
    }

    setUsedForVariations(attributeId, used, confirmFn = globalThis.confirm) {
        const row = (this.data.productAttributes ?? []).find(
            (item) => Number(item.attributeId) === Number(attributeId),
        );
        if (!row) {
            return false;
        }

        if (row.usedForVariations && !used && this.hasGeneratedMatrix()) {
            const message = this.data.labels.usedForVariationsUncheckConfirm
                ?? 'Unchecking this will rebuild or remove existing variants. Continue?';
            if (typeof confirmFn === 'function' && !confirmFn(message)) {
                return false;
            }
        }

        row.usedForVariations = Boolean(used);
        this.markDirty();
        return true;
    }

    syncProductAttributesFromSet(setId, catalog = [], { dirty = true } = {}) {
        this.data.product.attributeSetId = setId;
        const sets = catalog.length ? catalog : (this.data.attributeSets ?? []);
        const selected = sets.find((set) => String(set.id) === String(setId));
        const attributes = selected?.attributes ?? [];
        const previous = new Map(
            (this.data.productAttributes ?? []).map((row) => [Number(row.attributeId), row]),
        );

        this.data.productAttributes = attributes.map((attribute, index) => {
            const existing = previous.get(Number(attribute.id));

            return {
                attributeId: Number(attribute.id),
                usedForVariations: existing?.usedForVariations ?? false,
                valueIds: existing?.valueIds ?? [],
                newLabels: existing?.newLabels ?? [],
                position: existing?.position ?? index,
                name: attribute.name,
                type: attribute.type,
            };
        });

        if (dirty) {
            this.markDirty();
            return;
        }

        this.notify();
    }

    attributeValueMetaById() {
        const map = new Map();
        for (const set of this.data.attributeSets ?? []) {
            for (const attribute of set.attributes ?? []) {
                for (const value of attribute.values ?? []) {
                    map.set(Number(value.id), {
                        label: String(value.label ?? ''),
                        code: String(value.code ?? ''),
                    });
                }
            }
        }

        return map;
    }

    persistedVariantUuid(variant) {
        return String(variant?.uuid ?? '').trim();
    }

    shouldPostVariant(variant, generating) {
        if (this.persistedVariantUuid(variant) !== '') {
            return true;
        }

        if (generating) {
            return false;
        }

        return (variant.valueIds ?? []).length === 0;
    }

    generateFromAttributes() {
        if (!this.canGenerateVariants()) {
            return;
        }

        const axes = this.variationAxes().filter((axis) => this.axisValueIds(axis).length > 0);
        if (axes.length === this.variationAxes().length && axes.length > 0) {
            const combinations = cartesianProduct(axes.map((axis) => this.axisValueIds(axis)));
            const existingMap = new Map();
            this.data.variants.forEach((variant) => {
                const ids = variant.valueIds ?? [];
                if (ids.length) {
                    existingMap.set(variantIdentityKey(ids), variant);
                }
            });

            const slug = this.data.product.slug || 'product';
            const valueMeta = this.attributeValueMetaById();
            this.data.variants = combinations.map((combo, index) => {
                const existing = existingMap.get(variantIdentityKey(combo));
                const labels = combo.map((id) => valueMeta.get(Number(id))?.label || String(id));
                const codes = combo.map((id) => valueMeta.get(Number(id))?.code || String(id));
                const optionMap = {};
                axes.forEach((axis, axisIndex) => {
                    const name = String(axis.name ?? axis.attributeId).toLowerCase();
                    optionMap[name] = codes[axisIndex];
                });

                if (existing) {
                    return {
                        ...existing,
                        valueIds: combo,
                        options: optionMap,
                        name: existing.name || labels.join(' / '),
                    };
                }

                return {
                    id: randomId(),
                    uuid: null,
                    name: labels.join(' / '),
                    sku: generateSku(this.data.skuPattern, slug, optionMap, this.data.product.skuPrefix),
                    price: '',
                    cost: '',
                    comparePrice: '',
                    weight: '',
                    status: 'active',
                    imageMediaUuid: null,
                    options: optionMap,
                    valueIds: combo,
                    stock: { onHand: 0, reserved: 0, available: 0, incoming: 0 },
                    trackInventory: this.data.product.trackInventory,
                    skuIsAuto: true,
                    isDefault: index === 0,
                };
            });
        }

        this.data.generateVariants = true;
        this.markDirty({ rebuild: true });
    }

    consumeGenerateFlag() {
        this.data.generateVariants = false;
    }

    serializedProductAttributes() {
        return (this.data.productAttributes ?? []).map((row, index) => ({
            attributeId: Number(row.attributeId),
            usedForVariations: this.data.product.type === 'simple'
                ? false
                : Boolean(row.usedForVariations),
            valueIds: this.axisValueIds(row),
            newLabels: (row.newLabels ?? []).map((label) => String(label).trim()).filter(Boolean),
            position: row.position ?? index,
        }));
    }

    updateVariant(variantId, field, value, { rebuild = false } = {}) {
        const variant = this.data.variants.find((item) => item.id === variantId);
        if (!variant) {
            return;
        }

        variant[field] = value;
        this.markDirty({ rebuild });
    }

    updateVariantStock(variantId, field, value) {
        const variant = this.data.variants.find((item) => item.id === variantId);
        if (!variant) {
            return;
        }

        variant.stock = { ...variant.stock, [field]: value };
        this.markDirty();
    }

    deleteVariant(variantId) {
        if (this.data.variants.length <= 1) {
            return;
        }

        this.data.variants = this.data.variants.filter((item) => item.id !== variantId);
        this.data.selection = this.data.selection.filter((id) => id !== variantId);
        this.markDirty({ rebuild: true });
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
                variant.sku = generateSku(
                    this.data.skuPattern,
                    this.data.product.slug,
                    variant.options,
                    this.data.product.skuPrefix,
                );
            } else {
                variant[field] = value;
            }
        });

        this.markDirty({ rebuild: true });
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

        this.markDirty({ rebuild: true });
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
        this.markDirty({ rebuild: true });
    }

    serialize() {
        const trackInventory = Boolean(this.data.product.trackInventory);
        const generating = Boolean(this.data.generateVariants);
        let variants = this.data.variants
            .filter((variant) => this.shouldPostVariant(variant, generating))
            .map((variant) => {
                const { valueIds, ...rest } = variant;

                return {
                    ...rest,
                    onHand: variant.stock?.onHand,
                    trackInventory,
                };
            });

        if (this.data.product.type === 'simple') {
            const defaultVariant = variants[0] ?? createDefaultVariant(this.data.product.name);
            variants = [{
                ...defaultVariant,
                name: defaultVariant.name || this.data.product.name || 'Default',
                sku: this.data.product.sku,
                price: this.data.product.price,
                stock: {
                    ...defaultVariant.stock,
                    onHand: this.data.product.onHand,
                },
                onHand: this.data.product.onHand,
                trackInventory,
                isDefault: true,
            }];
        }

        const product = {
            ...this.data.product,
            sku: this.data.product.type === 'variable'
                ? (this.data.product.skuPrefix ?? '')
                : this.data.product.sku,
        };

        return JSON.stringify({
            product,
            media: this.data.media,
            options: this.data.options,
            productAttributes: this.serializedProductAttributes(),
            generateVariants: Boolean(this.data.generateVariants),
            variants,
            skuPattern: this.data.skuPattern,
        });
    }
}

export { slugify, randomSku, createDefaultVariant, FALLBACK_OPTION_PRESETS as OPTION_PRESETS };
