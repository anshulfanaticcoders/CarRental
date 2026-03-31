import { computed } from 'vue';

const normalizeExtraCode = (value) => {
    const text = `${value || ''}`.trim();
    return text ? text.toUpperCase() : '';
};

const normalizeExtraCodeList = (value) => {
    if (!value) return [];
    if (Array.isArray(value)) {
        return value.map(normalizeExtraCode).filter(Boolean);
    }
    return `${value}`
        .split(',')
        .map(normalizeExtraCode)
        .filter(Boolean);
};

const OK_MOBILITY_COVER_CODES = ['OPC', 'OPCO'];

const okMobilityBasicFeatures = [
    { label: 'Civil liability insurance', included: true },
    { label: 'Cover against damage to bodywork, windows and wheels', included: false },
    { label: 'Roadside assistance', included: false },
    { label: 'Telemedicine services', included: false },
];
const okMobilityPremiumFeatures = [
    { label: 'Civil liability insurance', included: true },
    { label: 'Cover against damage to bodywork, windows and wheels', included: true },
    { label: 'Roadside assistance', included: false },
    { label: 'Telemedicine services', included: false },
];
const okMobilitySuperPremiumFeatures = [
    { label: 'Civil liability insurance', included: true },
    { label: 'Cover against damage to bodywork, windows and wheels', included: true },
    { label: 'Roadside assistance', included: true },
    { label: 'Telemedicine services', included: true },
];

/**
 * @param {Object} props
 * @param {Object} props.vehicle
 * @param {number} props.numberOfDays
 * @param {string|null} [props.pickupLocation]
 * @param {string|null} [props.locationName]
 * @returns {import('./types').AdapterResult & {
 *   okMobilityNormalizedExtras: import('vue').ComputedRef,
 *   okMobilityCoverExtras: import('vue').ComputedRef,
 *   okMobilityIncludedLabels: import('vue').ComputedRef<string[]>,
 *   okMobilityRequiredLabels: import('vue').ComputedRef<string[]>,
 *   okMobilityAvailableLabels: import('vue').ComputedRef<string[]>,
 *   okMobilityPetExtras: import('vue').ComputedRef<string[]>,
 *   okMobilityFuelPolicy: import('vue').ComputedRef,
 *   okMobilityCancellationSummary: import('vue').ComputedRef,
 *   okMobilityInfoAvailable: import('vue').ComputedRef<boolean>,
 *   okMobilityPickupStation: import('vue').ComputedRef<string>,
 *   okMobilityDropoffStation: import('vue').ComputedRef<string>,
 *   okMobilityPickupAddress: import('vue').ComputedRef<string>,
 *   okMobilityDropoffAddress: import('vue').ComputedRef<string>,
 *   okMobilitySameLocation: import('vue').ComputedRef<boolean>,
 *   normalizeExtraCode: (value: any) => string,
 *   OK_MOBILITY_COVER_CODES: string[],
 *   getOkMobilityExtraTotal: (extra: Object) => number,
 * }}
 */
