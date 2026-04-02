import { computed } from 'vue';
import { resolveStandardPackages, useEmptyLocationData, defaultComputeNetTotal } from './shared.js';

const normalizeExtra = (extra, numberOfDays) => {
    if (!extra) return null;
    const dailyRate = parseFloat(extra.daily_rate ?? extra.price ?? 0);
    const totalPrice = parseFloat(extra.total_price ?? extra.total_for_booking ?? (dailyRate * numberOfDays) ?? 0);
    const id = extra.id || extra.option_id || extra.code || '';
    return {
        id,
        code: extra.code || extra.description || id,
        name: extra.name || extra.name_en || 'Extra',
        description: extra.description || extra.code || '',
        price: dailyRate,
        daily_rate: dailyRate,
        amount: totalPrice,
        total_for_booking: totalPrice,
        currency: extra.currency || 'EUR',
        numberAllowed: extra.max_quantity || extra.numberAllowed || parseInt(extra.quantity || '1'),
        maxQuantity: extra.max_quantity || extra.numberAllowed || parseInt(extra.quantity || '1'),
        type: extra.type || 'equipment',
    };
};

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 */
export function createClick2RentAdapter(props) {
    const packages = computed(() => {
        const standard = resolveStandardPackages(props.vehicle);
        if (standard.length > 0) return standard;
        // Fallback from vehicle pricing
        const total = parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
        if (total <= 0) return [];
        const currency = props.vehicle?.currency || 'EUR';
        const perDay = parseFloat(props.vehicle?.price_per_day || props.vehicle?.pricing?.price_per_day || 0)
            || (props.numberOfDays > 0 ? +(total / props.numberOfDays).toFixed(2) : total);
        return [{ type: 'BAS', name: 'Inclusive', total, price_per_day: perDay, currency }];
    });

    // Read extras from vehicle.extras or vehicle.options
    const optionalExtras = computed(() => {
        const raw = [
            ...(props.vehicle?.extras || []),
            ...(props.vehicle?.options || []),
        ];
        const byId = new Map();
        return raw
            .filter(e => e && !e.mandatory && e.type !== 'fee')
            .map(e => normalizeExtra(e, props.numberOfDays || 1))
            .filter(e => e && e.daily_rate > 0)
            .filter(e => {
                if (byId.has(e.id)) return false;
                byId.set(e.id, true);
                return true;
            });
    });

    const allExtras = computed(() => [...optionalExtras.value]);

    // Included items from package name
    const includedItems = computed(() => {
        const items = [];
        const pkgName = (props.vehicle?.supplier_data?.package_name || '').toLowerCase();
        if (pkgName.includes('inclusive')) {
            items.push({ label: 'Collision Damage Waiver (CDW)', included: true });
            items.push({ label: 'Theft Protection', included: true });
            items.push({ label: 'Third Party Liability', included: true });
            items.push({ label: 'Unlimited Mileage', included: true });
        }
        return items;
    });

    const locationData = computed(() => {
        const sd = props.vehicle?.supplier_data || {};
        const loc = props.vehicle?.location_details || {};
        return {
            pickupStation: loc.name || props.vehicle?.pickup_station_name || null,
            pickupAddress: loc.address_1 || props.vehicle?.pickup_address || null,
            pickupLines: [],
            pickupPhone: loc.telephone || null,
            pickupEmail: loc.email || null,
            dropoffStation: null,
            dropoffAddress: null,
            dropoffLines: [],
            dropoffPhone: null,
            dropoffEmail: null,
            sameLocation: true,
            fuelPolicy: null,
            cancellation: null,
            officeHours: loc.opening_hours || null,
            pickupInstructions: sd.pickup_instructions || loc.collection_details || null,
            dropoffInstructions: sd.dropoff_instructions || loc.dropoff_instructions || null,
        };
    });

    return {
        packages,
        optionalExtras,
        allExtras,
        includedItems,
        locationData,
        protectionPlans: computed(() => []),
        taxBreakdown: computed(() => null),
        baseTotal: computed(() => 0),
        mandatoryAmount: computed(() => 0),
        highlights: computed(() => []),
        computeNetTotal: defaultComputeNetTotal,
    };
}
