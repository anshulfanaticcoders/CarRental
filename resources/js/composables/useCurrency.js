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
