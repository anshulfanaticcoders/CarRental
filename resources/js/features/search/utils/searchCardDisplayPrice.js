const parseNumeric = (value) => {
    const numeric = parseFloat(value);
    return Number.isFinite(numeric) ? numeric : null;
};

const resolveCurrency = (vehicle, fallback = 'USD') => {
    return vehicle?.currency || vehicle?.pricing?.currency || fallback;
};

export const resolveSearchCardDisplayPrice = ({
    vehicle,
    rentalDays = 1,
    selectedPackage = 'BAS',
    selectedProtection = null,
    baseTotal = 0,
    canConvertFrom = () => true,
    convertRentalPrice = (amount) => amount,
} = {}) => {
    const source = `${vehicle?.source ?? ''}`.trim().toLowerCase();

    if (source === 'greenmotion' || source === 'usave') {
        const product = Array.isArray(vehicle?.products)
            ? vehicle.products.find((item) => item?.type === selectedPackage)
            : null;
        if (!product) return '0.00';

        const originalCurrency = product?.currency || resolveCurrency(vehicle);
        if (!canConvertFrom(originalCurrency)) return null;

        return convertRentalPrice((parseNumeric(product?.total) ?? 0) / Math.max(1, rentalDays), originalCurrency).toFixed(2);
    }

    if (source === 'locauto_rent') {
        const originalCurrency = resolveCurrency(vehicle, 'EUR');
        if (!canConvertFrom(originalCurrency)) return null;

        const originalPrice = (parseNumeric(vehicle?.price_per_day) ?? 0) + (parseNumeric(selectedProtection?.amount) ?? 0);
        return convertRentalPrice(originalPrice, originalCurrency).toFixed(2);
    }

    if (source === 'adobe') {
        if (!canConvertFrom('USD')) return null;
        const originalPrice = (parseNumeric(baseTotal) ?? 0) + (parseNumeric(selectedProtection?.amount) ?? 0);
        return convertRentalPrice(originalPrice / Math.max(1, rentalDays), 'USD').toFixed(2);
    }

    if (source === 'renteon') {
        const originalCurrency = resolveCurrency(vehicle, 'EUR');
        if (!canConvertFrom(originalCurrency)) return null;

        return convertRentalPrice(parseNumeric(vehicle?.price_per_day) ?? 0, originalCurrency).toFixed(2);
    }

    return null;
};
