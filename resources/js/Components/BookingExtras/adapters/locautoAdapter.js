import { ref, computed, watch } from 'vue';
import { useEmptyLocationData } from './shared.js';

/**
 * @param {Object} props
 * @param {Object} props.vehicle
 * @param {number} props.numberOfDays
 * @param {string|null} props.initialProtectionCode
 * @returns {import('./types').AdapterResult & {
 *   locautoProtectionPlans: import('vue').ComputedRef,
 *   selectedLocautoProtections: import('vue').Ref<string[]>,
 *   toggleLocautoProtection: (code: string) => void,
 *   locautoSmartCoverPlan: import('vue').ComputedRef,
 *   locautoDontWorryPlan: import('vue').ComputedRef,
 *   locautoBaseDaily: import('vue').ComputedRef<number>,
 * }}
 */
export function createLocautoAdapter(props) {
    const protectionCodes = ['136', '147', '145', '140', '146', '6', '43'];

    const locautoProtectionPlans = computed(() => {
        const extras = props.vehicle?.extras || [];
        return extras.filter(extra =>
            protectionCodes.includes(extra.code) && extra.amount > 0
        );
    });

    const selectedLocautoProtections = ref(
        props.initialProtectionCode ? [props.initialProtectionCode] : []
    );

    const toggleLocautoProtection = (code) => {
        const idx = selectedLocautoProtections.value.indexOf(code);
        if (idx >= 0) {
            selectedLocautoProtections.value.splice(idx, 1);
        } else {
            selectedLocautoProtections.value.push(code);
        }
    };

    watch(() => props.initialProtectionCode, (newCode) => {
        selectedLocautoProtections.value = newCode ? [newCode] : [];
    });

    const locautoSmartCoverPlan = computed(() => {
        return locautoProtectionPlans.value.find(p => p.code === '147') || null;
    });

    const locautoDontWorryPlan = computed(() => {
        return locautoProtectionPlans.value.find(p => p.code === '136') || null;
    });

    const baseTotal = computed(() => {
        const total = parseFloat(props.vehicle?.total_price || 0);
        if (total > 0) return total;
        const daily = parseFloat(props.vehicle?.price_per_day || 0);
        return daily > 0 ? daily * props.numberOfDays : 0;
    });

    const locautoBaseDaily = computed(() => {
        const total = baseTotal.value;
        if (!total || !props.numberOfDays) return 0;
        return total / props.numberOfDays;
    });

    const optionalExtras = computed(() => {
        const extras = props.vehicle?.extras || [];
        return extras.filter(extra =>
            !protectionCodes.includes(extra.code)
        ).map((extra) => ({
            id: extra.id || extra.code,
            code: extra.code,
            name: extra.description,
            description: extra.description,
            price: parseFloat(extra.amount) * props.numberOfDays,
            daily_rate: parseFloat(extra.amount),
            total_for_booking: extra.total_price ?? extra.total_for_booking ?? (parseFloat(extra.amount) * props.numberOfDays),
            amount: extra.amount
        }));
    });

    const packages = computed(() => []);
    const protectionPlans = locautoProtectionPlans;
    const allExtras = computed(() => [...optionalExtras.value]);
    const mandatoryAmount = computed(() => 0);
    const includedItems = computed(() => []);
    const taxBreakdown = computed(() => null);
    const locationData = useEmptyLocationData();
    const highlights = computed(() => []);

    const computeNetTotal = (extrasTotal) => {
        const protectionAmount = selectedLocautoProtections.value.reduce((sum, code) => {
            const plan = locautoProtectionPlans.value.find(p => p.code === code);
            return sum + (plan ? parseFloat(plan.amount || 0) * props.numberOfDays : 0);
        }, 0);
        return baseTotal.value + protectionAmount + extrasTotal;
    };

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal,
        // Locauto-specific exports
        locautoProtectionPlans, selectedLocautoProtections, toggleLocautoProtection,
        locautoSmartCoverPlan, locautoDontWorryPlan, locautoBaseDaily,
    };
}
