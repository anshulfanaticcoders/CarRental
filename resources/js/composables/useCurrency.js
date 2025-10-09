import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useCurrency() {
    const page = usePage();
    const loading = ref(false);

    const selectedCurrency = computed(() => page.props.currency);

    const supportedCurrencies = [
        'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNH', 'HKD', 'SGD',
        'SEK', 'KRW', 'NOK', 'NZD', 'INR', 'MXN', 'BRL', 'RUB', 'ZAR', 'AED',
        'MAD', 'TRY', 'JOD', 'ISK', 'AZN', 'MYR', 'OMR', 'UGX', 'NIO'
    ];

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
