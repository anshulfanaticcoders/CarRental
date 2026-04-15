const toNumber = (value) => {
    const parsed = Number.parseFloat(`${value ?? ''}`);

    return Number.isFinite(parsed) ? parsed : null;
};

export const resolveOfferMapDetails = (pickupLocation = {}, dropoffLocation = {}) => {
    const pickupLatitude = toNumber(pickupLocation?.latitude);
    const pickupLongitude = toNumber(pickupLocation?.longitude);
    const dropoffLatitude = toNumber(dropoffLocation?.latitude);
    const dropoffLongitude = toNumber(dropoffLocation?.longitude);

    const hasPickup = pickupLatitude !== null && pickupLongitude !== null;
    const hasDropoff = dropoffLatitude !== null && dropoffLongitude !== null;

    const differentDropoff = hasPickup
        && hasDropoff
        && (
            Math.abs(pickupLatitude - dropoffLatitude) > 0.001
            || Math.abs(pickupLongitude - dropoffLongitude) > 0.001
        );

    return {
        hasMap: hasPickup,
        hasPickup,
        hasDropoff: differentDropoff,
        pickup: hasPickup
            ? {
                latitude: pickupLatitude,
                longitude: pickupLongitude,
                name: `${pickupLocation?.name ?? ''}`.trim() || null,
            }
            : null,
        dropoff: differentDropoff
            ? {
                latitude: dropoffLatitude,
                longitude: dropoffLongitude,
                name: `${dropoffLocation?.name ?? ''}`.trim() || null,
            }
            : null,
    };
};
