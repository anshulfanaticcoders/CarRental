import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { selectableCurrencyCodes } from '@/utils/currencyRegistry';

export function useCurrency() {
    const page = usePage();
    const loading = ref(false);

    const selectedCurrency = computed(() => page.props.currency || page.props.currency_base || 'EUR');

    const supportedCurrencies = computed(() => {
        return page.props.currency_supported?.length
            ? page.props.currency_supported
            : selectableCurrencyCodes;
    });

    const changeCurrency = (newCurrency) => {
        // Changing currency reloads the page — mid-booking that throws away the
        // selected vehicle and extras. Ask first.
        if (typeof window !== 'undefined' && window.__vrooemBookingFlowActive) {
            const proceed = window.confirm(
                'Changing currency will restart your booking selection (your typed details are saved). Continue?'
            );
            if (!proceed) return;
        }

        loading.value = true;
        router.post(route('currency.update'), { currency: newCurrency }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            },
            onFinish: () => {
                loading.value = false;
            }
        });
    };

    return {
        selectedCurrency,
        supportedCurrencies,
        changeCurrency,
        loading,
    };
}
