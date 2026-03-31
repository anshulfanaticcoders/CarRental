import { computed } from 'vue';

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @returns {import('./types').AdapterResult}
 */
export function createAdobeAdapter(props) {
    const adobeBaseRate = computed(() => {
        return parseFloat(props.vehicle?.total_price || props.vehicle?.pricing?.total_price || props.vehicle?.tdr || 0);
    });

    const adobeProtectionPlans = computed(() => {
        const protections = [];
        if (props.vehicle?.pli !== undefined) protections.push({ code: 'PLI', name: 'Liability Protection', amount: parseFloat(props.vehicle.pli), required: true });
        if (props.vehicle?.ldw !== undefined) protections.push({ code: 'LDW', name: 'Car Protection', amount: parseFloat(props.vehicle.ldw) * props.numberOfDays, required: false });
        if (props.vehicle?.spp !== undefined) protections.push({ code: 'SPP', name: 'Extended Protection', amount: parseFloat(props.vehicle.spp) * props.numberOfDays, required: false });
        return protections;
    });

    const packages = computed(() => {
        return [{
            type: 'BAS',
            name: 'Basic Rental',
            subtitle: 'Base Rental',
            total: adobeBaseRate.value,
            deposit: 0,
            benefits: [],
            isBestValue: false,
            isAddOn: false
        }];
    });

    const optionalExtras = computed(() => {
        const options = adobeProtectionPlans.value.filter(p => p.code !== 'PLI').map(plan => ({
            id: `adobe_protection_${plan.code}`,
            code: plan.code,
            name: plan.name,
            description: 'Optional Protection',
            price: parseFloat(plan.amount),
            daily_rate: parseFloat(plan.amount) / props.numberOfDays,
            amount: plan.amount,
            isHidden: true,
            isProtection: true
        }));

        const extras = props.vehicle?.extras || [];
        extras.forEach(extra => {
            const totalPrice = extra.total_price ?? extra.total_for_booking ?? parseFloat(extra.price || extra.amount || 0);
            options.push({
                id: extra.id || extra.option_id || extra.code,
                code: extra.code,
                name: extra.name || extra.displayName || extra.description || extra.code,
                description: extra.description || extra.displayDescription || extra.name,
                daily_rate: parseFloat(extra.daily_rate || extra.price || extra.amount || 0),
                price: parseFloat(totalPrice),
                total_for_booking: parseFloat(totalPrice),
                amount: extra.price || extra.amount
            });
        });

        return options;
    });

    const protectionPlans = computed(() => {
        const protections = props.vehicle?.protections || [];
        if (protections.length > 0) {
            return protections.filter(p => p.code !== 'PLI' && !p.required).map(plan => ({
                id: `adobe_protection_${plan.code}`,
                code: plan.code,
                name: plan.displayName || plan.name || plan.code,
                description: plan.displayDescription || plan.description || '',
                price: parseFloat(plan.price || plan.total || 0),
                daily_rate: parseFloat(plan.price || plan.total || 0) / props.numberOfDays,
                total_for_booking: parseFloat(plan.price || plan.total || 0),
                required: plan.required || false,
            }));
        }
        // Fallback: build from PLI/LDW/SPP fields
        return adobeProtectionPlans.value
            .filter(p => p.code !== 'PLI')
            .map(plan => ({
                id: `adobe_protection_${plan.code}`,
                code: plan.code,
                name: plan.name,
                description: plan.code === 'LDW' ? 'Car damage protection' : 'Extended protection package',
                price: plan.amount,
                daily_rate: plan.amount / props.numberOfDays,
                total_for_booking: plan.amount,
                required: false,
            }));
    });

    const allExtras = computed(() => [...optionalExtras.value, ...protectionPlans.value]);

    const mandatoryAmount = computed(() => {
        const pli = adobeProtectionPlans.value.find(p => p.code === 'PLI');
        return pli ? pli.amount : 0;
    });

    const baseTotal = computed(() => adobeBaseRate.value);
    const includedItems = computed(() => {
        const items = [];
        const pli = adobeProtectionPlans.value.find(p => p.code === 'PLI');
        if (pli) {
            items.push({ label: 'Liability Protection (PLI)', detail: `Mandatory — $${pli.amount.toFixed(2)}` });
        }
        items.push({ label: 'Third Party Liability', detail: 'Included' });
        items.push({ label: 'Unlimited Mileage', detail: 'Included' });
        return items;
    });
    const taxBreakdown = computed(() => null);

    const locationData = computed(() => ({
        pickupStation: null,
        pickupAddress: props.vehicle?.office_address || null,
        pickupLines: [],
        pickupPhone: props.vehicle?.office_phone || null,
        pickupEmail: null,
        dropoffStation: null,
        dropoffAddress: null,
        dropoffLines: [],
        dropoffPhone: null,
        dropoffEmail: null,
        sameLocation: true,
        fuelPolicy: null,
        cancellation: null,
        officeHours: props.vehicle?.office_schedule || null,
        pickupInstructions: null,
        dropoffInstructions: null,
        atAirport: props.vehicle?.at_airport || false,
    }));

    const highlights = computed(() => []);

    const computeNetTotal = (extrasTotal) => {
        const pkgPrice = parseFloat(packages.value[0]?.total || 0);
        return pkgPrice + mandatoryAmount.value + extrasTotal;
    };

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal,
    };
}
