const FALLBACK_SYMBOLS = {
    THB: '฿',
    USD: '$',
    EUR: '€',
};

export function readMoneyConfig() {
    return window.__storefrontMoney ?? { currency: 'THB', symbol: '฿', decimals: 2 };
}

export function resolveSymbol(currency, config = readMoneyConfig()) {
    const code = String(currency || config.currency || 'THB').toUpperCase();

    if (code === String(config.currency || '').toUpperCase()) {
        return config.symbol ?? FALLBACK_SYMBOLS[code] ?? code;
    }

    return FALLBACK_SYMBOLS[code] ?? code;
}

function formatAmount(amount, currency, decimals) {
    const symbol = resolveSymbol(currency);
    const locale = document.documentElement.lang || 'th-TH';

    return `${symbol} ${Number(amount).toLocaleString(locale, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })}`;
}

export function formatMoneyMinor(amountMinor, currency, options = {}) {
    const config = readMoneyConfig();
    const decimals = options.decimals ?? config.decimals ?? 2;

    return formatAmount(Number(amountMinor) / (10 ** decimals), currency || config.currency, decimals);
}

export function formatMoneyMajor(amount, currency, options = {}) {
    const config = readMoneyConfig();
    const decimals = options.decimals ?? config.decimals ?? 2;

    return formatAmount(amount, currency || config.currency, decimals);
}
