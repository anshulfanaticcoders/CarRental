const cleanText = (value) => {
    if (value === null || value === undefined) return '';
    return `${value}`.replace(/\s+/g, ' ').trim();
};

const normalizeKey = (value) => cleanText(value)
    .toLowerCase()
    .replace(/[.,;:]+$/g, '')
    .replace(/\s+/g, ' ');

const toLines = (value) => {
    if (!value) return [];
    if (Array.isArray(value)) return value.flatMap(toLines);
    return cleanText(value) ? [cleanText(value)] : [];
};

const uniqueLines = (...groups) => {
    const seen = new Set();
    const lines = [];

    groups.flatMap(toLines).forEach((line) => {
        const key = normalizeKey(line);
        if (!key || seen.has(key)) return;
        seen.add(key);
        lines.push(line);
    });

    return lines;
};

const firstUnique = (values, existing = []) => {
    const existingKeys = new Set(uniqueLines(existing).map(normalizeKey));
    return uniqueLines(values).find((value) => !existingKeys.has(normalizeKey(value))) || null;
};

const detailsAddressLines = (details = {}) => uniqueLines(
    details.address,
    details.address_1,
    details.address_2,
    details.address_3,
    details.address_city,
    details.address_county,
    details.address_postcode,
    details.address_country || details.country
);

const contactFrom = (...sources) => {
    const contact = sources.reduce((carry, source = {}) => ({
        phone: carry.phone || cleanText(source.telephone || source.phone),
        email: carry.email || cleanText(source.email),
        whatsapp: carry.whatsapp || cleanText(source.whatsapp),
        iata: carry.iata || cleanText(source.iata),
    }), {});

    return {
        phone: contact.phone || null,
        email: contact.email || null,
        whatsapp: contact.whatsapp || null,
        iata: contact.iata || null,
    };
};

const hasContact = (contact = {}) => Boolean(contact.phone || contact.email || contact.whatsapp || contact.iata);

const hasHours = (details = {}) => Boolean(
    (Array.isArray(details.opening_hours) && details.opening_hours.length)
    || (Array.isArray(details.office_opening_hours) && details.office_opening_hours.length)
    || (Array.isArray(details.out_of_hours) && details.out_of_hours.length)
    || (Array.isArray(details.out_of_hours_dropoff) && details.out_of_hours_dropoff.length)
    || details.out_of_hours_charge
);

const emptyContact = () => ({
    phone: null,
    email: null,
    whatsapp: null,
    iata: null,
});

const canonicalPoint = (vehicle, type) => {
    const location = vehicle?.location;
    if (!location || typeof location === 'string') return {};
    return location[type] || {};
};

const adapterPoint = (adapterLocationData = {}, type) => {
    const prefix = type === 'pickup' ? 'pickup' : 'dropoff';
    return {
        label: adapterLocationData?.[`${prefix}Station`],
        addressLines: uniqueLines(
            adapterLocationData?.[`${prefix}Address`],
            adapterLocationData?.[`${prefix}Lines`]
        ),
        contact: {
            phone: adapterLocationData?.[`${prefix}Phone`],
            email: adapterLocationData?.[`${prefix}Email`],
        },
        instructions: adapterLocationData?.[`${prefix}Instructions`],
    };
};

