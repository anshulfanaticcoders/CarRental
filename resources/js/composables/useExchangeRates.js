import { ref, computed, watch, onMounted, onUnmounted, onErrorCaptured } from 'vue'
import { useToast } from 'vue-toastification'

// Global exchange rates cache - shared across all instances
const globalRatesCache = ref({
  rates: {},
  baseCurrency: 'USD',
  timestamp: null,
  provider: null,
  ttl: 3600000, // 1 hour in milliseconds
  isRefreshing: false
})

// Request tracking and rate limiting
const requestTracker = ref({
  requests: [],
  lastRequestTime: null,
  requestCount: 0,
  rateLimitReset: null
})

// Loading states for different operations
const loadingStates = ref({
  conversion: false,
  singleRate: false,
  allRates: false,
  batch: false
})

// Error states for different operations
const errorStates = ref({
  conversion: null,
  singleRate: null,
  allRates: null,
  batch: null,
  network: null
})

// Retry configuration
const RETRY_CONFIG = {
  maxRetries: 3,
  baseDelay: 1000,
  maxDelay: 10000,
  backoffMultiplier: 2,
  timeoutDuration: 10000
}

// Cache configuration
const CACHE_CONFIG = {
  version: 'v1',
  storageKey: 'exchange_rates_cache',
  maxAge: 3600000, // 1 hour
  compressionThreshold: 10240 // 10KB
}

/**
 * Enhanced exchange rates composable with intelligent caching and retry logic
 */
