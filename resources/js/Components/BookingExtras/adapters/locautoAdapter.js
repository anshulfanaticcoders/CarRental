import { ref, computed, watch } from 'vue';
import { useEmptyLocationData } from './shared.js';

/**
 * @param {Object} props
 * @param {Object} props.vehicle
 * @param {number} props.numberOfDays
 * @param {string|null} props.initialProtectionCode
 * @returns {import('./types').AdapterResult & {
 *   locautoProtectionPlans: import('vue').ComputedRef,
 *   locautoProtectionOptions: import('vue').ComputedRef,
 *   selectedLocautoProtections: import('vue').Ref<string[]>,
 *   toggleLocautoProtection: (code: string|null) => void,
 *   locautoSmartCoverPlan: import('vue').ComputedRef,
 *   locautoDontWorryPlan: import('vue').ComputedRef,
 *   locautoAssistanceItems: import('vue').ComputedRef,
 *   locautoBaseDaily: import('vue').ComputedRef<number>,
 * }}
 */
export function createLocautoAdapter(props) {
    const paidProtectionCodes = ['147', '136'];
    const assistanceCodes = ['43', '6'];
    const nonCustomerOptionCodes = new Set([
        ...paidProtectionCodes,
        ...assistanceCodes,
        '140', '145', '146',
        '23', '35', '55',
    ]);
    const locautoOptionMeta = {
        '136': {
            title: "Don't Worry",
            description: 'A super inclusive package that eliminates liability for theft and damage to the car.',
            badge: 'Most complete',
        },
        '147': {
            title: 'Smart Cover',
            description: 'Eliminates excess for theft and reduces liability for attempted theft while keeping standard damage excess.',
            badge: 'Balanced',
        },
        '43': {
            title: 'Roadside Plus',
            description: 'Ensures car towing in case of a technical stop or vehicle damage after an accident.',
            categoryKey: 'safety_assistance',
            categoryLabel: 'Safety and assistance',
        },
        '6': {
            title: 'Protection Against Injuries',
            description: 'Protects the driver from personal accidents that happen inside the vehicle.',
            categoryKey: 'safety_assistance',
            categoryLabel: 'Safety and assistance',
        },
        '19': {
            title: 'GPS',
            description: 'A reliable partner for your journey, with up-to-date maps to find the best routes faster.',
            categoryKey: 'extra_optionals',
            categoryLabel: 'Extra optionals',
        },
        '78': {
            title: 'Child Seat',
            description: 'For your child safety and to comply with road rules.',
            categoryKey: 'extra_optionals',
            categoryLabel: 'Extra optionals',
        },
        '7': {
            title: 'Infant Child Seat',
            description: 'A child seat prepared for younger passengers.',
            categoryKey: 'extra_optionals',
            categoryLabel: 'Extra optionals',
        },
        '8': {
            title: 'Child Toddler Seat',
            description: 'A toddler seat prepared for safe family travel.',
            categoryKey: 'extra_optionals',
            categoryLabel: 'Extra optionals',
        },
        '89': {
            title: 'Bau the way',
            description: 'Travel with your dog in full autonomy and in respect of the law.',
            categoryKey: 'extra_optionals',
            categoryLabel: 'Extra optionals',
        },
        '166': {
            title: 'Tollpass device',
            description: 'No more waiting at the toll booth.',
            categoryKey: 'extra_optionals',
            categoryLabel: 'Extra optionals',
        },
        '137': {
            title: 'Additional Driver',
            description: 'Allows another passenger to drive the vehicle.',
            categoryKey: 'extra_services',
            categoryLabel: 'Extra Services',
        },
        '9': {
            title: 'Additional Driver',
            description: 'Allows another passenger to drive the vehicle.',
            categoryKey: 'extra_services',
            categoryLabel: 'Extra Services',
        },
        '77': {
            title: 'No stress return',
            description: 'Return your vehicle up to 2 hours after the scheduled drop-off time.',
            categoryKey: 'extra_services',
            categoryLabel: 'Extra Services',
        },
        '138': {
            title: 'Car pooling from 3 to more drivers',
            description: 'Share the rental with family, friends, or work mates.',
            categoryKey: 'extra_services',
            categoryLabel: 'Extra Services',
        },
    };

    const normalizeCode = (value) => String(value ?? '').trim();
    const metaFor = (extraOrCode) => {
        const code = typeof extraOrCode === 'object'
            ? normalizeCode(extraOrCode?.code ?? extraOrCode?.id)
            : normalizeCode(extraOrCode);
        return locautoOptionMeta[code] || {};
    };
    const isOneWayFee = (extra) => {
        const code = normalizeCode(extra?.code || extra?.id || '');
        const label = `${extra?.name || ''} ${extra?.description || ''}`.toLowerCase().replace(/-/g, ' ');
        return ['23', '35'].includes(code) || label.includes('one way');
    };
    const extraBookingTotal = (extra) => {
        const total = parseFloat(extra?.total_for_booking ?? extra?.total_price);
        if (Number.isFinite(total)) return total;
        const amount = parseFloat(extra?.amount ?? extra?.daily_rate ?? 0);
        return amount * props.numberOfDays;
    };
    const extraDailyRate = (extra) => {
        const daily = parseFloat(extra?.daily_rate);
        if (Number.isFinite(daily) && daily > 0) return daily;
        const amount = parseFloat(extra?.amount);
        const pricingType = (extra?.pricing_type || extra?.supplier_data?.pricing_type || '').toLowerCase();
        if (Number.isFinite(amount) && amount > 0 && pricingType !== 'per_rental' && pricingType !== 'per_booking') return amount;
        const total = extraBookingTotal(extra);
        return props.numberOfDays > 0 ? total / props.numberOfDays : total;
    };
    const selectableExtras = computed(() => {
        const extras = props.vehicle?.extras || [];
        return extras.filter(extra => !isOneWayFee(extra));
    });
    const normalizeInitialProtectionCodes = (code) => {
        const raw = Array.isArray(code) ? code : (code ? [code] : []);
        const selected = raw.map(normalizeCode).find(item => paidProtectionCodes.includes(item));
        return selected ? [selected] : [];
    };

    const locautoProtectionPlans = computed(() => {
        return selectableExtras.value
            .filter(extra => paidProtectionCodes.includes(normalizeCode(extra.code)) && extraBookingTotal(extra) > 0)
            .map(extra => {
                const code = normalizeCode(extra.code);
                const meta = metaFor(code);
                const total = extraBookingTotal(extra);
                const daily = extraDailyRate(extra);
                return {
                    ...extra,
                    id: extra.id || `locauto_protection_${code}`,
                    code,
                    name: meta.title || extra.name || extra.description,
                    title: meta.title || extra.name || extra.description,
                    description: meta.description || extra.description || extra.name,
                    badge: meta.badge || null,
                    amount: daily,
                    daily_rate: daily,
                    price: total,
                    total_for_booking: total,
                    pricing_type: extra.pricing_type || extra.supplier_data?.pricing_type || 'per_day',
                };
            });
    });

    const selectedLocautoProtections = ref(normalizeInitialProtectionCodes(props.initialProtectionCode));

    const toggleLocautoProtection = (code) => {
        const normalized = normalizeCode(code);
        if (!normalized || selectedLocautoProtections.value.includes(normalized)) {
            selectedLocautoProtections.value = [];
            return;
        }

        if (paidProtectionCodes.includes(normalized)) {
            selectedLocautoProtections.value = [normalized];
        }
    };

    watch(() => props.initialProtectionCode, (newCode) => {
        selectedLocautoProtections.value = normalizeInitialProtectionCodes(newCode);
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

    const excessAmounts = computed(() => {
        const damage = parseFloat(
            props.vehicle?.benefits?.excess_amount
            ?? props.vehicle?.excess
            ?? props.vehicle?.excess_amount
            ?? 0
        );
        const theft = parseFloat(
            props.vehicle?.benefits?.excess_theft_amount
            ?? props.vehicle?.excess_theft_amount
            ?? 0
        );

        return {
            damage: Number.isFinite(damage) ? damage : 0,
            theft: Number.isFinite(theft) ? theft : 0,
        };
    });

    const buildProtectionFeatures = (code) => {
        const damage = excessAmounts.value.damage;
        const theft = excessAmounts.value.theft;
        const highRiskTheft = theft > 0 ? theft * 2 : 0;
        const isSmartCover = code === '147';
        const isDontWorry = code === '136';

        return [
            {
                label: 'Damages',
                value: isDontWorry ? 0 : damage,
                prefix: isDontWorry ? '' : 'up to',
                included: true,
            },
            {
                label: 'Total or partial theft',
                value: isSmartCover || isDontWorry ? 0 : theft,
                prefix: isSmartCover || isDontWorry ? '' : 'up to',
                included: true,
            },
            {
                label: 'Theft in high-risk area',
                value: isDontWorry || isSmartCover ? theft : highRiskTheft,
                prefix: 'up to',
                included: true,
            },
            {
                label: 'Roadside assistance',
                value: null,
                included: isDontWorry,
            },
            {
                label: 'Protection against injuries',
                value: null,
                included: isDontWorry,
            },
        ];
    };

    const locautoProtectionOptions = computed(() => {
        const byCode = Object.fromEntries(locautoProtectionPlans.value.map(plan => [plan.code, plan]));
        const options = [{
            id: 'locauto_protection_basic',
            code: null,
            title: 'Basic',
            name: 'Basic',
            description: 'Includes limited liability for vehicle damage and theft.',
            badge: 'Included',
            amount: 0,
            daily_rate: 0,
            price: 0,
            total_for_booking: 0,
            features: buildProtectionFeatures(null),
        }];

        paidProtectionCodes.forEach((code) => {
            const plan = byCode[code];
            if (!plan) return;
            options.push({
                ...plan,
                features: buildProtectionFeatures(code),
            });
        });

        return options;
    });

    const optionalExtras = computed(() => {
        return selectableExtras.value
            .filter(extra => {
                const code = normalizeCode(extra.code);
                return !nonCustomerOptionCodes.has(code) && extraBookingTotal(extra) > 0;
            })
            .map((extra) => {
                const code = normalizeCode(extra.code);
                const meta = metaFor(code);
                const total = extraBookingTotal(extra);
                const daily = extraDailyRate(extra);
                const pricingType = extra.pricing_type
                    || extra.supplier_data?.pricing_type
                    || (parseFloat(extra.daily_rate ?? extra.amount ?? 0) > 0 ? 'per_day' : 'per_booking');

                return {
                    ...extra,
                    id: extra.id || code,
                    code,
                    name: meta.title || extra.name || extra.description,
                    display_name: meta.title || extra.name || extra.description,
                    description: meta.description || extra.description || extra.name,
                    category_key: meta.categoryKey || 'extra_optionals',
                    category_label: meta.categoryLabel || 'Extra optionals',
                    price: total,
                    daily_rate: daily,
                    total_for_booking: total,
                    pricing_type: pricingType,
                    amount: daily,
                };
            });
    });

    const packages = computed(() => []);
    const protectionPlans = locautoProtectionPlans;
    const allExtras = computed(() => [...optionalExtras.value]);
    const mandatoryAmount = computed(() => 0);
    const includedItems = computed(() => {
        const policies = props.vehicle?.rental_policies || [];
        if (!Array.isArray(policies) || policies.length === 0) return [];
        return policies.map(p => ({
            label: p.label || '',
            detail: p.detail || p.value || '',
        }));
    });
    const locautoAssistanceItems = computed(() => {
        if (!selectedLocautoProtections.value.includes('136')) return [];

        return assistanceCodes.map((code) => {
            const source = selectableExtras.value.find(extra => normalizeCode(extra.code) === code);
            const meta = metaFor(code);

            return {
                id: `locauto_included_${code}`,
                code,
                name: meta.title,
                display_name: meta.title,
                description: meta.description,
                category_key: meta.categoryKey,
                category_label: meta.categoryLabel,
                price: 0,
                daily_rate: 0,
                total_for_booking: 0,
                pricing_type: 'included',
                amount: 0,
                included_value: source ? extraBookingTotal(source) : 0,
                isIncluded: true,
                required: true,
            };
        });
    });
    const taxBreakdown = computed(() => null);
    const locationData = useEmptyLocationData();
    const highlights = computed(() => []);

    const locautoProtectionTotal = computed(() => {
        return selectedLocautoProtections.value.reduce((sum, code) => {
            const plan = locautoProtectionPlans.value.find(p => p.code === code);
            return sum + (plan ? extraBookingTotal(plan) : 0);
        }, 0);
    });

    const computeNetTotal = (extrasTotal) => {
        return baseTotal.value + locautoProtectionTotal.value + extrasTotal;
    };

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal,
        // Locauto-specific exports
        locautoProtectionPlans, locautoProtectionOptions, selectedLocautoProtections, toggleLocautoProtection, locautoProtectionTotal,
        locautoSmartCoverPlan, locautoDontWorryPlan, locautoAssistanceItems, locautoBaseDaily,
    };
}
