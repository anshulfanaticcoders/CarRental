export const resolveProviderMarkupRate = (source = {}, fallback = 0.15) => {
    const rawRate = parseFloat(source?.provider_markup_rate ?? '');
    if (Number.isFinite(rawRate) && rawRate >= 0) {
        return rawRate;
    }

    const rawPercent = parseFloat(source?.provider_markup_percent ?? '');
    if (Number.isFinite(rawPercent) && rawPercent >= 0) {
        return rawPercent / 100;
    }

    const safeFallback = parseFloat(fallback);
    if (Number.isFinite(safeFallback) && safeFallback >= 0) {
        return safeFallback;
    }

    return 0;
};

export const toProviderGrossMultiplier = (rate) => {
    const numericRate = parseFloat(rate);
    if (!Number.isFinite(numericRate) || numericRate < 0) {
        return 1;
    }

    return 1 + numericRate;
};

export const grossUpAmount = (value, rate) => {
    const numeric = parseFloat(value);
    if (!Number.isFinite(numeric)) {
        return null;
    }

    const multiplier = toProviderGrossMultiplier(rate);
    return Math.round((numeric * multiplier) * 100) / 100;
};

export const isPartnerQuoteVehicle = (vehicle = {}) => {
    return Boolean(`${vehicle?.partner_supplier_name ?? ''}`.trim());
};

export const resolveEffectiveProviderMarkupRate = (vehicle = {}, rate = 0) => {
    if (isPartnerQuoteVehicle(vehicle)) {
        return 0;
    }

    return resolveProviderMarkupRate({ provider_markup_rate: rate }, 0);
};

export const shouldUseCommissionOnlyForVehicle = (vehicle = {}, rate = 0) => {
    const source = `${vehicle?.source ?? ''}`.trim();

    return source !== '' && resolveEffectiveProviderMarkupRate(vehicle, rate) > 0;
};

const roundMoney = (value) => {
    const numeric = parseFloat(value);
    if (!Number.isFinite(numeric)) {
        return 0;
    }

    return Math.round(numeric * 100) / 100;
};

export const computeBookingChargeBreakdown = ({
    netTotal = 0,
    markupRate = 0,
    depositPercentage = 15,
    useCommissionOnly = false,
} = {}) => {
    const safeNetTotal = roundMoney(netTotal);
    const safeMarkupRate = resolveProviderMarkupRate({ provider_markup_rate: markupRate }, 0);
    const grandTotal = grossUpAmount(safeNetTotal, safeMarkupRate) ?? safeNetTotal;

    let payableAmount = roundMoney(grandTotal * ((parseFloat(depositPercentage) || 0) / 100));
    if (useCommissionOnly) {
        payableAmount = roundMoney(Math.max(grandTotal - safeNetTotal, 0));
    }

    return {
        grandTotal,
        payableAmount,
        pendingAmount: roundMoney(grandTotal - payableAmount),
    };
};
