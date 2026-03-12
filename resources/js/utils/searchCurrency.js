const normalizeCurrency = (currency) => {
    const value = `${currency ?? ''}`.trim().toUpperCase();
    return value || null;
};

export const resolveSearchCurrency = ({
    currentCurrency = null,
    prefillCurrency = null,
    selectedCurrency = null,
    fallbackCurrency = 'USD',
} = {}) => {
    return (
        normalizeCurrency(currentCurrency) ??
        normalizeCurrency(prefillCurrency) ??
        normalizeCurrency(selectedCurrency) ??
        normalizeCurrency(fallbackCurrency)
    );
};
