import { computed } from 'vue';
import { resolveStandardPackages, useEmptyLocationData, defaultComputeNetTotal } from './shared.js';

const toTitleCase = (value) => {
    return `${value}`
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
};

const getProviderExtraLabel = (extra) => {
    const name = `${extra?.name || extra?.service_name || extra?.title || ''}`.trim();
    if (name) return name;
    const code = `${extra?.code || extra?.service_id || ''}`.trim();
    if (code) return toTitleCase(code);
    return extra?.description || '';
};

const parseAmount = (value) => {
    const parsed = parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
};

const normalizeFavricaExtra = (extra) => {
    if (!extra) return null;
    const price = parseAmount(extra.total_for_booking ?? extra.price ?? extra.amount) ?? 0;
    const dailyRate = parseAmount(extra.daily_rate) ?? 0;
    const id = extra.id || extra.option_id || extra.code || extra.service_id || '';
    return {
        id,
        code: extra.code || extra.service_id,
        name: extra.name || extra.description || 'Extra',
        description: extra.description || extra.name || 'Optional Extra',
        price,
        daily_rate: dailyRate,
        amount: extra.amount ?? price,
        total_for_booking: extra.total_for_booking ?? price,
        currency: extra.currency,
        isIncluded: extra.included === true || extra.is_included === true || extra.included === 'true' || extra.is_included === 'true' || price <= 0,
        excess: parseAmount(extra.excess_amount ?? extra.excess),
        deposit: parseAmount(extra.deposit_amount ?? extra.deposit),
        numberAllowed: extra.numberAllowed || extra.maxQuantity || 1,
        maxQuantity: extra.numberAllowed || extra.maxQuantity || 1,
        service_id: extra.service_id || extra.code,
        type: extra.type || null
    };
};

const isFavricaInsuranceService = (extra) => {
    if (!extra) return false;
    if (extra.type === 'insurance') return true;
    const code = `${extra.code || extra.service_id || extra.service_name || extra.name || ''}`.trim().toUpperCase();
    if (['CDW', 'SCDW', 'LCF', 'PAI'].includes(code)) return true;
    const text = `${extra.name || ''} ${extra.description || ''}`.toLowerCase();
    return ['insurance', 'damage', 'waiver', 'glass', 'tire', 'tyre', 'headlight', 'fuse'].some(keyword => text.includes(keyword));
};

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @param {'favrica' | 'xdrive' | 'emr'} source
 * @returns {import('./types').AdapterResult & Record<string, any>}
 */
export function createFavricaXdriveAdapter(props, source) {
    const isFavrica = source === 'favrica';
    const isXDrive = source === 'xdrive';
    const isEmr = source === 'emr';

    // ── Service Pool ────────────────────────────────────────────────────
    const servicePool = computed(() => {
        if (isFavrica && props.vehicle?.source !== 'favrica') return [];
        if (isXDrive && props.vehicle?.source !== 'xdrive') return [];
        if (isEmr && props.vehicle?.source !== 'emr') return [];
        const raw = [
            ...(props.vehicle?.insurance_options || []),
            ...(props.vehicle?.extras || [])
        ];
        const byId = new Map();
        raw.forEach((extra) => {
            if (!extra) return;
            const key = extra.id || extra.service_id || extra.code || extra.service_name;
            if (key && !byId.has(key)) {
                byId.set(key, extra);
            }
        });
        return Array.from(byId.values());
    });

    // ── Insurance Options ───────────────────────────────────────────────
    const insuranceOptions = computed(() => {
        return servicePool.value
            .filter(isFavricaInsuranceService)
            .map(normalizeFavricaExtra)
            .filter(Boolean)
            .filter(extra => extra.price > 0);
    });

    const includedInsuranceOptions = computed(() => {
        return servicePool.value
            .filter(isFavricaInsuranceService)
            .map(normalizeFavricaExtra)
            .filter(Boolean)
            .filter(extra => extra.isIncluded || extra.price <= 0);
    });

    const insuranceExcess = computed(() => {
        return includedInsuranceOptions.value.find(extra => extra.excess !== null)?.excess
            ?? insuranceOptions.value.find(extra => extra.excess !== null)?.excess
            ?? parseAmount(props.vehicle?.benefits?.excess_amount);
    });

    const packageBenefits = computed(() => {
        const labels = includedInsuranceOptions.value.map(extra => `${getProviderExtraLabel(extra)} included`);
        return Array.from(new Set(labels.filter(Boolean)));
    });

    const enrichPackage = (pkg) => {
        if (!pkg) return pkg;
        const benefits = Array.isArray(pkg.benefits) && pkg.benefits.length > 0
            ? Array.from(new Set([...pkg.benefits, ...packageBenefits.value]))
            : packageBenefits.value;
        return {
            ...pkg,
            benefits,
            deposit: pkg.deposit ?? props.vehicle?.security_deposit ?? props.vehicle?.benefits?.deposit_amount ?? null,
            excess: pkg.excess ?? insuranceExcess.value ?? null,
        };
    };

    // ── Optional Extras ─────────────────────────────────────────────────
    const optionalExtras = computed(() => {
        return servicePool.value
            .filter(extra => !isFavricaInsuranceService(extra))
            .map(normalizeFavricaExtra)
            .filter(Boolean)
            .filter(extra => extra.price > 0);
    });

    const allExtras = computed(() => [...insuranceOptions.value, ...optionalExtras.value]);
    const packages = computed(() => {
        const standardPkgs = resolveStandardPackages(props.vehicle);
        if (standardPkgs.length > 0) return standardPkgs.map(enrichPackage);
        // Fallback: build basic package from vehicle pricing
        const total = parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
        if (total <= 0) return [];
        const currency = props.vehicle?.currency || props.vehicle?.pricing?.currency || 'EUR';
        const perDay = parseFloat(props.vehicle?.price_per_day || props.vehicle?.pricing?.price_per_day || 0)
            || (props.numberOfDays > 0 ? +(total / props.numberOfDays).toFixed(2) : total);
        return [enrichPackage({ type: 'BAS', name: 'Basic Rental', total, price_per_day: perDay, currency })];
    });
    const protectionPlans = insuranceOptions;
    const locationData = useEmptyLocationData();
    const includedItems = computed(() => includedInsuranceOptions.value.map(extra => ({
        label: getProviderExtraLabel(extra),
        detail: extra.excess !== null ? `Included; excess ${extra.currency || props.vehicle?.currency || 'EUR'} ${extra.excess.toFixed(2)}` : 'Included',
    })));

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems,
        taxBreakdown: computed(() => null),
        baseTotal: computed(() => 0),
        mandatoryAmount: computed(() => 0),
        locationData, highlights: computed(() => []),
        computeNetTotal: defaultComputeNetTotal,
        // Provider-specific exports
        providerInsuranceOptions: insuranceOptions,
        providerIncludedInsuranceOptions: includedInsuranceOptions,
        providerOptionalExtras: optionalExtras,
        providerAllExtras: allExtras,
        servicePool,
        getProviderExtraLabel,
        toTitleCase,
        normalizeFavricaExtra,
        isFavricaInsuranceService,
    };
}
