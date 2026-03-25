import { computed } from 'vue';

/**
 * Standard package order used by gateway-normalised vehicles.
 * @type {string[]}
 */
export const PACKAGE_ORDER = ['BAS', 'PLU', 'PRE', 'PMP'];

/**
 * Resolve protection packages from `vehicle.products` using the canonical
 * BAS/PLU/PRE/PMP ordering.  Used by default, favrica/xdrive, greenMotion,
 * and surprice adapters.
 *
 * @param {Object} vehicle
 * @returns {Object[]}
 */
export function resolveStandardPackages(vehicle) {
    if (!vehicle?.products) return [];
    return PACKAGE_ORDER
        .map(type => vehicle.products.find(p => p.type === type))
        .filter(Boolean);
}

/**
 * Return a Vue computed that resolves standard packages for a vehicle prop.
 *
 * @param {{ vehicle: Object }} props
 * @returns {import('vue').ComputedRef<Object[]>}
 */
export function useStandardPackages(props) {
    return computed(() => resolveStandardPackages(props.vehicle));
}

/**
 * Build an empty LocationData object matching the AdapterResult contract.
 *
 * @returns {import('./types').LocationData}
 */
export function emptyLocationData() {
    return {
        pickupStation: null, pickupAddress: null, pickupLines: [],
        pickupPhone: null, pickupEmail: null,
        dropoffStation: null, dropoffAddress: null, dropoffLines: [],
        dropoffPhone: null, dropoffEmail: null,
        sameLocation: true, fuelPolicy: null, cancellation: null,
        officeHours: null, pickupInstructions: null, dropoffInstructions: null,
    };
}

/**
 * Return a Vue computed that always yields an empty LocationData.
 *
 * @returns {import('vue').ComputedRef<import('./types').LocationData>}
 */
export function useEmptyLocationData() {
    return computed(() => emptyLocationData());
}

/**
 * Standard computeNetTotal: package total + extras total.
 *
 * @param {number} extrasTotal
 * @param {Object|null} currentProduct
 * @returns {number}
 */
export function defaultComputeNetTotal(extrasTotal, currentProduct) {
    const pkgPrice = parseFloat(currentProduct?.total || 0);
    return pkgPrice + extrasTotal;
}

/**
 * Return the set of "empty" computed refs that most adapters share:
 * protectionPlans, includedItems, taxBreakdown, baseTotal,
 * mandatoryAmount, highlights.
 *
 * Spread the result into your adapter return to avoid re-declaring them.
 *
 * @returns {Object}
 */
export function emptyAdapterDefaults() {
    return {
        protectionPlans: computed(() => []),
        includedItems: computed(() => []),
        taxBreakdown: computed(() => null),
        baseTotal: computed(() => 0),
        mandatoryAmount: computed(() => 0),
        highlights: computed(() => []),
    };
}