export function useExchangeRates(options = {}) {
  const {
    defaultBaseCurrency = 'USD',
    enableCache = true,
    enableBackgroundRefresh = true,
    backgroundRefreshInterval = 300000, // 5 minutes
    enableRateLimiting = true,
    maxRequestsPerMinute = 60
  } = options

  const toast = useToast()

  // Reactive state
  const isLoading = ref(false)
  const error = ref(null)
  const rates = ref({})
  const baseCurrency = ref(defaultBaseCurrency)
  const lastUpdated = ref(null)

  // Computed properties
  const isCacheValid = computed(() => {
    if (!globalRatesCache.value.timestamp) return false
    const now = Date.now()
    const age = now - new Date(globalRatesCache.value.timestamp).getTime()
    return age < globalRatesCache.value.ttl
  })

  const cacheAge = computed(() => {
    if (!globalRatesCache.value.timestamp) return null
    const now = Date.now()
    return now - new Date(globalRatesCache.value.timestamp).getTime()
  })

  const isBackgroundRefreshing = computed(() => globalRatesCache.value.isRefreshing)

  // Rate limiting check
  const checkRateLimit = () => {
    if (!enableRateLimiting) return true

    const now = Date.now()
    const oneMinuteAgo = now - 60000

    // Clean old requests
    requestTracker.value.requests = requestTracker.value.requests.filter(
      timestamp => timestamp > oneMinuteAgo
    )

    return requestTracker.value.requests.length < maxRequestsPerMinute
  }

  // Record request for rate limiting
  const recordRequest = () => {
    if (!enableRateLimiting) return

    const now = Date.now()
    requestTracker.value.requests.push(now)
    requestTracker.value.lastRequestTime = now
    requestTracker.value.requestCount++
  }

  // Calculate retry delay with exponential backoff and jitter
  const calculateRetryDelay = (attempt) => {
    const delay = Math.min(
      RETRY_CONFIG.baseDelay * Math.pow(RETRY_CONFIG.backoffMultiplier, attempt),
      RETRY_CONFIG.maxDelay
    )

    // Add jitter to prevent thundering herd
    const jitter = delay * 0.1 * Math.random()
    return delay + jitter
  }

  // Initialize cache from localStorage
  const initializeCache = () => {
    if (!enableCache) return

    try {
      const cached = localStorage.getItem(CACHE_CONFIG.storageKey)
      if (!cached) return

      const cacheData = JSON.parse(cached)

      // Check version compatibility
      if (cacheData.version !== CACHE_CONFIG.version) {
        localStorage.removeItem(CACHE_CONFIG.storageKey)
        return
      }

      // Check if cache is still valid
      const now = Date.now()
      if (now - cacheData.timestamp > CACHE_CONFIG.maxAge) {
        localStorage.removeItem(CACHE_CONFIG.storageKey)
        return
      }

      globalRatesCache.value = {
        ...globalRatesCache.value,
        ...cacheData
      }

      // Update local reactive state
      rates.value = globalRatesCache.value.rates
      baseCurrency.value = globalRatesCache.value.baseCurrency
      lastUpdated.value = globalRatesCache.value.timestamp

    } catch (error) {
      console.warn('Failed to initialize cache:', error.message)
      localStorage.removeItem(CACHE_CONFIG.storageKey)
    }
  }

  // Save cache to localStorage
  const saveCache = () => {
    if (!enableCache) return

    try {
      const cacheData = {
        ...globalRatesCache.value,
        version: CACHE_CONFIG.version,
        timestamp: Date.now()
      }

      // Compress cache if it's too large
      let serialized = JSON.stringify(cacheData)
      if (serialized.length > CACHE_CONFIG.compressionThreshold) {
        // Remove old rates to reduce size
        const rates = {}
        const commonCurrencies = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD']
        commonCurrencies.forEach(currency => {
          if (cacheData.rates[currency]) {
            rates[currency] = cacheData.rates[currency]
          }
        })
        cacheData.rates = rates
        serialized = JSON.stringify(cacheData)
      }

      localStorage.setItem(CACHE_CONFIG.storageKey, serialized)
    } catch (error) {
      console.warn('Failed to save cache:', error.message)
    }
  }

  // Clear cache
  const clearCache = () => {
    globalRatesCache.value = {
      rates: {},
      baseCurrency: 'USD',
      timestamp: null,
      provider: null,
      ttl: globalRatesCache.value.ttl,
      isRefreshing: false
    }

    rates.value = {}
    lastUpdated.value = null

    if (enableCache) {
      try {
        localStorage.removeItem(CACHE_CONFIG.storageKey)
      } catch (error) {
        console.warn('Failed to clear cache:', error.message)
      }
    }
  }

  // Make HTTP request with timeout and retry logic
  const makeRequest = async (url, options = {}, retryCount = 0) => {
    const controller = new AbortController()
    const timeoutId = setTimeout(() => controller.abort(), RETRY_CONFIG.timeoutDuration)

    try {
      // Check rate limit
      if (!checkRateLimit()) {
        throw new Error('Rate limit exceeded')
      }

      recordRequest()

      const response = await fetch(url, {
        ...options,
        signal: controller.signal,
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Cache-Control': 'no-cache',
          ...options.headers
        }
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()
      return { success: true, data }

    } catch (error) {
      clearTimeout(timeoutId)

      // Retry logic for network errors or timeouts
      if (retryCount < RETRY_CONFIG.maxRetries &&
          (error.name === 'AbortError' || error.message.includes('Failed to fetch'))) {

        const delay = calculateRetryDelay(retryCount)
        await new Promise(resolve => setTimeout(resolve, delay))

        return makeRequest(url, options, retryCount + 1)
      }

      throw error
    }
  }

  // Fetch all exchange rates for a base currency
  const fetchAllRates = async (base = defaultBaseCurrency, forceRefresh = false) => {
    if (!forceRefresh && isCacheValid.value && globalRatesCache.value.baseCurrency === base) {
      return {
        success: true,
        rates: globalRatesCache.value.rates,
        baseCurrency: globalRatesCache.value.baseCurrency,
        timestamp: globalRatesCache.value.timestamp,
        provider: globalRatesCache.value.provider,
        cacheHit: true
      }
    }

    loadingStates.value.allRates = true
    errorStates.value.allRates = null

    try {
      const url = `/api/currency/exchange-rates?base=${encodeURIComponent(base)}`
      const result = await makeRequest(url)

      if (result.data.success) {
        const newRates = result.data.data.rates
        const provider = result.data.data.provider
        const timestamp = result.data.data.timestamp

        // Update global cache
        globalRatesCache.value = {
          rates: newRates,
          baseCurrency: base,
          timestamp: timestamp || new Date().toISOString(),
          provider: provider,
          ttl: globalRatesCache.value.ttl,
          isRefreshing: false
        }

        // Update local reactive state
        rates.value = newRates
        baseCurrency.value = base
        lastUpdated.value = timestamp

        // Save to localStorage
        saveCache()

        return {
          success: true,
          rates: newRates,
          baseCurrency: base,
          timestamp: timestamp,
          provider: provider,
          cacheHit: false
        }
      } else {
        throw new Error(result.data.error || 'Failed to fetch exchange rates')
      }

    } catch (error) {
      const errorMessage = 'Failed to fetch exchange rates'
      errorStates.value.allRates = errorMessage
      error.value = errorMessage

      console.error('Fetch all rates error:', error)

      // Return cached rates if available, even if expired
      if (Object.keys(globalRatesCache.value.rates).length > 0) {
        return {
          success: false,
          error: errorMessage,
          rates: globalRatesCache.value.rates,
          baseCurrency: globalRatesCache.value.baseCurrency,
          timestamp: globalRatesCache.value.timestamp,
          provider: globalRatesCache.value.provider,
          cacheHit: true,
          stale: true
        }
      }

      return { success: false, error: errorMessage }

    } finally {
      loadingStates.value.allRates = false
    }
  }

  // Fetch single exchange rate
  const fetchSingleRate = async (fromCurrency, toCurrency) => {
    if (!fromCurrency || !toCurrency) {
      throw new Error('Both fromCurrency and toCurrency are required')
    }

    // Check cache first
    const cacheKey = `${fromCurrency}_${toCurrency}`
    if (isCacheValid.value && globalRatesCache.value.rates[toCurrency]) {
      return {
        success: true,
        rate: globalRatesCache.value.rates[toCurrency],
        fromCurrency,
        toCurrency,
        timestamp: globalRatesCache.value.timestamp,
        provider: globalRatesCache.value.provider,
        cacheHit: true
      }
    }

    loadingStates.value.singleRate = true
    errorStates.value.singleRate = null

    try {
      const url = `/api/currency/exchange-rate?from=${encodeURIComponent(fromCurrency)}&to=${encodeURIComponent(toCurrency)}`
      const result = await makeRequest(url)

      if (result.data.success) {
        const rateData = result.data.data

        return {
          success: true,
          rate: rateData.rate,
          fromCurrency,
          toCurrency,
          timestamp: rateData.timestamp,
          provider: rateData.provider,
          cacheHit: rateData.cache_hit || false
        }
      } else {
        throw new Error(result.data.error || 'Failed to fetch exchange rate')
      }

    } catch (error) {
      const errorMessage = 'Failed to fetch exchange rate'
      errorStates.value.singleRate = errorMessage

      console.error('Fetch single rate error:', error)

      return { success: false, error: errorMessage }

    } finally {
      loadingStates.value.singleRate = false
    }
  }

  // Convert currency amount
  const convertCurrency = async (amount, fromCurrency, toCurrency) => {
    // Convert string amounts to numbers and validate
    const numericAmount = parseFloat(amount);

    if (isNaN(numericAmount) || numericAmount < 0) {
      throw new Error('Invalid amount for conversion')
    }

    if (fromCurrency === toCurrency) {
      return {
        success: true,
        originalAmount: numericAmount,
        convertedAmount: numericAmount,
        fromCurrency,
        toCurrency,
        rate: 1.0,
        conversionMethod: 'same_currency'
      }
    }

    loadingStates.value.conversion = true
    errorStates.value.conversion = null

    try {
      const url = '/api/currency/convert'
      const result = await makeRequest(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          amount: numericAmount.toString(),
          from: fromCurrency,
          to: toCurrency
        })
      })

      if (result.data.success) {
        const conversionData = result.data.data

        return {
          success: true,
          originalAmount: conversionData.original_amount,
          convertedAmount: conversionData.converted_amount,
          fromCurrency: conversionData.from_currency,
          toCurrency: conversionData.to_currency,
          rate: conversionData.rate,
          timestamp: conversionData.rate_timestamp,
          conversionMethod: conversionData.conversion_method,
          cacheHit: conversionData.cache_hit || false
        }
      } else {
        throw new Error(result.data.error || 'Currency conversion failed')
      }

    } catch (error) {
      const errorMessage = 'Currency conversion failed'
      errorStates.value.conversion = errorMessage

      console.error('Currency conversion error:', error)

      // Return fallback conversion
      return {
        success: false,
        error: errorMessage,
        originalAmount: numericAmount,
        convertedAmount: numericAmount,
        fromCurrency,
        toCurrency,
        rate: 1.0,
        conversionMethod: 'fallback'
      }

    } finally {
      loadingStates.value.conversion = false
    }
  }

  // Batch convert multiple amounts
  const batchConvert = async (conversions) => {
    if (!Array.isArray(conversions) || conversions.length === 0) {
      throw new Error('Invalid conversions array')
    }

    loadingStates.value.batch = true
    errorStates.value.batch = null

    try {
      const results = []

      for (const conversion of conversions) {
        const { amount, from, to } = conversion

        // Validate amount before conversion
        if (amount === null || amount === undefined || isNaN(parseFloat(amount))) {
          results.push({
            success: false,
            error: 'Invalid amount',
            originalAmount: 0,
            convertedAmount: 0,
            fromCurrency: from,
            toCurrency: to,
            rate: 1.0,
            conversionMethod: 'fallback'
          })
          continue
        }

        const result = await convertCurrency(amount, from, to)
        results.push(result)

        // Add small delay between requests to avoid rate limiting
        if (conversions.length > 1) {
          await new Promise(resolve => setTimeout(resolve, 100))
        }
      }

      return {
        success: true,
        conversions: results,
        totalProcessed: results.length
      }

    } catch (error) {
      const errorMessage = 'Batch conversion failed'
      errorStates.value.batch = errorMessage

      console.error('Batch conversion error:', error)
      return { success: false, error: errorMessage }

    } finally {
      loadingStates.value.batch = false
    }
  }

  // Background refresh
  let refreshTimerId = null

  const startBackgroundRefresh = () => {
    if (!enableBackgroundRefresh || refreshTimerId) return

    refreshTimerId = setInterval(async () => {
      if (!globalRatesCache.value.isRefreshing && !isCacheValid.value) {
        globalRatesCache.value.isRefreshing = true
        await fetchAllRates(baseCurrency.value, true)
        globalRatesCache.value.isRefreshing = false
      }
    }, backgroundRefreshInterval)
  }

  const stopBackgroundRefresh = () => {
    if (refreshTimerId) {
      clearInterval(refreshTimerId)
      refreshTimerId = null
    }
  }

  // Enhanced batch conversion with API endpoint and rate limiting
  const batchConvertPricesWithApi = async (priceList) => {
    if (!priceList || priceList.length === 0) {
      return []
    }

    // Group conversions by currency pair to optimize API calls
    const groupedConversions = {}
    priceList.forEach((item, index) => {
      const key = `${item.fromCurrency || 'USD'}_to_${selectedCurrency.value}`
      if (!groupedConversions[key]) {
        groupedConversions[key] = []
      }
      groupedConversions[key].push({
        amount: item.amount,
        from: item.fromCurrency || 'USD',
        to: selectedCurrency.value,
        index: index
      })
    })

    // Prepare batch request data
    const batchConversions = []
    Object.values(groupedConversions).forEach(group => {
      group.forEach(item => {
        batchConversions.push({
          amount: item.amount,
          from: item.from,
          to: item.to
        })
      })
    })

    try {
      loadingStates.value.batch = true
      errorStates.value.batch = null

      // Call batch conversion API
      const result = await makeRequest('/api/currency/batch-convert', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          conversions: batchConversions
        })
      })

      if (result.data.success) {
        const conversions = result.data.data.conversions

        // Map results back to original items
        return priceList.map((item, index) => {
          const conversion = conversions[index]
          return {
            ...item,
            convertedAmount: conversion.success ? conversion.converted_amount : item.amount,
            originalAmount: conversion.original_amount,
            fromCurrency: conversion.from_currency,
            toCurrency: conversion.to_currency,
            rate: conversion.rate,
            success: conversion.success,
            error: conversion.error || null
          }
        })
      } else {
        throw new Error(result.data.error || 'Batch conversion failed')
      }

    } catch (error) {
      console.warn('Batch conversion failed:', error.message)
      errorStates.value.batch = error.message

      // Fallback to individual conversions
      const fallbackResults = await Promise.allSettled(
        batchConversions.map(conv => convertCurrency(conv.amount, conv.from, conv.to))
      )

      return priceList.map((item, index) => {
        const result = fallbackResults[index]
        if (result.status === 'fulfilled' && result.value.success) {
          return {
            ...item,
            convertedAmount: result.value.convertedAmount,
            success: true
          }
        } else {
          return {
            ...item,
            convertedAmount: item.amount,
            success: false,
            error: 'Conversion failed'
          }
        }
      })
    } finally {
      loadingStates.value.batch = false
    }
  }

  // Debounced batch conversion to prevent API spam
  let batchTimeoutId = null
  const pendingConversions = ref([])

  const queueBatchConversion = (priceList, immediate = false) => {
    if (immediate) {
      // Process immediately
      pendingConversions.value = []
      return batchConvertPricesWithApi(priceList)
    }

    // Add to pending queue
    pendingConversions.value.push(...priceList)

    // Clear existing timeout
    if (batchTimeoutId) {
      clearTimeout(batchTimeoutId)
    }

    // Set new timeout for batch processing
    batchTimeoutId = setTimeout(async () => {
      if (pendingConversions.value.length > 0) {
        const conversionsToProcess = [...pendingConversions.value]
        pendingConversions.value = []

        try {
          await batchConvertPricesWithApi(conversionsToProcess)
        } catch (error) {
          console.warn('Queued batch conversion failed:', error.message)
        }
      }
    }, 500) // 500ms delay to batch multiple requests
  }

  // Get operation loading state
  const isLoadingOperation = (operation) => {
    return loadingStates.value[operation] || false
  }

  // Get operation error
  const getOperationError = (operation) => {
    return errorStates.value[operation] || null
  }

  // Clear errors
  const clearErrors = () => {
    error.value = null
    Object.keys(errorStates.value).forEach(key => {
      errorStates.value[key] = null
    })
  }

  // Get service statistics
  const getStats = () => {
    return {
      cacheAge: cacheAge.value,
      isCacheValid: isCacheValid.value,
      baseCurrency: baseCurrency.value,
      totalRates: Object.keys(rates.value).length,
      lastUpdated: lastUpdated.value,
      requestCount: requestTracker.value.requestCount,
      backgroundRefreshEnabled: enableBackgroundRefresh,
      backgroundRefreshActive: !!refreshTimerId
    }
  }

  // Error boundary for this composable
  onErrorCaptured((error, instance, info) => {
    console.error('useExchangeRates error boundary:', { error, instance, info })
    error.value = 'Exchange rate service encountered an error'

    toast.error('Exchange rate service temporarily unavailable', {
      timeout: 5000,
      closeOnClick: true
    })

    return false
  })

  // Initialize on mount
  onMounted(async () => {
    initializeCache()

    // If cache is empty or invalid, fetch fresh rates
    if (!isCacheValid.value) {
      await fetchAllRates(baseCurrency.value)
    }

    // Start background refresh
    startBackgroundRefresh()
  })

  // Cleanup on unmount
  onUnmounted(() => {
    stopBackgroundRefresh()
  })

  return {
    // State
    rates,
    baseCurrency,
    isLoading,
    error,
    lastUpdated,
    isCacheValid,
    cacheAge,
    isBackgroundRefreshing,

    // Loading and error states
    isLoadingOperation,
    getOperationError,

    // Methods
    fetchAllRates,
    fetchSingleRate,
    convertCurrency,
    batchConvert,
    batchConvertPricesWithApi,
    queueBatchConversion,
    clearCache,
    clearErrors,
    getStats,

    // Configuration
    RETRY_CONFIG,
    CACHE_CONFIG
  }
}