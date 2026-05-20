import currencies from '../../data/currencies.json';

const registry = Array.isArray(currencies) ? currencies : [];

export const currencyMetaByCode = Object.freeze(
    registry.reduce((map, currency) => {
        if (currency?.code) {
            map[String(currency.code).toUpperCase()] = currency;
        }

        return map;
    }, {})
);

const aliasToCode = Object.freeze(
    registry.reduce((map, currency) => {
        const code = String(currency?.code || '').toUpperCase();
        if (!code) return map;

        [currency.symbol, ...(currency.aliases || [])].forEach((alias) => {
            const key = String(alias || '').trim();
            if (key) {
                map[key] = code;
                map[key.toUpperCase()] = code;
            }
        });

        return map;
    }, {})
);

export const allCurrencies = registry;

export const selectableCurrencyCodes = registry
    .filter((currency) => currency.selectable && currency.checkout_enabled)
    .map((currency) => currency.code);

export function normalizeCurrencyCode(currency, fallback = 'EUR') {
    const raw = String(currency || '').trim();
    if (!raw) return fallback;

    const upper = raw.toUpperCase();
    if (currencyMetaByCode[upper]) return upper;

    return aliasToCode[raw] || aliasToCode[upper] || fallback;
}

export function getCurrencyMeta(currency) {
    const code = normalizeCurrencyCode(currency, String(currency || 'EUR').toUpperCase());

    return currencyMetaByCode[code] || {
        code,
        name: code,
        symbol: code,
        flag_country: null,
        minor_unit: 2,
        popular: false,
    };
}

export function getCurrencySymbol(currency) {
    return getCurrencyMeta(currency).symbol || normalizeCurrencyCode(currency);
}

export function getCurrencyName(currency) {
    return getCurrencyMeta(currency).name || normalizeCurrencyCode(currency);
}

export function getCurrencyFlagCountry(currency) {
    return getCurrencyMeta(currency).flag_country || null;
}

export function getCurrencyMinorUnit(currency) {
    return Number(getCurrencyMeta(currency).minor_unit ?? 2);
}

export function getCurrencyOptions(codes = selectableCurrencyCodes) {
    return [...new Set(codes.map((code) => normalizeCurrencyCode(code)).filter(Boolean))]
        .map((code) => getCurrencyMeta(code))
        .sort((a, b) => a.code.localeCompare(b.code));
}
