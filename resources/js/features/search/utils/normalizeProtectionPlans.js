const GM_PACKAGE_NAMES = { BAS: 'Basic', PLU: 'Plus', PRE: 'Premium', PMP: 'Premium Plus' };
const GM_PACKAGE_ORDER = ['BAS', 'PLU', 'PRE', 'PMP'];
const LOCAUTO_PROTECTION_CODES = ['136', '147', '145', '140', '146', '6', '43'];

const parseNum = (v, fallback = 0) => { const n = parseFloat(v); return Number.isFinite(n) ? n : fallback; };

const shortName = (description) => {
    if (!description) return '';
    return description.includes('/') ? description.split('/')[0].trim() : description;
};

const getLegacyPayload = (vehicle) => {
    return vehicle?.booking_context?.provider_payload || vehicle?.legacyPayload || {};
};

/**
 * Normalize protection plans from any provider into a canonical format.
 * Returns [] for providers that have no plan selection (renteon, xdrive, etc.).
 */
export const normalizeProtectionPlans = ({ vehicle, rentalDays = 1, selectedId = null, convertPrice = (a) => a }) => {
    const source = `${vehicle?.source ?? ''}`.trim().toLowerCase();
    const days = Math.max(1, rentalDays);

    if (source === 'greenmotion' || source === 'usave') {
        return normalizeGM(vehicle, days, selectedId, convertPrice);
    }
    if (source === 'locauto_rent') {
        return normalizeLocauto(vehicle, days, selectedId, convertPrice);
    }
    if (source === 'adobe') {
        return normalizeAdobe(vehicle, days, selectedId, convertPrice);
    }
    if (source === 'internal') {
        return normalizeInternal(vehicle, days, selectedId, convertPrice);
    }

    return [];
};

function normalizeGM(vehicle, days, selectedId, convertPrice) {
    const products = vehicle?.products || [];
    const currency = vehicle?.currency || 'USD';

    return GM_PACKAGE_ORDER
        .map(type => products.find(p => p.type === type))
        .filter(Boolean)
        .map(product => {
            const total = parseNum(product.total);
            const cur = product.currency || currency;
            return {
                id: product.type,
                name: GM_PACKAGE_NAMES[product.type] || product.type,
                subtitle: product.type,
                dailyPrice: convertPrice(total / days, cur),
                totalPrice: convertPrice(total, cur),
                currency: cur,
                benefits: buildGMBenefits(product, convertPrice, cur),
                deposit: product.deposit ? convertPrice(parseNum(product.deposit), cur) : null,
                isSelected: selectedId === product.type,
                isBasic: product.type === 'BAS',
            };
        });
}

function buildGMBenefits(product, convertPrice, currency) {
    const benefits = [];
    const excess = parseNum(product.excess, null);
    if (excess === 0) {
        benefits.push('Glass and tyres covered');
    } else if (excess !== null) {
        benefits.push(`Excess: ${convertPrice(excess, currency).toFixed(2)}`);
    }
    if (product.debitcard === 'Y') benefits.push('Debit Card Accepted');
    if (product.fuelpolicy === 'FF') benefits.push('Free Fuel / Full to Full');
    else if (product.fuelpolicy === 'SL') benefits.push('Like for Like fuel policy');
    if (parseNum(product.costperextradistance) === 0) benefits.push('Unlimited mileage');
    if (product.type === 'BAS') { benefits.push('Non-refundable'); benefits.push('Non-amendable'); }
    if (['PLU', 'PRE', 'PMP'].includes(product.type)) benefits.push('Cancellation in line with T&Cs');
    return benefits;
}

