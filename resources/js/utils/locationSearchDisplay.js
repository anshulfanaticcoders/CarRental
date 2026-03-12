const NUMERIC_ONLY_PATTERN = /^\d+$/;
const MULTI_SPACE_PATTERN = /\s+/g;

const SUFFIX_PATTERNS = {
    airport: /\s+airport(?:\s+\([^)]+\))?$/i,
    downtown: /\s+downtown$/i,
    port: /\s+port$/i,
    train_station: /\s+train station$/i,
};

function normalizeText(value) {
    if (typeof value !== 'string') {
        return '';
    }

    return value.replace(MULTI_SPACE_PATTERN, ' ').trim();
}

function getAliases(result) {
    if (!Array.isArray(result?.aliases)) {
        return [];
    }

    return [...new Set(result.aliases.map(normalizeText).filter(Boolean))];
}

function getPreferredBaseName(result) {
    const name = normalizeText(result?.name);

    if (result?.location_type !== 'downtown') {
        return name;
    }

    const downtownAlias = getAliases(result)
        .filter((alias) => /downtown/i.test(alias))
        .sort((left, right) => right.length - left.length)[0];

    if (downtownAlias && downtownAlias.length > name.length) {
        return downtownAlias;
    }

    return name;
}

function sanitizeCountry(country) {
    const normalizedCountry = normalizeText(country);

    if (!normalizedCountry || NUMERIC_ONLY_PATTERN.test(normalizedCountry)) {
        return null;
    }

    return normalizedCountry;
}

function deriveCityFromLabel(label, locationType) {
    const normalizedLabel = normalizeText(label);
    const suffixPattern = SUFFIX_PATTERNS[locationType];

    if (!normalizedLabel || !suffixPattern) {
        return null;
    }

    const strippedLabel = normalizeText(normalizedLabel.replace(suffixPattern, ''));
    return strippedLabel || null;
}

function isSuspiciousCity(city, label) {
    const normalizedCity = normalizeText(city);
    const normalizedLabel = normalizeText(label).toLowerCase();

    if (!normalizedCity) {
        return true;
    }

    if (normalizedLabel.includes(normalizedCity.toLowerCase())) {
        return false;
    }

    return normalizedCity.length <= 3;
}

export function formatLocationPrimaryLabel(result) {
    const baseName = getPreferredBaseName(result);
    const iata = normalizeText(result?.iata).toUpperCase();

    if (result?.location_type === 'airport' && iata) {
        if (baseName.includes(`(${iata})`) || new RegExp(`\\b${iata}\\b`, 'i').test(baseName)) {
            return baseName;
        }

        return `${baseName} (${iata})`;
    }

    return baseName;
}

export function getLocationSelectionData(result) {
    const displayName = formatLocationPrimaryLabel(result);
    const fallbackLabel = getPreferredBaseName(result);
    const fallbackCity = (deriveCityFromLabel(fallbackLabel, result?.location_type) ?? normalizeText(result?.city)) || null;
    const city = isSuspiciousCity(result?.city, fallbackLabel)
        ? fallbackCity
        : normalizeText(result?.city);

    return {
        displayName,
        city: city || null,
        country: sanitizeCountry(result?.country),
    };
}

export function formatLocationSecondaryLabel(result) {
    const { city, country } = getLocationSelectionData(result);
    return [city, country].filter(Boolean).join(', ');
}

export function buildLocationInputValue(result) {
    const { displayName, city } = getLocationSelectionData(result);

    if (!city || displayName.toLowerCase().includes(city.toLowerCase())) {
        return displayName;
    }

    return `${displayName}, ${city}`;
}

export function isAirportLocation(result) {
    return result?.location_type === 'airport'
        || normalizeText(result?.name).toLowerCase().includes('airport');
}
