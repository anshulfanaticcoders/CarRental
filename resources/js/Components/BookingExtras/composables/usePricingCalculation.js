import { computed } from 'vue';
import { useCurrencyConversion } from '@/composables/useCurrencyConversion';
import {
    computeBookingChargeBreakdown,
    resolveEffectiveProviderMarkupRate,
    shouldUseCommissionOnlyForVehicle,
    toProviderGrossMultiplier,
} from '@/utils/platformPricing';
import { getSicilyByCarExtraTotal } from '@/utils/sicilyByCarExtras';
import { normalizeCurrencyCode as registryNormalizeCurrencyCode } from '@/utils/currencyRegistry';

export function normalizeCurrencyCode(currency) {
    return registryNormalizeCurrencyCode(currency, 'EUR');
}

export function stripHtml(value) {
    if (!value || typeof value !== 'string') return value || '';
    return value.replace(/<[^>]*>/g, '').trim();
}

export function resolveVehicleCurrency(currentProduct, vehicle) {
    const rawCurrency = currentProduct?.currency
        || vehicle?.pricing?.currency
        || vehicle?.currency
        || vehicle?.booking_context?.provider_payload?.vendorProfileData?.currency
        || vehicle?.booking_context?.provider_payload?.vendor_profile_data?.currency
        || vehicle?.booking_context?.provider_payload?.benefits?.deposit_currency
        || vehicle?.benefits?.deposit_currency
        || 'EUR';
    return normalizeCurrencyCode(rawCurrency);
}

/**
 * Composable that encapsulates all pricing / currency logic for BookingExtras.
 *
 * @param {Object} options
 * @param {Object}                           options.props                      - Component props (vehicle, numberOfDays, paymentPercentage, optionalExtras)
 * @param {import('vue').Ref<Object>}        options.selectedExtras             - Reactive map { extraId: quantity }
 * @param {Object}                           options.adapter                    - Current provider adapter (AdapterResult)
 * @param {import('vue').ComputedRef<Object>} options.currentProduct            - Selected product / package
 * @param {import('vue').ComputedRef<number>} options.providerMarkupRate        - Markup rate from page props
 * @param {import('vue').ComputedRef<number>} options.providerGrossMultiplier   - Gross multiplier derived from markup rate
 * @param {import('vue').Ref<string[]>}      options.selectedLocautoProtections - Selected Locauto protection codes
 * @param {import('vue').ComputedRef<Array>} options.locautoProtectionPlans     - Available Locauto protection plans
 */
