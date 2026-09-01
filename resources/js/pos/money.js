const CURRENCY_SYMBOLS = {
    THB: '฿',
    USD: '$',
    EUR: '€',
};

export function formatMoney(value, options = {}) {
    const { minor = false, currency = 'THB' } = options;
    const amount = minor ? Number(value) / 100 : Number(String(value).replace(/[^\d.-]/g, '') || 0);
    const code = String(currency).toUpperCase();
    const symbol = CURRENCY_SYMBOLS[code] ?? `${code} `;
    const formatted = amount.toLocaleString('th-TH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

    return `${symbol}${formatted}`;
}

export function parseBahtToMinor(value) {
    const normalized = String(value ?? '').replace(/,/g, '').replace(/[^\d.-]/g, '').trim();

    if (normalized === '' || normalized === '-') {
        return 0;
    }

    const negative = normalized.startsWith('-');
    const unsigned = negative ? normalized.slice(1) : normalized;
    const [wholePart, fractionPart = ''] = unsigned.split('.');
    const baht = parseInt(wholePart || '0', 10);
    const satang = parseInt(fractionPart.padEnd(2, '0').slice(0, 2) || '0', 10);
    const minor = (baht * 100) + satang;

    return negative ? -minor : minor;
}

export function formatMoneyFromState(totals, field, { minor = false } = {}) {
    if (!totals) {
        return formatMoney(0, { currency: 'THB' });
    }

    if (minor && totals[`${field}_minor`] !== undefined) {
        return formatMoney(totals[`${field}_minor`], { minor: true, currency: totals.currency });
    }

    return totals[field] ?? formatMoney(0, { currency: totals.currency ?? 'THB' });
}