function normalizeLocauto(vehicle, days, selectedId, convertPrice) {
    const currency = vehicle?.currency || 'EUR';
    const pricePerDay = parseNum(vehicle?.price_per_day);
    const totalPrice = parseNum(vehicle?.total_price);
    const extras = vehicle?.extras || [];
    const protections = extras.filter(e => LOCAUTO_PROTECTION_CODES.includes(e.code) && parseNum(e.amount) > 0);

    const plans = [{
        id: 'BAS',
        name: 'Basic Coverage',
        subtitle: 'Standard',
        dailyPrice: convertPrice(pricePerDay, currency),
        totalPrice: convertPrice(totalPrice, currency),
        currency,
        benefits: ['Standard protection included'],
        deposit: null,
        isSelected: selectedId === null || selectedId === 'BAS',
        isBasic: true,
    }];

    protections.forEach(p => {
        const amount = parseNum(p.amount);
        plans.push({
            id: p.code,
            name: shortName(p.description),
            subtitle: p.code,
            dailyPrice: convertPrice(pricePerDay + amount, currency),
            totalPrice: convertPrice(totalPrice + amount * days, currency),
            currency,
            benefits: [p.description],
            deposit: null,
            isSelected: selectedId === p.code,
            isBasic: false,
        });
    });

    return plans;
}

function normalizeAdobe(vehicle, days, selectedId, convertPrice) {
    const baseTotal = parseNum(vehicle?.tdr);
    const protectionDefs = [
        { code: 'LDW', name: 'Car Protection', field: 'ldw' },
        { code: 'SPP', name: 'Extended Protection', field: 'spp' },
    ];

    const plans = [{
        id: 'BAS',
        name: 'Basic Rate',
        subtitle: 'BAS',
        dailyPrice: convertPrice(baseTotal / days, 'USD'),
        totalPrice: convertPrice(baseTotal, 'USD'),
        currency: 'USD',
        benefits: ['Base rental rate only'],
        deposit: null,
        isSelected: selectedId === null || selectedId === 'BAS',
        isBasic: true,
    }];

    protectionDefs.forEach(def => {
        const amount = parseNum(vehicle?.[def.field], null);
        if (amount === null) return;
        const total = baseTotal + amount;
        plans.push({
            id: def.code,
            name: def.name,
            subtitle: def.code,
            dailyPrice: convertPrice(total / days, 'USD'),
            totalPrice: convertPrice(total, 'USD'),
            currency: 'USD',
            benefits: ['Includes Liability Protection (PLI)', 'Optional Protection'],
            deposit: null,
            isSelected: selectedId === def.code,
            isBasic: false,
        });
    });

    return plans;
}

function normalizeInternal(vehicle, days, selectedId, convertPrice) {
    const pricePerDay = parseNum(vehicle?.pricing?.price_per_day);
    const deposit = parseNum(vehicle?.pricing?.deposit_amount || getLegacyPayload(vehicle)?.benefits?.deposit_amount || getLegacyPayload(vehicle)?.security_deposit);
    const currency = vehicle?.pricing?.currency || 'USD';
    const vendorPlans = getLegacyPayload(vehicle)?.vendorPlans || getLegacyPayload(vehicle)?.vendor_plans || [];

    const plans = [{
        id: 'BAS',
        name: 'Basic Rental',
        subtitle: 'Standard Package',
        dailyPrice: convertPrice(pricePerDay, currency),
        totalPrice: convertPrice(pricePerDay * days, currency),
        currency,
        benefits: ['Base rental rate', 'Standard coverage'],
        deposit: deposit ? convertPrice(deposit, currency) : null,
        isSelected: selectedId === null || selectedId === 'BAS',
        isBasic: true,
    }];

    vendorPlans.forEach(plan => {
        const features = plan.features ? (typeof plan.features === 'string' ? JSON.parse(plan.features) : plan.features) : [];
        const planPrice = parseNum(plan.price);
        plans.push({
            id: `${plan.id}`,
            name: plan.plan_type || 'Custom Plan',
            subtitle: plan.plan_description || 'Vendor Package',
            dailyPrice: convertPrice(planPrice, currency),
            totalPrice: convertPrice(planPrice * days, currency),
            currency,
            benefits: features.length > 0 ? features : ['Custom vendor package'],
            deposit: deposit ? convertPrice(deposit, currency) : null,
            isSelected: selectedId === `${plan.id}`,
            isBasic: false,
        });
    });

    return plans;
}
