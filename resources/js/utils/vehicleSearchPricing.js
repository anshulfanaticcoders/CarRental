import { grossUpAmount } from './platformPricing.js';

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

const resolveFirstProduct = (vehicle = {}) => {
    if (!Array.isArray(vehicle?.products) || vehicle.products.length === 0) {
        return null;
    }

    return vehicle.products.find((product) => product && typeof product === 'object') ?? null;
};

export const resolveVehicleDailyPriceSeed = (vehicle, rentalDays = 1) => {
    if (!vehicle) return null;

    const days = toSafeRentalDays(rentalDays);
    const pricing = vehicle?.pricing ?? {};
    const firstProduct = resolveFirstProduct(vehicle);

    const amount = parseNumeric(pricing?.price_per_day)
        ?? parseNumeric(vehicle?.price_per_day)
        ?? parseNumeric(firstProduct?.price_per_day)
        ?? (() => {
            const productTotal = parseNumeric(firstProduct?.total);
            if (productTotal !== null) {
                return productTotal / days;
            }

            const totalPrice = parseNumeric(pricing?.total_price)
                ?? parseNumeric(vehicle?.total_price)
                ?? parseNumeric(vehicle?.total);

            return totalPrice !== null ? totalPrice / days : null;
        })();

    if (amount === null) return null;

    const currency = pricing?.currency
        || vehicle?.currency
        || firstProduct?.currency
        || 'USD';

    return { amount, currency };
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
