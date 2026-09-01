/**
 * Build a numeric barcode sequence from a starting value.
 *
 * @param {string} start
 * @param {number} count
 * @returns {string[]}
 */
export function buildNumericSequence(start, count) {
    const trimmed = String(start ?? '').trim();

    if (!/^\d+$/.test(trimmed)) {
        return [];
    }

    const safeCount = Math.max(1, Math.min(10000, Number(count) || 1));
    const values = [];
    let current = trimmed;
    const padLength = trimmed.length;

    for (let i = 0; i < safeCount; i++) {
        values.push(current);
        current = incrementNumericString(current, padLength);
    }

    return values;
}

/**
 * @param {string} value
 * @param {number} padLength
 * @returns {string}
 */
function incrementNumericString(value, padLength) {
    const next = String(Number(value) + 1);
    const length = Math.max(padLength, next.length);

    return next.padStart(length, '0');
}

/**
 * @param {string} value
 * @returns {boolean}
 */
export function isNumericBarcode(value) {
    return /^\d+$/.test(String(value ?? '').trim());
}
