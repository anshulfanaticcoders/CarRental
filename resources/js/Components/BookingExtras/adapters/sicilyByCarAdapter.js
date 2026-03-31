import { computed } from 'vue';
import { buildSicilyByCarOptionalExtras } from '@/utils/sicilyByCarExtras';
import { useEmptyLocationData, defaultComputeNetTotal } from './shared.js';

const SBC_PROTECTION_CODES = ['CDW', 'TLW', 'CPP', 'GLD', 'PAI', 'RAP'];

const getSbcExcessValue = (service) => {
    if (!service) return null;
    let raw = service.excess ?? service.excessAmount ?? service.excessValue ?? service.excess_amount ?? null;
    if (raw && typeof raw === 'object') {
        raw = raw.amount ?? raw.value ?? raw.total ?? null;
    }
    const parsed = parseFloat(raw);
    return Number.isFinite(parsed) ? parsed : null;
};

/**
 * @param {{ vehicle: Object, numberOfDays: number }} props
 * @returns {import('./types').AdapterResult & Record<string, any>}
 */
export function createSicilyByCarAdapter(props) {
    const sicilyByCarServices = computed(() => {
        return Array.isArray(props.vehicle?.extras) ? props.vehicle.extras : [];
    });

    const sicilyByCarIncludedServices = computed(() => {
        return sicilyByCarServices.value.filter(service => service?.isMandatory);
    });

    const sicilyByCarProtectionPlans = computed(() => {
        return sicilyByCarServices.value
            .filter(service => !service?.isMandatory && SBC_PROTECTION_CODES.includes(`${service?.code || service?.id || ''}`.toUpperCase()))
            .map((service, index) => {
                const code = `${service?.code || service?.id || ''}`.toUpperCase() || `PROT_${index}`;
                const total = parseFloat(service?.total || 0);
                const excess = getSbcExcessValue(service);
                return {
                    id: service?.id || `sbc_protection_${code}_${index}`,
                    code,
                    name: service?.description || code,
                    description: service?.description || code,
                    price: total,
                    daily_rate: props.numberOfDays ? total / props.numberOfDays : total,
                    total_for_booking: total,
                    amount: total,
                    excess,
                    required: false,
                    payment: service?.payment || null,
                };
            });
    });

    const sicilyByCarOptionalExtras = computed(() => {
        return buildSicilyByCarOptionalExtras(
            sicilyByCarServices.value.filter(service => !service?.isMandatory && !SBC_PROTECTION_CODES.includes(`${service?.code || service?.id || ''}`.toUpperCase())),
            props.numberOfDays,
        );
    });

    const sicilyByCarAllExtras = computed(() => {
        return [...sicilyByCarProtectionPlans.value, ...sicilyByCarOptionalExtras.value];
    });

    // ── Base Total ──────────────────────────────────────────────────────
    const baseTotal = computed(() => {
        const total = parseFloat(props.vehicle?.total_price || 0);
        if (total > 0) return total;
        const daily = parseFloat(props.vehicle?.price_per_day || 0);
        return daily > 0 ? daily * props.numberOfDays : 0;
    });

    // ── Packages ────────────────────────────────────────────────────────
    const packages = computed(() => {
        const benefits = [];
        if (props.vehicle?.rate_name) {
            benefits.push(props.vehicle.rate_name);
        }
        if (props.vehicle?.payment_type) {
            benefits.push(props.vehicle.payment_type === 'Prepaid' ? 'Prepaid' : 'Pay on arrival');
        }
        if (props.vehicle?.mileage) {
            benefits.push(`Mileage: ${props.vehicle.mileage}`);
        }
        const deposit = parseFloat(props.vehicle?.deposit ?? 0);

        return [
            {
                type: 'BAS',
                name: 'Basic Rental',
                subtitle: props.vehicle?.payment_type === 'Prepaid' ? 'Prepaid' : 'Pay on arrival',
                total: baseTotal.value,
                deposit: Number.isFinite(deposit) ? deposit : 0,
                benefits: benefits.filter(Boolean),
                isBestValue: true,
                isAddOn: false,
            }
        ];
    });

    const optionalExtras = sicilyByCarOptionalExtras;
    const protectionPlans = sicilyByCarProtectionPlans;
    const allExtras = sicilyByCarAllExtras;

    const includedItems = computed(() => {
        return sicilyByCarIncludedServices.value.map(service => {
            const excess = getSbcExcessValue(service);
            const code = `${service?.code || service?.id || ''}`.toUpperCase();
            let detail = 'Included';
            if (excess && excess > 0 && (code === 'CDW' || code === 'TLW')) {
                detail = `Included (excess: €${excess})`;
            }
            return {
                label: service?.description || service?.name || service?.id || 'Included Service',
                detail,
            };
        });
    });

    const taxBreakdown = computed(() => null);
    const mandatoryAmount = computed(() => 0);
    const locationData = useEmptyLocationData();
    const highlights = computed(() => []);

    return {
        packages, optionalExtras, protectionPlans, allExtras,
        includedItems, taxBreakdown, baseTotal, mandatoryAmount,
        locationData, highlights, computeNetTotal: defaultComputeNetTotal,
        // Sicily by Car specific exports
        sicilyByCarIncludedServices,
        sicilyByCarAllExtras,
    };
}
