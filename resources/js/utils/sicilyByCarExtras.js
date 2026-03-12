const NUMBERED_DESCRIPTION_PATTERN = /^(.*?)(?:\s*#\s*(\d+))$/;
const NUMBERED_CODE_PATTERN = /^([A-Z]+)(\d+)$/i;

const parseAmount = (value) => {
    const parsed = parseFloat(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};

const cleanNumberedLabel = (value) => {
    const text = `${value || ''}`.trim();
    const match = text.match(NUMBERED_DESCRIPTION_PATTERN);
    return (match?.[1] || text).trim();
};

const buildBaseServiceExtra = (service, index, numberOfDays) => {
    const serviceId = `${service?.service_id ?? service?.id ?? service?.option_id ?? `EXTRA_${index}`}`.trim();
    const code = `${service?.id ?? service?.service_id ?? service?.option_id ?? `EXTRA_${index}`}`.trim().toUpperCase();
    const name = `${service?.description || service?.name || code}`.trim();
    const total = parseAmount(service?.total ?? service?.amount ?? service?.price ?? 0);

    return {
        id: `sbc_extra_${code}_${index}`,
        option_id: service?.option_id ?? serviceId,
        service_id: serviceId,
        code,
        name,
        description: service?.description || service?.name || '',
        price: total,
        daily_rate: numberOfDays ? total / numberOfDays : total,
        total_for_booking: total,
        amount: total,
        required: false,
        payment: service?.payment || null,
        currency: service?.currency || null,
        prepay_available: service?.prepay_available ?? null,
        purpose: service?.purpose ?? null,
        excess: service?.excess ?? null,
    };
};

const getNumberedSlotDescriptor = (service) => {
    const code = `${service?.id ?? service?.service_id ?? service?.option_id ?? ''}`.trim().toUpperCase();
    const description = `${service?.description || service?.name || ''}`.trim();
    const codeMatch = code.match(NUMBERED_CODE_PATTERN);
    const descriptionMatch = description.match(NUMBERED_DESCRIPTION_PATTERN);

    if (!descriptionMatch) {
        return null;
    }

    const cleanName = cleanNumberedLabel(description);
    if (!cleanName) {
        return null;
    }

    return {
        groupCode: (codeMatch?.[1] || cleanName.replace(/[^A-Za-z0-9]+/g, '_')).toUpperCase(),
        slotNumber: parseInt(descriptionMatch[2] || codeMatch?.[2] || '0', 10) || 0,
        cleanName,
    };
};

export const getSicilyByCarExtraTotal = (extra) => {
    if (!extra) return 0;

    if (extra.total_for_booking !== undefined && extra.total_for_booking !== null) {
        return parseAmount(extra.total_for_booking);
    }

    if (extra.daily_rate !== undefined && extra.daily_rate !== null) {
        return parseAmount(extra.daily_rate) * (parseInt(extra.number_of_days ?? 0, 10) || 0);
    }

    return parseAmount(extra.price);
};

export const buildSicilyByCarOptionalExtras = (services = [], numberOfDays = 1) => {
    const extras = [];
    const groupedByKey = new Map();

    services.forEach((service, index) => {
        const baseExtra = buildBaseServiceExtra(service, index, numberOfDays);
        const numberedSlot = getNumberedSlotDescriptor(service);

        if (!numberedSlot) {
            extras.push({
                ...baseExtra,
                description: baseExtra.description && baseExtra.description !== baseExtra.name ? baseExtra.description : '',
                numberAllowed: 1,
                maxQuantity: 1,
            });
            return;
        }

        const groupKey = `${numberedSlot.groupCode}:${numberedSlot.cleanName.toLowerCase()}`;
        let groupedExtra = groupedByKey.get(groupKey);

        if (!groupedExtra) {
            groupedExtra = {
                ...baseExtra,
                id: `sbc_group_${numberedSlot.groupCode}`,
                code: numberedSlot.groupCode,
                name: numberedSlot.cleanName,
                description: '',
                service_slots: [],
                numberAllowed: 0,
                maxQuantity: 0,
            };
            groupedByKey.set(groupKey, groupedExtra);
            extras.push(groupedExtra);
        }

        groupedExtra.service_slots.push({
            ...baseExtra,
            id: baseExtra.service_id,
            option_id: baseExtra.option_id,
            service_id: baseExtra.service_id,
            name: numberedSlot.cleanName,
            description: '',
            slot_number: numberedSlot.slotNumber,
        });

        groupedExtra.service_slots.sort((left, right) => left.slot_number - right.slot_number);
        groupedExtra.numberAllowed = groupedExtra.service_slots.length;
        groupedExtra.maxQuantity = groupedExtra.service_slots.length;

        const firstSlot = groupedExtra.service_slots[0];
        groupedExtra.price = firstSlot?.price ?? groupedExtra.price;
        groupedExtra.daily_rate = firstSlot?.daily_rate ?? groupedExtra.daily_rate;
        groupedExtra.total_for_booking = firstSlot?.total_for_booking ?? groupedExtra.total_for_booking;
        groupedExtra.amount = firstSlot?.amount ?? groupedExtra.amount;
        groupedExtra.currency = firstSlot?.currency ?? groupedExtra.currency;
        groupedExtra.payment = firstSlot?.payment ?? groupedExtra.payment;
    });

    return extras;
};

export const expandSicilyByCarSelectedExtras = (selectedExtras = {}, displayExtras = []) => {
    const extrasById = new Map(displayExtras.map((extra) => [extra.id, extra]));
    const expandedExtras = [];

    Object.entries(selectedExtras).forEach(([id, quantity]) => {
        const qty = parseInt(quantity ?? 0, 10) || 0;
        if (qty <= 0) {
            return;
        }

        const extra = extrasById.get(id);
        if (!extra) {
            return;
        }

        if (Array.isArray(extra.service_slots) && extra.service_slots.length > 0) {
            extra.service_slots.slice(0, qty).forEach((slot) => {
                expandedExtras.push({
                    id: slot.service_id ?? slot.option_id ?? slot.id,
                    option_id: slot.option_id ?? slot.service_id ?? slot.id,
                    service_id: slot.service_id ?? slot.option_id ?? slot.id,
                    name: extra.name,
                    qty: 1,
                    total: getSicilyByCarExtraTotal(slot),
                    total_for_booking: slot.total_for_booking ?? null,
                    daily_rate: slot.daily_rate ?? null,
                    price: slot.price ?? null,
                    excess: slot.excess ?? null,
                    currency: slot.currency ?? extra.currency ?? null,
                    required: extra.required || false,
                    numberAllowed: 1,
                    prepay_available: slot.prepay_available ?? extra.prepay_available ?? null,
                    code: slot.code,
                    purpose: slot.purpose ?? extra.purpose ?? null,
                    payment: slot.payment ?? extra.payment ?? null,
                });
            });
            return;
        }

        expandedExtras.push({
            id: extra.service_id ?? extra.option_id ?? extra.id,
            option_id: extra.option_id ?? extra.service_id ?? extra.id,
            service_id: extra.service_id ?? extra.option_id ?? extra.id,
            name: extra.name,
            qty,
            total: getSicilyByCarExtraTotal(extra) * qty,
            total_for_booking: extra.total_for_booking ?? null,
            daily_rate: extra.daily_rate ?? null,
            price: extra.price ?? null,
            excess: extra.excess ?? null,
            currency: extra.currency ?? null,
            required: extra.required || false,
            numberAllowed: extra.numberAllowed ?? null,
            prepay_available: extra.prepay_available ?? null,
            code: extra.code,
            purpose: extra.purpose ?? null,
            payment: extra.payment ?? null,
        });
    });

    return expandedExtras;
};
