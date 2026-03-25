import { computed } from 'vue';
import { useStandardPackages, useEmptyLocationData, defaultComputeNetTotal, emptyAdapterDefaults } from './shared.js';

/**
 * Fallback adapter for unknown sources (wheelsys, etc.).
 * Uses default `products` from vehicle and `optionalExtras` from props.
 *
 * @param {{ vehicle: Object, numberOfDays: number, optionalExtras: Array }} props
 * @returns {import('./types').AdapterResult}
 */
export function createDefaultAdapter(props) {
    const packages = useStandardPackages(props);
    const optionalExtras = computed(() => props.optionalExtras || []);
    const allExtras = computed(() => [...optionalExtras.value]);
    const locationData = useEmptyLocationData();

    return {
        packages, optionalExtras, allExtras,
        locationData, computeNetTotal: defaultComputeNetTotal,
        ...emptyAdapterDefaults(),
    };
}
