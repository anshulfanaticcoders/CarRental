import { computed } from 'vue';
import { useStandardPackages, emptyLocationData, defaultComputeNetTotal, emptyAdapterDefaults } from './shared.js';

const toNumber = (value) => {
    const numeric = parseFloat(value ?? 0);
    return Number.isNaN(numeric) ? 0 : numeric;
};

const normalizeGenericProduct = (product, index, vehicle, numberOfDays) => {
    const total = toNumber(product?.total ?? product?.total_price ?? vehicle?.total_price);
    const pricePerDay = toNumber(product?.price_per_day ?? product?.daily_rate)
        || (numberOfDays > 0 ? +(total / numberOfDays).toFixed(2) : total);

    return {
        type: product?.type || `PKG_${index + 1}`,
        name: product?.name || `Package ${index + 1}`,
        subtitle: product?.subtitle || 'Rental option',
        pricePerDay,
        total,
        deposit: product?.deposit != null ? toNumber(product.deposit) : null,
        excess: product?.excess != null ? toNumber(product.excess) : null,
        excessTheft: product?.excess_theft_amount != null ? toNumber(product.excess_theft_amount) : null,
        benefits: Array.isArray(product?.benefits) ? product.benefits : [],
        isBestValue: Boolean(product?.isBestValue ?? index === 0),
        isAddOn: false,
        currency: product?.currency || vehicle?.currency || vehicle?.pricing?.currency || 'EUR',
    };
};

const normalizeGenericProtection = (extra, index) => {
    const total = toNumber(extra?.total_for_booking ?? extra?.total_price ?? extra?.price ?? extra?.amount);
    const dailyRate = toNumber(extra?.daily_rate);

    return {
        id: extra?.id || extra?.code || extra?.coverage_type || `insurance_${index + 1}`,
        code: extra?.code || extra?.coverage_type || extra?.id || `insurance_${index + 1}`,
        name: extra?.name || extra?.coverage_type || 'Protection',
        description: extra?.description || null,
        price: total,
        daily_rate: dailyRate,
        total_for_booking: total,
        amount: total,
        currency: extra?.currency || 'EUR',
        numberAllowed: 1,
        maxQuantity: 1,
        required: false,
        excess: extra?.excess_amount != null ? toNumber(extra.excess_amount) : null,
        type: 'insurance',
        included: Boolean(extra?.included),
    };
};

const normalizeGenericExtra = (extra, index) => {
    const total = toNumber(extra?.total_for_booking ?? extra?.total_price ?? extra?.price ?? extra?.amount);
    const dailyRate = toNumber(extra?.daily_rate);

    return {
        id: extra?.id || extra?.code || `extra_${index + 1}`,
        code: extra?.code || extra?.id || `extra_${index + 1}`,
        name: extra?.name || extra?.description || 'Extra',
        description: extra?.description || extra?.name || 'Optional Extra',
        price: total,
        daily_rate: dailyRate,
        total_for_booking: total,
        amount: total,
        currency: extra?.currency || 'EUR',
        numberAllowed: extra?.numberAllowed || extra?.maxQuantity || extra?.max_quantity || 1,
        maxQuantity: extra?.maxQuantity || extra?.max_quantity || extra?.numberAllowed || 1,
        required: Boolean(extra?.required),
        type: extra?.type || 'optional',
    };
};

const buildGenericLocationData = (vehicle) => {
    const supplierData = vehicle?.supplier_data || {};
    const location = vehicle?.location_details || {};

    return {
        pickupStation: location?.name || vehicle?.pickup_station_name || supplierData?.pickup_station_name || null,
        pickupAddress: location?.address_1 || vehicle?.pickup_address || supplierData?.pickup_address || vehicle?.office_address || supplierData?.office_address || null,
        pickupLines: [],
        pickupPhone: location?.telephone || vehicle?.office_phone || supplierData?.pickup_phone || supplierData?.office_phone || null,
        pickupEmail: location?.email || supplierData?.pickup_email || null,
        dropoffStation: vehicle?.dropoff_station_name || supplierData?.dropoff_station_name || null,
        dropoffAddress: vehicle?.dropoff_address || supplierData?.dropoff_address || null,
        dropoffLines: [],
        dropoffPhone: supplierData?.dropoff_phone || null,
        dropoffEmail: supplierData?.dropoff_email || null,
        sameLocation: !(
            (vehicle?.dropoff_station_name || supplierData?.dropoff_station_name)
            || (vehicle?.dropoff_address || supplierData?.dropoff_address)
        ),
        fuelPolicy: null,
        cancellation: null,
        officeHours: location?.opening_hours || vehicle?.office_schedule || supplierData?.office_schedule || null,
        pickupInstructions: supplierData?.pickup_instructions || location?.collection_details || vehicle?.pickup_instructions || null,
        dropoffInstructions: supplierData?.dropoff_instructions || location?.dropoff_instructions || vehicle?.dropoff_instructions || null,
    };
};

/**
 * Fallback adapter for unknown sources (wheelsys, etc.).
 * Uses default `products` from vehicle and `optionalExtras` from props.
 *
 * @param {{ vehicle: Object, numberOfDays: number, optionalExtras: Array }} props
 * @returns {import('./types').AdapterResult}
 */
export function createDefaultAdapter(props) {
    const standardPackages = useStandardPackages(props);
    const genericInsurance = computed(() => {
        const insurance = Array.isArray(props.vehicle?.insurance_options) ? props.vehicle.insurance_options : [];
        return insurance.map((extra, index) => normalizeGenericProtection(extra, index));
    });
    const protectionPlans = computed(() => {
        return genericInsurance.value.filter((extra) => !extra.included && extra.total_for_booking > 0);
    });
    const includedItems = computed(() => {
        return genericInsurance.value
            .filter((extra) => extra.included || extra.total_for_booking <= 0)
            .map((extra) => ({
                label: extra.name,
                detail: 'Included',
            }));
    });
    const vehicleExtras = computed(() => {
        const extras = Array.isArray(props.vehicle?.extras) ? props.vehicle.extras : [];
        return extras.map((extra, index) => normalizeGenericExtra(extra, index));
    });
    const optionalExtras = computed(() => {
        return vehicleExtras.value.length > 0 ? vehicleExtras.value : (props.optionalExtras || []);
    });
    const allExtras = computed(() => [...protectionPlans.value, ...optionalExtras.value]);
    const packages = computed(() => {
        if (standardPackages.value.length > 0) return standardPackages.value;

        const products = Array.isArray(props.vehicle?.products) ? props.vehicle.products : [];
        if (!products.length) return [];

        return products.map((product, index) => normalizeGenericProduct(
            product,
            index,
            props.vehicle,
            props.numberOfDays,
        ));
    });
    const locationData = computed(() => {
        const hasLocationDetails = props.vehicle?.location_details || props.vehicle?.pickup_address || props.vehicle?.office_address
            || props.vehicle?.pickup_station_name || props.vehicle?.supplier_data?.pickup_instructions;

        if (!hasLocationDetails) {
            return emptyLocationData();
        }

        return buildGenericLocationData(props.vehicle);
    });

    return {
        ...emptyAdapterDefaults(),
        packages, optionalExtras, allExtras,
        protectionPlans, includedItems,
        locationData, computeNetTotal: defaultComputeNetTotal,
    };
}
