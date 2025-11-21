import { ref, computed } from 'vue';

// Shared state for currency conversion across all Wheelsys pages
const exchangeRates = ref(null);
const currencySymbols = ref({});
const isLoading = ref(false);
const lastFetchTime = ref(null);
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes

// Symbol to currency code mapping for proper API usage
const symbolToCodeMap = {
    '$': 'USD',
    '€': 'EUR',
    '£': 'GBP',
    '¥': 'JPY',
    'A$': 'AUD',
    'C$': 'CAD',
    'Fr': 'CHF',
    'HK$': 'HKD',
    'S$': 'SGD',
    'kr': 'SEK',
    '₩': 'KRW',
    'kr': 'NOK',
    'NZ$': 'NZD',
    '₹': 'INR',
    'Mex$': 'MXN',
    'R': 'ZAR',
    'R$': 'BRL',
    '₽': 'RUB',
    '₺': 'TRY',
    '฿': 'THB',
    '؋': 'LBP',
    '﷼': 'IRR',
    '₮': 'MNT',
    '円': 'JPY',
    '元': 'CNY',
    'ƒ': 'ANG',
    '৳': 'BDT',
    '៛': 'KHR',
    '₭': 'LAK',
    '₦': 'NGN',
    '₨': 'PKR',
    '₪': 'ILS',
    '₫': 'LAK',
    '₸': 'KZT',
    '₼': 'AZN',
    '₴': 'UAH',
    '₡': 'CRC',
    '₲': 'PYG',
    '₱': 'PHP',
    '૱': 'INR',
    '௹': 'LKR',
    'রু': 'BDT'
};

/**
 * Shared currency composable for Wheelsys pages
 * Provides consistent currency conversion across SearchResults.vue, Show.vue, and Booking.vue
 */
