import { grossUpAmount } from './platformPricing.js';

const PROVIDERS_USING_DAILY_RATE = new Set([
    'wheelsys',
    'locauto_rent',
    'sicily_by_car',
    'recordgo',
    'surprice',
    'xdrive',
]);

const parseNumeric = (value) => {
    const numeric = parseFloat(value);
    if (!Number.isFinite(numeric)) {
        return null;
    }

    return numeric;
};

const toSafeRentalDays = (value) => {
    const parsed = parseInt(value, 10);
    if (!Number.isFinite(parsed) || parsed <= 0) {
        return 1;
    }

    return parsed;
};

const resolveVehicleSource = (vehicle = {}) => `${vehicle?.source || ''}`.trim().toLowerCase();

const extractFirstProductTotal = (vehicle = {}) => parseNumeric(vehicle?.products?.[0]?.total);

export const resolveVehicleDailyPriceSeed = (vehicle, rentalDays = 1) => {
    if (!vehicle) return null;

    const source = resolveVehicleSource(vehicle);
    const days = toSafeRentalDays(rentalDays);

    if (source === 'adobe') {
        const perDay = parseNumeric(vehicle?.price_per_day);
        const tdr = parseNumeric(vehicle?.tdr);
        const amount = perDay ?? (tdr !== null ? tdr / days : null);

        if (amount === null) return null;
        return { amount, currency: 'USD' };
    }

    if (PROVIDERS_USING_DAILY_RATE.has(source)) {
        const amount = parseNumeric(vehicle?.price_per_day);
        if (amount === null) return null;
        return { amount, currency: vehicle?.currency || 'USD' };
    }

    if (source === 'greenmotion' || source === 'usave') {
        const perDay = parseNumeric(vehicle?.price_per_day);
        const total = extractFirstProductTotal(vehicle);
        const amount = perDay ?? (total !== null ? total / days : null);
        if (amount === null) return null;
        return {
            amount,
            currency: vehicle?.products?.[0]?.currency || vehicle?.currency || 'USD',
        };
    }

    if (source === 'okmobility') {
        const amount = parseNumeric(vehicle?.price_per_day);
        if (amount === null) return null;
        return { amount, currency: 'EUR' };
    }

    if (source === 'renteon') {
        const perDay = parseNumeric(vehicle?.price_per_day);
        const total = extractFirstProductTotal(vehicle);
        const amount = perDay ?? total;
        if (amount === null) return null;
        return {
            amount,
            currency: vehicle?.currency || vehicle?.products?.[0]?.currency || 'EUR',
        };
    }

    if (source === 'favrica') {
        const perDay = parseNumeric(vehicle?.price_per_day);
        const total = extractFirstProductTotal(vehicle);
        const amount = perDay ?? (total !== null ? total / days : null);
        if (amount === null) return null;
        return {
            amount,
            currency: vehicle?.currency || vehicle?.products?.[0]?.currency || 'EUR',
        };
    }

    const amount = parseNumeric(vehicle?.price_per_day);
    if (amount === null) return null;
    return {
        amount,
        currency: vehicle?.currency || vehicle?.vendor_profile?.currency || 'USD',
    };
};

export const computeVehicleDisplayDailyPrice = ({
    vehicle,
    rentalDays = 1,
    markupRate = 0.15,
    convertAmount = (value) => value,
} = {}) => {
    const seed = resolveVehicleDailyPriceSeed(vehicle, rentalDays);
    if (!seed) return null;

    let converted = null;
    try {
        converted = convertAmount(seed.amount, seed.currency);
    } catch (error) {
        return null;
    }

    return grossUpAmount(converted, markupRate);
};
