export const getSearchVehicleLegacyPayload = (vehicle) => vehicle?.booking_context?.provider_payload ?? {};

const coerceNumber = (value) => {
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
};

const firstNonNull = (...values) => values.find((value) => value !== undefined && value !== null && value !== '');
const coerceOptional = (value) => value === undefined ? null : value;

export const resolveSearchVehicleDisplayName = (vehicle) => {
    const displayName = `${vehicle?.display_name ?? ''}`.trim();
    if (displayName) return displayName;

    const parts = [vehicle?.brand, vehicle?.model].filter(Boolean);
    if (parts.length) return parts.join(' ');

    return '';
};

export const resolveSearchVehicleImage = (vehicle) => {
    if (vehicle?.image) return vehicle.image;
    return null;
};

export const resolveSearchVehicleSpecs = (vehicle) => {
    const specs = vehicle?.specs ?? {};
    const legacyPayload = getSearchVehicleLegacyPayload(vehicle);

    const luggageSmall = firstNonNull(specs?.luggage_small, vehicle?.luggageSmall, legacyPayload?.luggageSmall);
    const luggageMedium = firstNonNull(specs?.luggage_medium, vehicle?.luggageMed, legacyPayload?.luggageMed);
    const luggageLarge = firstNonNull(specs?.luggage_large, vehicle?.luggageLarge, legacyPayload?.luggageLarge);
    const hasStructuredLuggage = [luggageSmall, luggageMedium, luggageLarge].some((value) => coerceNumber(value) !== null);

    let bagDisplay = null;
    if (hasStructuredLuggage) {
        const small = coerceNumber(luggageSmall) ?? 0;
        const medium = coerceNumber(luggageMedium) ?? 0;
        const large = coerceNumber(luggageLarge) ?? 0;
        if ((small + medium + large) > 0) {
            bagDisplay = `S:${small} M:${medium} L:${large}`;
        }
    } else {
        const bagTotal = (coerceNumber(vehicle?.bags) ?? 0) + (coerceNumber(vehicle?.suitcases) ?? 0);
        if (bagTotal > 0) {
            bagDisplay = bagTotal;
        }
    }

    return {
        passengers: coerceOptional(firstNonNull(specs?.seating_capacity, vehicle?.seating_capacity)),
        doors: coerceOptional(firstNonNull(specs?.doors, vehicle?.doors)),
        transmission: coerceOptional(firstNonNull(specs?.transmission, vehicle?.transmission)),
        fuel: coerceOptional(firstNonNull(specs?.fuel, vehicle?.fuel)),
        bagDisplay,
        acriss: firstNonNull(specs?.sipp_code, vehicle?.sipp_code, vehicle?.acriss_code, vehicle?.group_code, vehicle?.category) ?? null,
        airConditioning: coerceOptional(firstNonNull(
            specs?.air_conditioning,
            vehicle?.airConditioning,
            Array.isArray(vehicle?.features) ? vehicle.features.includes('Air Conditioning') : null,
            legacyPayload?.airConditioning
        )),
    };
};

export const resolveSearchVehicleFeatureFlags = (vehicle) => {
    const mileagePolicy = `${vehicle?.mileage ?? ''}`.trim().toLowerCase();

    return {
        freeCancellation: vehicle?.cancellation?.available === true
            || vehicle?.benefits?.cancellation_available_per_day === true,
        unlimitedMileage: mileagePolicy === 'unlimited'
            || vehicle?.benefits?.limited_km_per_day === false,
    };
};

export const resolveSearchVehicleCurrency = (vehicle, fallback = 'EUR') => {
    const legacyPayload = getSearchVehicleLegacyPayload(vehicle);
    const raw = firstNonNull(
        vehicle?.pricing?.currency,
        vehicle?.currency,
        legacyPayload?.currency,
        legacyPayload?.vendorProfileData?.currency,
        legacyPayload?.vendor_profile_data?.currency,
        legacyPayload?.vendorProfile?.currency,
        legacyPayload?.vendor_profile?.currency,
        legacyPayload?.benefits?.deposit_currency,
        fallback,
    );

    return `${raw ?? fallback}`.trim().toUpperCase();
};

export const resolveSearchVehiclePricePerDay = (vehicle) => {
    return coerceNumber(firstNonNull(vehicle?.pricing?.price_per_day, vehicle?.price_per_day));
};

export const resolveSearchVehicleTotalPrice = (vehicle) => {
    return coerceNumber(firstNonNull(vehicle?.pricing?.total_price, vehicle?.total_price, vehicle?.total));
};

export const resolveSearchVehicleRating = (vehicle) => {
    const legacyPayload = getSearchVehicleLegacyPayload(vehicle);
    return {
        average: coerceNumber(firstNonNull(vehicle?.average_rating, legacyPayload?.average_rating)),
        count: coerceNumber(firstNonNull(vehicle?.review_count, legacyPayload?.review_count)),
    };
};

export const resolveSearchVehicleProviderLabel = (vehicle) => {
    if (vehicle?.source !== 'internal') {
        return null;
    }

    const legacyPayload = getSearchVehicleLegacyPayload(vehicle);
    return legacyPayload?.vendorProfileData?.company_name
        || legacyPayload?.vendor_profile_data?.company_name
        || legacyPayload?.vendor?.profile?.company_name
        || legacyPayload?.vendorProfile?.company_name
        || legacyPayload?.vendor_profile?.company_name
        || 'Vrooem';
};
