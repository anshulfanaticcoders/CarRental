import { ref } from 'vue';
import { useCurrency } from './useCurrency';
import {
    getCurrencySymbol as registryCurrencySymbol,
    normalizeCurrencyCode as registryNormalizeCurrencyCode,
} from '@/utils/currencyRegistry';

const exchangeRates = ref(null);
const loading = ref(false);
let fetchPromise = null;

export function useCurrencyConversion() {
    const { selectedCurrency } = useCurrency();

    const normalizeCurrencyCode = (currency) => registryNormalizeCurrencyCode(currency, 'EUR');

    const fetchExchangeRates = async () => {
        if (exchangeRates.value) return exchangeRates.value;
        if (fetchPromise) return fetchPromise;

        loading.value = true;
        fetchPromise = (async () => {
            try {
                const response = await fetch('/api/currency-rates');
                const data = await response.json();
                if (data.success) {
                    exchangeRates.value = data.rates;
                } else {
                    console.error('Failed to fetch exchange rates:', data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Error fetching exchange rates:', error);
            } finally {
                loading.value = false;
                fetchPromise = null;
            }

            return exchangeRates.value;
        })();

        return fetchPromise;
    };

    const convertPrice = (price, fromCurrency) => {
        const numericPrice = parseFloat(price);
        if (isNaN(numericPrice)) return 0;

        if (!exchangeRates.value || !fromCurrency || !selectedCurrency.value) {
            return numericPrice;
        }

        const fromCurrencyCode = normalizeCurrencyCode(fromCurrency);
        const toCurrencyCode = normalizeCurrencyCode(selectedCurrency.value);

        if (fromCurrencyCode === toCurrencyCode) {
            return numericPrice;
        }

        const rateFrom = exchangeRates.value[fromCurrencyCode];
        const rateTo = exchangeRates.value[toCurrencyCode];

        if (rateFrom && rateTo) {
            return (numericPrice / rateFrom) * rateTo;
        }

        return numericPrice;
    };

    const getSelectedCurrencySymbol = () => {
        if (!selectedCurrency.value) return registryCurrencySymbol('EUR');
        return registryCurrencySymbol(selectedCurrency.value);
    };

    const getCurrencySymbol = (currencyCode) => {
        if (!currencyCode) return registryCurrencySymbol('EUR');
        return registryCurrencySymbol(currencyCode);
    };

    return {
        selectedCurrency,
        exchangeRates,
        loading,
        convertPrice,
        getSelectedCurrencySymbol,
        getCurrencySymbol,
        normalizeCurrencyCode,
        fetchExchangeRates,
    };
}
