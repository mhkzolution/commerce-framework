function normalizeOptionKey(key) {
    return String(key).toLowerCase();
}

export function getOptionValue(options, axisKey) {
    if (!options || typeof options !== 'object') {
        return undefined;
    }

    if (options[axisKey] !== undefined) {
        return options[axisKey];
    }

    const normalized = normalizeOptionKey(axisKey);
    return Object.entries(options).find(([key]) => normalizeOptionKey(key) === normalized)?.[1];
}

export function combinationExists(variants, selections) {
    const entries = Object.entries(selections).filter(([, value]) => value !== undefined && value !== '');

    if (entries.length === 0) {
        return variants.length > 0;
    }

    return variants.some((variant) => (
        entries.every(([key, value]) => String(getOptionValue(variant.options, key)) === String(value))
    ));
}

export function isAxisValueEnabled(variants, selections, axisKey, axisValue) {
    return combinationExists(variants, { ...selections, [axisKey]: axisValue });
}

export function resolveExactVariant(variants, selections) {
    const entries = Object.entries(selections).filter(([, value]) => value !== undefined && value !== '');

    if (entries.length === 0) {
        return null;
    }

    return variants.find((variant) => (
        entries.every(([key, value]) => String(getOptionValue(variant.options, key)) === String(value))
    )) ?? null;
}