export function usePricingCalculation({
    props,
    selectedExtras,
    adapter,
    currentProduct,
    providerMarkupRate,
    providerGrossMultiplier,
    selectedLocautoProtections,
    locautoProtectionPlans,
}) {
    const { convertPrice, getSelectedCurrencySymbol } = useCurrencyConversion();

    // â”€â”€ Currency resolution â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    const pricingCurrency = computed(() => {
        return resolveVehicleCurrency(currentProduct.value, props.vehicle);
    });
    const shouldUsePartnerQuoteCurrency = computed(() => Boolean(`${props.vehicle?.partner_supplier_name || ''}`.trim()));
    const effectiveProviderMarkupRate = computed(() => resolveEffectiveProviderMarkupRate(props.vehicle, providerMarkupRate.value));
    const effectiveProviderGrossMultiplier = computed(() => toProviderGrossMultiplier(effectiveProviderMarkupRate.value));

    const vehicleTotalCurrency = computed(() => {
        return resolveVehicleCurrency(currentProduct.value, props.vehicle);
    });

    // â”€â”€ Formatting helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    const formatPrice = (val) => {
        const currencyCode = pricingCurrency.value;
        const numeric = parseFloat(val);

        if (shouldUsePartnerQuoteCurrency.value) {
            try {
                return new Intl.NumberFormat(undefined, {
                    style: 'currency',
                    currency: currencyCode,
                    maximumFractionDigits: 2,
                }).format(Number.isFinite(numeric) ? numeric : 0);
            } catch {
                return `${currencyCode} ${(Number.isFinite(numeric) ? numeric : 0).toFixed(2)}`;
            }
        }

        const converted = convertPrice(parseFloat(val), currencyCode);
        return `${getSelectedCurrencySymbol()}${converted.toFixed(2)}`;
    };

    const formatRentalPrice = (val) => {
        const numeric = parseFloat(val ?? 0);
        return formatPrice(numeric * effectiveProviderGrossMultiplier.value);
    };

    // â”€â”€ Per-extra price helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    const getExtraTotal = (extra) => {
        if (!extra) return 0;
        if (extra.total_for_booking !== undefined && extra.total_for_booking !== null) {
            return parseFloat(extra.total_for_booking) || 0;
        }
        const dailyRate = extra.daily_rate !== undefined
            ? parseFloat(extra.daily_rate)
            : (parseFloat(extra.price || 0) / props.numberOfDays);
        return dailyRate * props.numberOfDays;
    };

    const getExtraPerDay = (extra) => {
        if (!extra) return 0;
        if (extra.daily_rate !== undefined && extra.daily_rate !== null) {
            return parseFloat(extra.daily_rate) || 0;
        }
        const total = getExtraTotal(extra);
        return props.numberOfDays ? total / props.numberOfDays : total;
    };

    const getSelectedSicilyByCarExtraTotal = (extra, quantity) => {
        const qty = parseInt(quantity ?? 0, 10) || 0;
        if (qty <= 0 || !extra) return 0;
        if (!Array.isArray(extra.service_slots) || extra.service_slots.length === 0) {
            return getSicilyByCarExtraTotal(extra) * qty;
        }
        return extra.service_slots
            .slice(0, qty)
            .reduce((sum, slot) => sum + getSicilyByCarExtraTotal(slot), 0);
    };

    // â”€â”€ Totals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    const isSicilyByCar = computed(() => props.vehicle?.source === 'sicily_by_car');

    const extrasTotal = computed(() => {
        let total = 0;
        const allExtras = adapter.allExtras?.value ?? [];
        for (const [id, qty] of Object.entries(selectedExtras.value)) {
            const extra = allExtras.find(e => e.id === id)
                || props.optionalExtras?.find(e => e.id === id);
            if (!extra) continue;

            if (isSicilyByCar.value && Array.isArray(extra.service_slots) && extra.service_slots.length > 0) {
                total += getSelectedSicilyByCarExtraTotal(extra, qty);
                continue;
            }
            if (extra.total_for_booking !== undefined && extra.total_for_booking !== null) {
                total += parseFloat(extra.total_for_booking) * qty;
            } else {
                const dailyRate = extra.daily_rate !== undefined
                    ? parseFloat(extra.daily_rate)
                    : (parseFloat(extra.price) / props.numberOfDays);
                total += dailyRate * props.numberOfDays * qty;
            }
        }
        return total;
    });

    const netGrandTotal = computed(() => {
        // Access locautoProtectionTotal to ensure Vue tracks it as dependency
        const locautoProtTotal = adapter.locautoProtectionTotal?.value ?? 0;

        if (typeof adapter.computeNetTotal === 'function') {
            return adapter.computeNetTotal(extrasTotal.value, currentProduct.value);
        }

        const isLocautoRent = props.vehicle?.source === 'locauto_rent';
        if (isLocautoRent) {
            const basePrice = adapter.baseTotal?.value ?? 0;
            const protectionAmount = (selectedLocautoProtections?.value ?? []).reduce((sum, code) => {
                const plan = (locautoProtectionPlans?.value ?? []).find(p => p.code === code);
                return sum + (plan ? parseFloat(plan.amount || 0) * props.numberOfDays : 0);
            }, 0);
            return basePrice + protectionAmount + extrasTotal.value;
        }

        const isRenteon = props.vehicle?.source === 'renteon';
        const isOkMobility = props.vehicle?.source === 'okmobility';
        const isAdobeCars = props.vehicle?.source === 'adobe';

        const pkgPrice = parseFloat(currentProduct.value?.total || 0);
        const mandatoryExtra = isAdobeCars ? (adapter.mandatoryAmount?.value ?? 0) : 0;

        if (isRenteon) {
            const providerTotal = parseFloat(props.vehicle?.provider_gross_amount ?? NaN);
            const baseTotal = Number.isFinite(providerTotal) ? providerTotal : pkgPrice;
            return baseTotal + extrasTotal.value;
        }

        if (isOkMobility) {
            return (adapter.baseTotal?.value ?? pkgPrice) + extrasTotal.value;
        }

        return pkgPrice + mandatoryExtra + extrasTotal.value;
    });

    const shouldUseCommissionOnly = computed(() => {
        return shouldUseCommissionOnlyForVehicle(props.vehicle, providerMarkupRate.value);
    });

    const bookingChargeBreakdown = computed(() => {
        // Markup flows charge the markup upfront; non-markup flows use the configured deposit percentage.
        const percent = props.paymentPercentage && props.paymentPercentage > 0 ? props.paymentPercentage : 15;
        return computeBookingChargeBreakdown({
            netTotal: parseFloat(netGrandTotal.value || 0),
            markupRate: effectiveProviderMarkupRate.value,
            depositPercentage: percent,
            useCommissionOnly: shouldUseCommissionOnly.value,
        });
    });

    const grandTotal = computed(() => bookingChargeBreakdown.value.grandTotal.toFixed(2));

    const payableAmount = computed(() => bookingChargeBreakdown.value.payableAmount.toFixed(2));

    const pendingAmount = computed(() => bookingChargeBreakdown.value.pendingAmount.toFixed(2));

    const effectivePaymentPercentage = computed(() => {
        const percent = props.paymentPercentage && props.paymentPercentage > 0 ? props.paymentPercentage : 15;
        return Math.round(percent * 100) / 100;
    });

    return {
        pricingCurrency,
        vehicleTotalCurrency,
        formatPrice,
        formatRentalPrice,
        getExtraTotal,
        getExtraPerDay,
        getSelectedSicilyByCarExtraTotal,
        extrasTotal,
        netGrandTotal,
        bookingChargeBreakdown,
        grandTotal,
        payableAmount,
        pendingAmount,
        effectivePaymentPercentage,
    };
}
