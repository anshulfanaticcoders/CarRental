import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

export function useCurrency() {
    const page = usePage();

    const selectedCurrency = computed(() => page.props.currency);

    const supportedCurrencies = [
        'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD', 'CHF', 'CNH', 'HKD', 'SGD',
        'SEK', 'KRW', 'NOK', 'NZD', 'INR', 'MXN', 'BRL', 'RUB', 'ZAR', 'AED'
    ];

    const changeCurrency = (newCurrency) => {
        router.post(route('currency.update'), { currency: newCurrency }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                window.location.reload();
            }
        });
    };

    return {
        selectedCurrency,
        supportedCurrencies,
        changeCurrency,
    };
}
