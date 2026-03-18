const normalizeText = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim();

const toNumber = (value) => {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
};

const toInt = (value) => {
    const parsed = Number.parseInt(String(value ?? ''), 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null;
};

const haversineKm = (lat1, lon1, lat2, lon2) => {
    const d2r = (deg) => (deg * Math.PI) / 180;
    const r = 6371;
    const dLat = d2r(lat2 - lat1);
    const dLon = d2r(lon2 - lon1);
    const a = Math.sin(dLat / 2) ** 2
        + Math.cos(d2r(lat1)) * Math.cos(d2r(lat2)) * Math.sin(dLon / 2) ** 2;
    return 2 * r * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

export const resolveUnifiedLocationForPopularPlace = (place, unifiedLocations = []) => {
    if (!place || !Array.isArray(unifiedLocations) || unifiedLocations.length === 0) {
        return null;
    }

    const requestedUnifiedId = toInt(place.unified_location_id);
    if (requestedUnifiedId) {
        const byId = unifiedLocations.find((location) => toInt(location.unified_location_id) === requestedUnifiedId);
        if (byId) {
            return byId;
        }
    }

    const placeName = normalizeText(place.place_name);
    const placeCity = normalizeText(place.city);
    const placeCountry = normalizeText(place.country);
    const placeLat = toNumber(place.latitude);
    const placeLng = toNumber(place.longitude);

    const providerReadyLocations = unifiedLocations.filter((location) => Array.isArray(location.providers) && location.providers.length > 0);
    const pool = providerReadyLocations.length > 0 ? providerReadyLocations : unifiedLocations;

    const exactByName = pool.find((location) => {
        const sameName = normalizeText(location.name) === placeName;
        const sameCountry = !placeCountry || normalizeText(location.country) === placeCountry;
        const sameCity = !placeCity || normalizeText(location.city) === placeCity;
        return sameName && sameCountry && sameCity;
    });
    if (exactByName) {
        return exactByName;
    }

    const cityCountryCandidates = pool.filter((location) => {
        const sameCountry = !placeCountry || normalizeText(location.country) === placeCountry;
        const sameCity = !placeCity || normalizeText(location.city) === placeCity;
        return sameCountry && sameCity;
    });

    const countryCandidates = pool.filter((location) => {
        if (!placeCountry) {
            return true;
        }
        return normalizeText(location.country) === placeCountry;
    });

    const distanceCandidates = cityCountryCandidates.length > 0
        ? cityCountryCandidates
        : (countryCandidates.length > 0 ? countryCandidates : pool);

    if (placeLat !== null && placeLng !== null) {
        const nearest = distanceCandidates
            .map((location) => ({
                location,
                distance: haversineKm(
                    placeLat,
                    placeLng,
                    toNumber(location.latitude) ?? placeLat,
                    toNumber(location.longitude) ?? placeLng,
                ),
            }))
            .sort((left, right) => left.distance - right.distance)[0];

        if (nearest?.location) {
            return nearest.location;
        }
    }

    if (placeName) {
        const fuzzyByName = pool.find((location) => {
            const locationName = normalizeText(location.name);
            return locationName.includes(placeName) || placeName.includes(locationName);
        });
        if (fuzzyByName) {
            return fuzzyByName;
        }
    }

    return distanceCandidates[0] ?? null;
};

const buildDefaultDates = (now = new Date()) => {
    const pickupDate = new Date(now);
    pickupDate.setDate(pickupDate.getDate() + 1);

    const returnDate = new Date(pickupDate);
    returnDate.setDate(returnDate.getDate() + 1);

    const format = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    return {
        date_from: format(pickupDate),
        date_to: format(returnDate),
    };
};

export const buildPopularPlaceSearchUrl = (place, unifiedLocations = [], options = {}) => {
    if (!place) {
        return null;
    }

    const resolvedLocation = resolveUnifiedLocationForPopularPlace(place, unifiedLocations);
    const fallbackUnifiedId = toInt(place.unified_location_id);

    const activeLocation = resolvedLocation ?? (
        fallbackUnifiedId
            ? {
                unified_location_id: fallbackUnifiedId,
                name: place.place_name,
                city: place.city,
                country: place.country,
                latitude: place.latitude,
                longitude: place.longitude,
                providers: [],
            }
            : null
    );

    const unifiedLocationId = toInt(activeLocation?.unified_location_id);
    if (!unifiedLocationId) {
        return null;
    }

    const where = activeLocation.name || place.place_name || '';
    const dateDefaults = buildDefaultDates(options.now);
    const firstProvider = Array.isArray(activeLocation.providers) && activeLocation.providers.length > 0
        ? activeLocation.providers[0]
        : null;

    const params = {
        where,
        city: activeLocation.city || place.city || '',
        country: activeLocation.country || place.country || '',
        latitude: activeLocation.latitude ?? place.latitude ?? null,
        longitude: activeLocation.longitude ?? place.longitude ?? null,
        provider: 'mixed',
        unified_location_id: String(unifiedLocationId),
        dropoff_unified_location_id: String(unifiedLocationId),
        dropoff_where: where,
        date_from: dateDefaults.date_from,
        date_to: dateDefaults.date_to,
        start_time: options.start_time || '09:00',
        end_time: options.end_time || '09:00',
        age: String(options.age || 35),
    };

    if (firstProvider?.pickup_id) {
        params.provider_pickup_id = String(firstProvider.pickup_id);
        params.dropoff_location_id = String(firstProvider.pickup_id);
    }

    const cleanedParams = Object.fromEntries(
        Object.entries(params).filter(([, value]) => value !== null && value !== undefined && value !== ''),
    );

    return `/s?${new URLSearchParams(cleanedParams).toString()}`;
};

