import { computed } from 'vue';
import { resolveStandardPackages, emptyAdapterDefaults } from './shared.js';

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @returns {import('./types').AdapterResult}
 */
export function createSurpriceAdapter(props) {
    // ── Packages ──────────────────────────────────────────────────────
    const packages = computed(() => {
        const standardPkgs = resolveStandardPackages(props.vehicle);
        if (standardPkgs.length > 0) return standardPkgs;

        // Gateway canonical path: Surprice has no supplier_data.products,
        // so the products array arrives empty.  Synthesize a BAS package
        // from the vehicle-level pricing to avoid $0.00 display.
        const total = parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
        if (total <= 0) return [];
        const currency = props.vehicle?.currency || props.vehicle?.pricing?.currency || 'EUR';
        const perDay = parseFloat(props.vehicle?.price_per_day || props.vehicle?.pricing?.price_per_day || 0)
            || (props.numberOfDays > 0 ? +(total / props.numberOfDays).toFixed(2) : total);
        return [{
            type: 'BAS',
            name: 'Basic',
            total,
            price_per_day: perDay,
            currency,
        }];
    });

    // ── Optional Extras ─────────────────────────────────────────────────
    const optionalExtras = computed(() => {
        const extras = Array.isArray(props.vehicle?.extras) ? props.vehicle.extras : [];
        return extras.map((extra, index) => {
            const code = extra.code || extra.id || `EXTRA_${index}`;
            const totalPrice = parseFloat(extra.price || 0);
            const dailyRate = extra.per_day ? parseFloat(extra.price_per_day || 0) : (props.numberOfDays ? totalPrice / props.numberOfDays : totalPrice);
            return {
                id: `surprice_extra_${code}_${index}`,
                code,
                name: extra.name || code,
                description: extra.name || code,
                price: totalPrice,
                daily_rate: dailyRate,
                currency: extra.currency || 'EUR',
                required: false,
                allow_quantity: extra.allow_quantity || false,
                purpose: extra.purpose ?? null,
            };
        });
    });

    const { protectionPlans, includedItems, mandatoryAmount, highlights } = emptyAdapterDefaults();
    const allExtras = computed(() => [...optionalExtras.value]);
    const taxBreakdown = computed(() => null);
    const baseTotal = computed(() => {
        const product = packages.value.find(p => p.type === 'BAS');
        return parseFloat(product?.total || props.vehicle?.total_price || 0);
    });

    const locationData = computed(() => {
        const sd = props.vehicle?.supplier_data || {};
        const pickup = sd.pickup_office || {};
        const dropoff = sd.dropoff_office || {};
        return {
            pickupStation: pickup.name || sd.pickup_station_name || null,
            pickupAddress: pickup.address || null,
            pickupLines: [pickup.town, pickup.postal_code].filter(Boolean),
            pickupPhone: pickup.phone || null,
            pickupEmail: pickup.email || null,
            dropoffStation: dropoff.name || sd.return_station_name || null,
            dropoffAddress: dropoff.address || null,
            dropoffLines: [dropoff.town, dropoff.postal_code].filter(Boolean),
            dropoffPhone: dropoff.phone || null,
            dropoffEmail: dropoff.email || null,
            sameLocation: !dropoff.name || pickup.name === dropoff.name,
            fuelPolicy: null,
            cancellation: null,
            officeHours: null,
            pickupInstructions: pickup.pickup_instructions || sd.pickup_additional_info || null,
            dropoffInstructions: dropoff.dropoff_instructions || null,
        };
    });

    const computeNetTotal = (extrasTotal, currentProduct) => {
        const pkgPrice = parseFloat(currentProduct?.total || 0)
            || parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || 0);
        return pkgPrice + extrasTotal;
    };

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal,
    };
}
