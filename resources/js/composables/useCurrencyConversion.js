import { computed, ref } from 'vue';
import { useCurrency } from './useCurrency';

const exchangeRates = ref(null);
const loading = ref(false);
let fetchPromise = null;

export function useCurrencyConversion() {
    const { selectedCurrency } = useCurrency();

    // Currency symbols mapping
    const currencySymbols = {
        'USD': '$',
        'EUR': '€',
        'GBP': '£',
        'JPY': '¥',
        'AUD': 'A$',
        'CAD': 'C$',
        'CHF': 'Fr',
        'CNH': '¥',
        'HKD': 'HK$',
        'SGD': 'S$',
        'SEK': 'kr',
        'KRW': '₩',
        'NOK': 'kr',
        'NZD': 'NZ$',
        'INR': '₹',
        'MXN': '$',
        'BRL': 'R$',
        'RUB': '₽',
        'ZAR': 'R',
        'AED': 'د.إ',
        'MAD': 'د.م.',
        'TRY': '₺',
        'JOD': 'د.ا.',
        'ISK': 'kr.',
        'AZN': '₼',
        'MYR': 'RM',
        'OMR': '﷼',
        'UGX': 'USh',
        'NIO': 'C$',
        'ALL': 'L'
    };

    // Fetch exchange rates
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

    // Convert price from one currency to another
    const convertPrice = (price, fromCurrency) => {
        const numericPrice = parseFloat(price);
        if (isNaN(numericPrice)) return 0;

        if (!exchangeRates.value || !fromCurrency || !selectedCurrency.value) {
            return numericPrice;
        }

        const fromCurrencyCode = fromCurrency.toUpperCase();
        const toCurrencyCode = selectedCurrency.value.toUpperCase();

        // If same currency, no conversion needed
        if (fromCurrencyCode === toCurrencyCode) {
            return numericPrice;
        }

        const rateFrom = exchangeRates.value[fromCurrencyCode];
        const rateTo = exchangeRates.value[toCurrencyCode];

        if (rateFrom && rateTo) {
            return (numericPrice / rateFrom) * rateTo;
        }

        return numericPrice; // Fallback to original price
    };

    // Get currency symbol for selected currency
    const getSelectedCurrencySymbol = () => {
        if (!selectedCurrency.value) return '$';
        return currencySymbols[selectedCurrency.value.toUpperCase()] || selectedCurrency.value;
    };

    // Get currency symbol for a specific currency code
    const getCurrencySymbol = (currencyCode) => {
        if (!currencyCode) return '$';
        return currencySymbols[currencyCode.toUpperCase()] || currencyCode;
    };

    return {
        selectedCurrency,
        exchangeRates,
        loading,
        convertPrice,
        getSelectedCurrencySymbol,
        getCurrencySymbol,
        fetchExchangeRates
    };
}