const buildPoint = (type, input = {}) => {
    const vehicle = input.vehicle || {};
    const details = type === 'pickup' ? input.locationDetails || {} : input.dropoffLocationDetails || {};
    const provider = type === 'pickup' ? input.pickupProviderDetails || {} : input.dropoffProviderDetails || {};
    const adapter = adapterPoint(input.adapterLocationData, type);
    const canonical = canonicalPoint(vehicle, type);
    const formLabel = type === 'pickup'
        ? (input.pickupLocation || input.locationName)
        : (input.dropoffLocation || input.pickupLocation || input.locationName);

    const label = firstUnique([
        provider.label,
        details.name,
        canonical.name,
        adapter.label,
        formLabel,
    ]);

    const addressLines = uniqueLines(
        provider.addressLines,
        detailsAddressLines(details),
        canonical.address,
        canonical.city,
        canonical.country,
        adapter.addressLines,
        type === 'pickup' ? input.locationDetailLines : []
    );

    const parkingAddress = type === 'pickup'
        ? firstUnique([
            provider.parkingAddress,
            vehicle.full_vehicle_address,
            input.vehicleLocationText,
        ], [label, ...addressLines])
        : firstUnique([
            provider.parkingAddress,
            vehicle.dropoff_address,
        ], [label, ...addressLines]);

    const instructions = firstUnique([
        provider.instructions,
        type === 'pickup' ? details.pickup_instructions : details.dropoff_instructions,
        type === 'pickup' ? details.collection_details : null,
        type === 'pickup' ? input.locationInstructions : input.dropoffInstructions,
        adapter.instructions,
        type === 'pickup' ? vehicle.pickup_instructions : vehicle.dropoff_instructions,
    ]);

    const contact = contactFrom(
        provider.contact,
        details,
        adapter.contact,
        type === 'pickup' ? input.locationContact : {}
    );

    return {
        label,
        addressLines,
        parkingAddress,
        instructions,
        contact,
        hasContact: hasContact(contact),
        hasHours: hasHours(details),
        summaryLines: uniqueLines(label, addressLines, parkingAddress),
    };
};

const formIsOneWay = (input = {}) => Boolean(
    !input.sameLocation
    && normalizeKey(input.dropoffLocation)
    && normalizeKey(input.pickupLocation)
    && normalizeKey(input.dropoffLocation) !== normalizeKey(input.pickupLocation)
);

const pointLooksLikePickup = (dropoff = {}, pickup = {}) => {
    const pickupKeys = new Set((pickup.summaryLines || []).map(normalizeKey).filter(Boolean));
    const dropoffLines = dropoff.summaryLines || [];
    const labelMatches = normalizeKey(dropoff.label) && normalizeKey(dropoff.label) === normalizeKey(pickup.label);
    const lineMatchesPickup = dropoffLines.some((line) => {
        const key = normalizeKey(line);
        return key.length >= 12 && pickupKeys.has(key);
    });

    return labelMatches || lineMatchesPickup;
};

const formOnlyDropoffPoint = (input = {}, fallback = {}) => {
    const label = cleanText(input.dropoffLocation) || fallback.label || null;

    return {
        label,
        addressLines: [],
        parkingAddress: null,
        instructions: null,
        contact: emptyContact(),
        hasContact: false,
        hasHours: false,
        summaryLines: uniqueLines(label),
    };
};

export const createBookingLocationDisplay = (input = {}) => {
    const pickup = buildPoint('pickup', input);
    const rawDropoff = buildPoint('dropoff', input);
    const oneWay = formIsOneWay(input);
    const dropoff = oneWay && pointLooksLikePickup(rawDropoff, pickup)
        ? formOnlyDropoffPoint(input, rawDropoff)
        : rawDropoff;
    const sameLocation = Boolean(input.sameLocation)
        || normalizeKey(input.dropoffLocation) === normalizeKey(input.pickupLocation)
        || (!oneWay && normalizeKey(dropoff.label) === normalizeKey(pickup.label));
    const dropoffRepeatsPickup = sameLocation && pointLooksLikePickup(dropoff, pickup);

    const hasDropoffDetails = Boolean(
        dropoff.addressLines.length
        || dropoff.parkingAddress
        || dropoff.instructions
        || dropoff.hasContact
    );

    return {
        pickup,
        dropoff: {
            ...dropoff,
            isSameAsPickup: sameLocation && (!hasDropoffDetails || dropoffRepeatsPickup),
        },
        hasAnyDetails: Boolean(
            pickup.label
            || pickup.addressLines.length
            || pickup.parkingAddress
            || pickup.instructions
            || pickup.hasContact
            || pickup.hasHours
            || hasDropoffDetails
        ),
    };
};