export function createOkMobilityAdapter(props) {
    const okMobilityExtrasIncluded = computed(() => normalizeExtraCodeList(props.vehicle?.extras_included));
    const okMobilityExtrasRequired = computed(() => normalizeExtraCodeList(props.vehicle?.extras_required));
    const okMobilityExtrasAvailable = computed(() => normalizeExtraCodeList(props.vehicle?.extras_available));

    const okMobilityExtrasByCode = computed(() => {
        const extras = props.vehicle?.extras || [];
        const map = new Map();
        extras.forEach((extra, index) => {
            const id = extra.id || extra.extraID || extra.extraId || extra.extra_id || extra.code || extra.extra || index;
            const code = normalizeExtraCode(extra.code || extra.extraID || extra.extraId || extra.extra_id || extra.extra || id);
            if (!code || map.has(code)) return;
            const name = extra.name || extra.extra || extra.description || extra.displayName || extra.code || code;
            const description = extra.description || extra.displayDescription || '';
            map.set(code, { name, description });
        });
        return map;
    });

    const resolveOkMobilityExtraLabel = (code) => {
        const normalized = normalizeExtraCode(code);
        const extra = okMobilityExtrasByCode.value.get(normalized);
        if (extra?.name) return extra.name;
        if (extra?.description) return extra.description;
        return normalized || code;
    };

    const okMobilityExtraLabels = (codes) => {
        const labels = (codes || []).map(resolveOkMobilityExtraLabel).filter(Boolean);
        return Array.from(new Set(labels));
    };

    const okMobilityIncludedLabels = computed(() => okMobilityExtraLabels(okMobilityExtrasIncluded.value));
    const okMobilityRequiredLabels = computed(() => okMobilityExtraLabels(okMobilityExtrasRequired.value));
    const okMobilityAvailableLabels = computed(() => {
        const labels = okMobilityExtraLabels(okMobilityExtrasAvailable.value);
        if (!labels.length) return [];
        const exclude = new Set([...okMobilityIncludedLabels.value, ...okMobilityRequiredLabels.value]);
        return labels.filter(label => !exclude.has(label));
    });

    const okMobilityPetExtras = computed(() => {
        const matches = [];
        okMobilityExtrasByCode.value.forEach((extra) => {
            const text = `${extra?.name || ''} ${extra?.description || ''}`.toLowerCase();
            if (text.includes('pet') || text.includes('pets') || text.includes('animal')) {
                matches.push(extra?.name || extra?.description);
            }
        });
        return Array.from(new Set(matches.filter(Boolean)));
    });

    const okMobilityTaxBreakdown = computed(() => {
        const total = parseFloat(props.vehicle?.preview_value ?? props.vehicle?.total_price ?? 0);
        const base = parseFloat(props.vehicle?.value_without_tax ?? 0);
        const rateRaw = props.vehicle?.tax_rate;
        const rate = rateRaw !== null && rateRaw !== undefined && rateRaw !== '' ? parseFloat(rateRaw) : null;
        let taxValue = props.vehicle?.tax_value;

        if (taxValue === null || taxValue === undefined || taxValue === '') {
            if (Number.isFinite(total) && Number.isFinite(base) && total && base) {
                taxValue = total - base;
            }
        }

        const tax = parseFloat(taxValue ?? 0);

        return {
            base: Number.isFinite(base) && base > 0 ? base : null,
            tax: Number.isFinite(tax) && tax > 0 ? tax : null,
            total: Number.isFinite(total) && total > 0 ? total : null,
            rate: Number.isFinite(rate) && rate > 0 ? rate : null,
        };
    });

    const okMobilityPickupStation = computed(() => props.vehicle?.pickup_station_name || props.vehicle?.station || '');
    const okMobilityDropoffStation = computed(() => props.vehicle?.dropoff_station_name || props.vehicle?.station || '');
    const okMobilityPickupAddress = computed(() => props.vehicle?.pickup_address || '');
    const okMobilityDropoffAddress = computed(() => props.vehicle?.dropoff_address || '');
    const okMobilitySameLocation = computed(() => {
        return okMobilityPickupStation.value === okMobilityDropoffStation.value
            && okMobilityPickupAddress.value === okMobilityDropoffAddress.value;
    });

    const okMobilityFuelPolicy = computed(() => props.vehicle?.fuel_policy || null);

    const okMobilityCancellation = computed(() => props.vehicle?.cancellation || null);

    const okMobilityCancellationSummary = computed(() => {
        const cancellation = okMobilityCancellation.value;
        if (!cancellation) return null;
        const available = cancellation.available === true || `${cancellation.available}`.toLowerCase() === 'true';
        const penalty = cancellation.penalty === true || `${cancellation.penalty}`.toLowerCase() === 'true';
        const amount = parseFloat(cancellation.amount ?? 0);
        const currency = cancellation.currency || props.vehicle?.currency || null;
        const deadline = cancellation.deadline || null;
        return {
            available,
            penalty,
            amount: Number.isFinite(amount) ? amount : null,
            currency,
            deadline
        };
    });

    const vehicleLocationText = computed(() => {
        if (!props.vehicle) return '';
        if (props.vehicle.full_vehicle_address) return props.vehicle.full_vehicle_address;
        const parts = [props.vehicle.location, props.vehicle.city, props.vehicle.state, props.vehicle.country]
            .filter(Boolean)
            .map(part => `${part}`.trim())
            .filter(part => part.length > 0);
        const fallback = parts.join(', ');
        if (fallback) return fallback;
        return props.pickupLocation || props.locationName || '';
    });

    const okMobilityInfoAvailable = computed(() => {
        const hasTaxes = okMobilityTaxBreakdown.value.total || okMobilityTaxBreakdown.value.base || okMobilityTaxBreakdown.value.tax;
        return Boolean(
            okMobilityPickupAddress.value
            || okMobilityDropoffAddress.value
            || okMobilityPickupStation.value
            || okMobilityDropoffStation.value
            || vehicleLocationText.value
            || okMobilityIncludedLabels.value.length
            || okMobilityRequiredLabels.value.length
            || okMobilityAvailableLabels.value.length
            || okMobilityPetExtras.value.length
            || okMobilityFuelPolicy.value
            || okMobilityCancellationSummary.value
            || hasTaxes
        );
    });

    const okMobilityBaseTotal = computed(() => {
        const total = parseFloat(props.vehicle?.total_price || 0);
        if (total > 0) return total;
        const daily = parseFloat(props.vehicle?.price_per_day || 0);
        return daily > 0 ? daily * props.numberOfDays : 0;
    });

    const getOkMobilityExtraTotal = (extra) => {
        if (!extra) return 0;
        if (extra.is_one_time) return parseFloat(extra.price || 0);
        const dailyRate = extra.daily_rate !== undefined
            ? parseFloat(extra.daily_rate)
            : (parseFloat(extra.price || 0) / props.numberOfDays);
        return dailyRate * props.numberOfDays;
    };

    const okMobilityNormalizedExtras = computed(() => {
        const requiredCodes = new Set(okMobilityExtrasRequired.value);
        const includedCodes = new Set(okMobilityExtrasIncluded.value);
        const availableCodes = new Set(okMobilityExtrasAvailable.value);
        const hasAvailableFilter = availableCodes.size > 0;
        const extras = props.vehicle?.extras || [];
        return extras.map((extra, index) => {
            const id = extra.id || extra.extraID || extra.extraId || extra.extra_id || extra.code || extra.extra || index;
            const codeRaw = extra.code || extra.extraID || extra.extraId || extra.extra_id || extra.extra || id;
            const code = normalizeExtraCode(codeRaw);
            const name = extra.name || extra.extra || extra.description || extra.displayName || extra.code || code || 'Extra';
            const description = extra.description || extra.displayDescription || '';
            const priceValue = parseFloat(
                extra.priceWithTax ?? extra.valueWithTax ?? extra.value ?? extra.price ?? extra.amount ?? 0
            );
            const pricePerContract = extra.pricePerContract === true || extra.pricePerContract === 'true';
            const dailyRate = pricePerContract && props.numberOfDays
                ? (priceValue / props.numberOfDays)
                : priceValue;

            const isRequired = extra.required || extra.extra_Required === 'true' || (code && requiredCodes.has(code));
            const isIncluded = extra.included || extra.extra_Included === 'true' || (code && includedCodes.has(code));

            const totalForBooking = pricePerContract
                ? priceValue
                : (dailyRate * props.numberOfDays);
            return {
                id: id,
                code: code || codeRaw || id,
                name,
                description,
                price: priceValue,
                daily_rate: dailyRate,
                total_for_booking: totalForBooking,
                amount: priceValue,
                included: isIncluded,
                required: isRequired,
                is_one_time: pricePerContract
            };
        }).filter(extra => {
            if (!extra) return false;
            if (extra.required || extra.included) return true;
            if (hasAvailableFilter) {
                return availableCodes.has(normalizeExtraCode(extra.code));
            }
            return extra.price > 0;
        });
    });

    const okMobilityCoverExtras = computed(() => {
        const coverCodes = new Set(OK_MOBILITY_COVER_CODES);
        return okMobilityNormalizedExtras.value.filter(extra => coverCodes.has(normalizeExtraCode(extra.code)));
    });

    const packages = computed(() => {
        const pkgs = [
            {
                type: 'BAS',
                name: 'Basic Cover',
                subtitle: 'Basic Cover',
                total: okMobilityBaseTotal.value,
                deposit: 0,
                benefits: [],
                coverFeatures: okMobilityBasicFeatures,
                isBestValue: okMobilityCoverExtras.value.length === 0,
                isAddOn: false
            }
        ];

        okMobilityCoverExtras.value.forEach((extra) => {
            const extraTotal = getOkMobilityExtraTotal(extra);
            const code = normalizeExtraCode(extra.code);
            const isSuperPremium = code === 'OPCO';
            pkgs.push({
                type: code || extra.code,
                name: extra.name || (isSuperPremium ? 'Super Premium Cover' : 'Premium Cover'),
                subtitle: 'Excess Waiver',
                total: okMobilityBaseTotal.value + extraTotal,
                deposit: 0,
                benefits: [],
                coverFeatures: isSuperPremium ? okMobilitySuperPremiumFeatures : okMobilityPremiumFeatures,
                isBestValue: code === 'OPC',
                isAddOn: false,
                extraId: extra.id
            });
        });

        return pkgs;
    });

    const optionalExtras = computed(() => {
        const coverCodes = new Set(OK_MOBILITY_COVER_CODES);
        return okMobilityNormalizedExtras.value.filter(extra => !coverCodes.has(normalizeExtraCode(extra.code)));
    });

    const protectionPlans = computed(() => []);

    const allExtras = okMobilityNormalizedExtras;

    const baseTotal = okMobilityBaseTotal;

    const mandatoryAmount = computed(() => 0);

    const includedItems = computed(() => {
        const items = [];
        if (okMobilityFuelPolicy.value) {
            items.push({ label: `Fuel Policy: ${okMobilityFuelPolicy.value}`, detail: null });
        }
        const cancellation = okMobilityCancellationSummary.value;
        if (cancellation) {
            if (cancellation.available && !cancellation.penalty) {
                items.push({ label: 'Free Cancellation', detail: cancellation.deadline ? `Until ${cancellation.deadline}` : null });
            } else if (cancellation.available && cancellation.penalty && cancellation.amount) {
                const curr = cancellation.currency || '';
                items.push({ label: 'Cancellation Available', detail: `Penalty: ${cancellation.amount} ${curr}`.trim() });
            }
        }
        okMobilityIncludedLabels.value.forEach(label => {
            items.push({ label, detail: 'Included' });
        });
        return items;
    });

    const taxBreakdown = computed(() => {
        const tb = okMobilityTaxBreakdown.value;
        if (!tb.base && !tb.tax && !tb.total) return null;
        const items = [];
        if (tb.base) items.push({ label: 'Base (excl. tax)', amount: tb.base });
        if (tb.tax) items.push({ label: tb.rate ? `Tax (${tb.rate}%)` : 'Tax', amount: tb.tax });
        if (tb.total) items.push({ label: 'Total (incl. tax)', amount: tb.total });
        return { taxRate: tb.rate, taxAmount: tb.tax, taxLabel: tb.rate ? `${tb.rate}% tax` : 'Tax', items };
    });

    const locationData = computed(() => ({
        pickupStation: okMobilityPickupStation.value || null,
        pickupAddress: okMobilityPickupAddress.value || null,
        pickupLines: [],
        pickupPhone: null,
        pickupEmail: null,
        dropoffStation: okMobilityDropoffStation.value || null,
        dropoffAddress: okMobilityDropoffAddress.value || null,
        dropoffLines: [],
        dropoffPhone: null,
        dropoffEmail: null,
        sameLocation: okMobilitySameLocation.value,
        fuelPolicy: okMobilityFuelPolicy.value,
        cancellation: okMobilityCancellationSummary.value,
        officeHours: null,
        pickupInstructions: null,
        dropoffInstructions: null,
    }));

    const highlights = computed(() => {
        const items = [];
        if (okMobilityFuelPolicy.value) {
            items.push({ label: 'Fuel Policy', value: okMobilityFuelPolicy.value, type: 'neutral' });
        }
        const cancellation = okMobilityCancellationSummary.value;
        if (cancellation?.available) {
            items.push({
                label: 'Cancellation',
                value: cancellation.penalty ? 'Available (with penalty)' : 'Free cancellation',
                type: cancellation.penalty ? 'warning' : 'positive'
            });
        }
        if (okMobilityPetExtras.value.length) {
            items.push({ label: 'Pet Friendly', value: okMobilityPetExtras.value.join(', '), type: 'positive' });
        }
        return items;
    });

    const computeNetTotal = (extrasTotal) => {
        return okMobilityBaseTotal.value + extrasTotal;
    };

    return {
        // Standard adapter interface
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal,
        // OkMobility-specific exports
        okMobilityNormalizedExtras, okMobilityCoverExtras,
        okMobilityIncludedLabels, okMobilityRequiredLabels, okMobilityAvailableLabels,
        okMobilityPetExtras, okMobilityFuelPolicy, okMobilityCancellationSummary,
        okMobilityInfoAvailable, okMobilityPickupStation, okMobilityDropoffStation,
        okMobilityPickupAddress, okMobilityDropoffAddress, okMobilitySameLocation,
        normalizeExtraCode, OK_MOBILITY_COVER_CODES, getOkMobilityExtraTotal,
    };
}
