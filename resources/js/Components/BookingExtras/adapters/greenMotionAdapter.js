import { computed } from 'vue';
import { useStandardPackages, defaultComputeNetTotal, emptyAdapterDefaults } from './shared.js';

/**
 * @param {{ vehicle: Object, numberOfDays: number, optionalExtras: Array }} props
 * @returns {import('./types').AdapterResult}
 */
export function createGreenMotionAdapter(props) {
    // ── Green Motion Extras ─────────────────────────────────────────────
    const greenMotionExtras = computed(() => {
        const source = props.vehicle?.source;
        if (source !== 'greenmotion' && source !== 'usave') return [];

        const options = [];
        const seen = new Set();

        const normalizeRequired = (value) => {
            if (value === true) return true;
            if (value === false || value === null || value === undefined) return false;
            return `${value}`.toLowerCase() === 'required';
        };

        const pushExtra = (extra, fallbackPrefix = 'gm_option_', typeOverride = null) => {
            if (!extra) return;
            const optionId = extra.option_id || extra.optionID || extra.id;
            if (!optionId) return;
            const key = String(optionId);
            if (seen.has(key)) return;
            seen.add(key);

            const totalForBooking = extra.total_for_booking ?? extra.Total_for_this_booking ?? extra.total ?? null;
            const numberAllowed = extra.numberAllowed ? parseInt(extra.numberAllowed) : null;
            const required = normalizeRequired(extra.required);

            options.push({
                id: extra.id || `${fallbackPrefix}${optionId}`,
                option_id: extra.option_id || extra.optionID || optionId,
                name: extra.name || extra.Name || extra.Description || 'Extra',
                description: extra.description || extra.Description || '',
                required,
                numberAllowed,
                prepay_available: (extra.prepay_available || extra.Prepay_available || '').toString().toLowerCase(),
                daily_rate: extra.daily_rate || extra.Daily_rate || 0,
                total_for_booking: totalForBooking,
                total_for_booking_currency: extra.total_for_booking_currency || extra.Total_for_this_booking_currency || extra.currency || null,
                code: extra.code || extra.Code || null,
                type: typeOverride || extra.type || 'option',
            });
        };

        const collect = (items, fallbackPrefix, typeOverride = null) => {
            (items || []).forEach((extra) => pushExtra(extra, fallbackPrefix, typeOverride));
        };

        const collectOptionalExtras = (items) => {
            (items || []).forEach((extra) => {
                if (Array.isArray(extra.options) && extra.options.length > 0) {
                    extra.options.forEach((option) => {
                        pushExtra({
                            ...option,
                            name: option.name || option.Name || extra.Name,
                            description: option.description || option.Description || extra.Description,
                            required: option.required ?? extra.required,
                            numberAllowed: option.numberAllowed ?? extra.numberAllowed,
                            code: option.code || extra.code,
                            type: option.type || extra.type || 'optional'
                        }, 'gm_optional_', 'optional');
                    });
                    return;
                }
                pushExtra(extra, 'gm_optional_', 'optional');
            });
        };

        collect(props.vehicle?.options || [], 'gm_option_', 'option');
        collect(props.vehicle?.insurance_options || [], 'gm_insurance_', 'insurance');
        collectOptionalExtras(props.optionalExtras || []);

        return options;
    });

    const packages = useStandardPackages(props);
    const optionalExtras = greenMotionExtras;
    const allExtras = computed(() => [...greenMotionExtras.value]);
    const locationData = computed(() => {
        const sd = props.vehicle?.supplier_data || {};
        return {
            pickupStation: sd.pickup_station_name || null,
            pickupAddress: sd.pickup_address || null,
            pickupLines: [],
            pickupPhone: sd.office_phone || null,
            pickupEmail: sd.office_email || null,
            dropoffStation: null, dropoffAddress: null, dropoffLines: [],
            dropoffPhone: null, dropoffEmail: null,
            sameLocation: true,
            fuelPolicy: sd.fuel_policy || null,
            cancellation: null,
            officeHours: sd.office_opening_hours || null,
            pickupInstructions: sd.pickup_instructions || null,
            dropoffInstructions: null,
        };
    });

    return {
        packages, optionalExtras, allExtras,
        locationData, computeNetTotal: defaultComputeNetTotal,
        ...emptyAdapterDefaults(),
        // Green Motion specific export
        greenMotionExtras,
    };
}