export function useWheelsysCurrency() {

    /**
     * Fetch exchange rates from the API with caching
     */
    const fetchExchangeRates = async () => {
        const now = Date.now();

        // Return cached data if still valid
        if (lastFetchTime.value && (now - lastFetchTime.value < CACHE_DURATION) && exchangeRates.value) {
            return exchangeRates.value;
        }

        try {
            isLoading.value = true;

            // Use the same API endpoint as Show.vue for consistency
            const response = await fetch(`${import.meta.env.VITE_EXCHANGERATE_API_BASE_URL}/v6/${import.meta.env.VITE_EXCHANGERATE_API_KEY}/latest/USD`);
            const data = await response.json();

            if (data.result === 'success') {
                exchangeRates.value = data.conversion_rates;
                lastFetchTime.value = now;

                console.log('Wheelsys: Exchange rates updated successfully', {
                    timestamp: new Date(now).toISOString(),
                    sampleRates: {
                        USD: data.conversion_rates.USD,
                        EUR: data.conversion_rates.EUR,
                        GBP: data.conversion_rates.GBP,
                        INR: data.conversion_rates.INR
                    }
                });

                return exchangeRates.value;
            } else {
                console.error('Wheelsys: Failed to fetch exchange rates:', data['error-type']);
                return null;
            }
        } catch (error) {
            console.error('Wheelsys: Error fetching exchange rates:', error);
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Load currency symbols from local JSON file
     */
    const loadCurrencySymbols = async () => {
        try {
            const response = await fetch('/currency.json');
            const data = await response.json();

            currencySymbols.value = data.reduce((acc, curr) => {
                acc[curr.code] = curr.symbol;
                return acc;
            }, {});

            console.log('Wheelsys: Currency symbols loaded', {
                count: Object.keys(currencySymbols.value).length,
                sample: currencySymbols.value
            });
        } catch (error) {
            console.error("Wheelsys: Error loading currency symbols:", error);
            // Fallback to basic symbols
            currencySymbols.value = {
                'USD': '$',
                'EUR': '€',
                'GBP': '£',
                'INR': '₹'
            };
        }
    };

    /**
     * Convert currency using shared exchange rates
     * @param {number} price - The price to convert
     * @param {string} fromCurrency - Source currency code
     * @param {string} toCurrency - Target currency code (optional, defaults to selectedCurrency)
     * @returns {number} - Converted price
     */
    const convertCurrency = (price, fromCurrency, toCurrency = null) => {
        const numericPrice = parseFloat(price);

        if (isNaN(numericPrice) || numericPrice <= 0) {
            console.warn('Wheelsys: Invalid price for conversion', { price, fromCurrency });
            return 0;
        }

        // Initialize exchange rates if not loaded
        if (!exchangeRates.value) {
            console.warn('Wheelsys: Exchange rates not loaded, returning original price');
            return numericPrice;
        }

        // Handle symbol to currency code conversion
        const fromCurrencyCode = symbolToCodeMap[fromCurrency] || fromCurrency;
        const targetCurrency = toCurrency || 'USD'; // Default to USD if no target specified

        if (!fromCurrencyCode || !targetCurrency) {
            console.warn('Wheelsys: Invalid currency codes', { fromCurrency, targetCurrency });
            return numericPrice;
        }

        if (fromCurrencyCode === targetCurrency) {
            return numericPrice;
        }

        const rateFrom = exchangeRates.value[fromCurrencyCode];
        const rateTo = exchangeRates.value[targetCurrency];

        if (!rateFrom || !rateTo) {
            console.warn('Wheelsys: Missing exchange rates', {
                fromCurrencyCode,
                targetCurrency,
                rateFrom,
                rateTo,
                availableRates: Object.keys(exchangeRates.value).slice(0, 10)
            });
            return numericPrice;
        }

        // Convert: price in fromCurrency → USD → toCurrency
        const priceInUSD = numericPrice / rateFrom;
        const convertedPrice = priceInUSD * rateTo;

        return convertedPrice;
    };

    /**
     * Get currency symbol for display
     * @param {string} currencyCode - Currency code
     * @returns {string} - Currency symbol
     */
    const getCurrencySymbol = (currencyCode) => {
        if (!currencyCode) return '$';

        const symbol = currencySymbols.value[currencyCode];
        if (symbol) {
            return symbol;
        }

        // Fallback to common symbols
        const fallbackSymbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'INR': '₹',
            'JPY': '¥'
        };

        return fallbackSymbols[currencyCode] || '$';
    };

    /**
     * Format price for display with currency symbol
     * @param {number} price - The price to format
     * @param {string} currency - Source currency code
     * @param {string} targetCurrency - Target currency code (optional)
     * @returns {string} - Formatted price string
     */
    const formatPrice = (price, currency = 'USD', targetCurrency = null) => {
        if (!price || price === 0) return 'Price on request';

        const originalCurrency = currency || 'USD';
        const convertedPrice = convertCurrency(price, originalCurrency, targetCurrency);
        const displayCurrency = targetCurrency || originalCurrency;
        const currencySymbol = getCurrencySymbol(displayCurrency);

        return `${currencySymbol}${convertedPrice.toFixed(2)}`;
    };

    /**
     * Initialize currency system (call on component mount)
     */
    const initializeCurrency = async () => {
        console.log('Wheelsys: Initializing currency system');

        // Load both exchange rates and symbols in parallel
        await Promise.all([
            fetchExchangeRates(),
            loadCurrencySymbols()
        ]);

        console.log('Wheelsys: Currency system initialized', {
            exchangeRatesLoaded: !!exchangeRates.value,
            symbolsLoaded: Object.keys(currencySymbols.value).length
        });
    };

    /**
     * Force refresh exchange rates
     */
    const refreshExchangeRates = async () => {
        lastFetchTime.value = 0; // Reset cache
        return await fetchExchangeRates();
    };

    // Computed properties for reactive access
    const isReady = computed(() => exchangeRates.value && Object.keys(currencySymbols.value).length > 0);
    const loading = computed(() => isLoading.value);

    return {
        // State
        exchangeRates,
        currencySymbols,
        loading,
        isReady,

        // Methods
        fetchExchangeRates,
        loadCurrencySymbols,
        convertCurrency,
        getCurrencySymbol,
        formatPrice,
        initializeCurrency,
        refreshExchangeRates
    };
}