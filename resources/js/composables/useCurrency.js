import { ref, computed, watch, onMounted, onErrorCaptured } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'vue-toastification'

// Global currency state - shared across all instances
const globalCurrencyState = ref({
  selectedCurrency: 'USD',
  supportedCurrencies: [],
  currencySymbols: {},
  loading: false,
  error: null,
  lastUpdated: null,
  detectionMethod: 'manual'
})

// Loading state tracking
const loadingStates = ref({
  detection: false,
  conversion: false,
  symbols: false,
  preferences: false
})

// Error state tracking
const errorStates = ref({
  detection: null,
  conversion: null,
  symbols: null,
  preferences: null
})

// Retry configuration
const RETRY_CONFIG = {
  maxRetries: 3,
  retryDelay: 1000,
  backoffMultiplier: 2
}

/**
 * Enhanced currency composable with comprehensive error handling and loading states
 */
export function useCurrency(initialCurrency = null) {
  const toast = useToast()
  const page = usePage()

  // Local state with fallback to global state
  const selectedCurrency = ref(initialCurrency || page.props.filters?.currency || 'USD')
  const isLoading = ref(false)
  const error = ref(null)

  // Computed properties
  const currentCurrency = computed({
    get: () => globalCurrencyState.value.selectedCurrency,
    set: (value) => {
      globalCurrencyState.value.selectedCurrency = value
      selectedCurrency.value = value
    }
  })

  const isGlobalLoading = computed(() => globalCurrencyState.value.loading)
  const globalError = computed(() => globalCurrencyState.value.error)
  const supportedCurrencies = computed(() => globalCurrencyState.value.supportedCurrencies)
  const currencySymbols = computed(() => globalCurrencyState.value.currencySymbols)
  const detectionMethod = computed(() => globalCurrencyState.value.detectionMethod)

  // Check if specific operation is loading
  const isOperationLoading = (operation) => {
    return loadingStates.value[operation] || false
  }

  // Get specific operation error
  const getOperationError = (operation) => {
    return errorStates.value[operation] || null
  }

  // Detect currency based on user's IP or locale
  const detectCurrency = async (options = {}) => {
    const {
      preferLocale = true,
      fallbackCurrency = 'USD',
      showToast = true
    } = options

    loadingStates.value.detection = true
    errorStates.value.detection = null

    try {
      let detectionResult = null

      // Try IP-based detection first
      if (!options.localeOnly) {
        try {
          const response = await fetch('/api/currency/detect', {
            method: 'GET',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })

          if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`)
          }

          const data = await response.json()

          if (data.success) {
            detectionResult = {
              currency: data.data.currency,
              confidence: data.data.confidence,
              method: data.data.detection_method,
              countryCode: data.data.country_code
            }
          } else if (data.fallback_currency) {
            detectionResult = {
              currency: data.fallback_currency,
              confidence: data.confidence,
              method: data.detection_method,
              fallback: true
            }
          }
        } catch (error) {
          console.warn('IP-based detection failed:', error.message)

          if (preferLocale) {
            // Try locale-based detection as fallback
            const locale = navigator.language || navigator.userLanguage || 'en-US'
            try {
              const localeResponse = await fetch(`/api/currency/detect-locale?locale=${encodeURIComponent(locale)}`, {
                headers: {
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest'
                }
              })

              if (localeResponse.ok) {
                const localeData = await localeResponse.json()
                if (localeData.success) {
                  detectionResult = {
                    currency: localeData.data.currency,
                    confidence: localeData.data.confidence,
                    method: localeData.data.detection_method,
                    countryCode: localeData.data.country_code
                  }
                }
              }
            } catch (localeError) {
              console.warn('Locale detection failed:', localeError.message)
            }
          }
        }
      }

      // Set detected currency or fallback
      const finalCurrency = detectionResult?.currency || fallbackCurrency
      setCurrentCurrency(finalCurrency, {
        method: detectionResult?.method || 'fallback',
        confidence: detectionResult?.confidence || 'low',
        autoDetected: true
      })

      // Show toast notification if enabled
      if (showToast && detectionResult && !detectionResult.fallback) {
        toast.success(`Currency automatically set to ${finalCurrency}`, {
          timeout: 3000,
          closeOnClick: true
        })
      }

      globalCurrencyState.value.detectionMethod = detectionResult?.method || 'fallback'

      return {
        success: true,
        currency: finalCurrency,
        detection: detectionResult
      }

    } catch (error) {
      const errorMessage = 'Currency detection failed'
      errorStates.value.detection = errorMessage
      console.error('Currency detection error:', error)

      // Use fallback currency
      setCurrentCurrency(fallbackCurrency, {
        method: 'fallback',
        confidence: 'low',
        error: errorMessage
      })

      if (showToast) {
        toast.error(errorMessage, {
          timeout: 5000,
          closeOnClick: true
        })
      }

      return {
        success: false,
        currency: fallbackCurrency,
        error: errorMessage
      }
    } finally {
      loadingStates.value.detection = false
    }
  }

  // Set current currency with validation and persistence
  const setCurrentCurrency = async (currencyCode, options = {}) => {
    const {
      persist = true,
      showToast = false,
      skipValidation = false
    } = options

    if (!skipValidation && !isValidCurrencyCode(currencyCode)) {
      const error = `Invalid currency code: ${currencyCode}`
      errorStates.value.preferences = error

      if (showToast) {
        toast.error(error, { timeout: 5000 })
      }

      return { success: false, error }
    }

    isLoading.value = true

    try {
      const oldCurrency = currentCurrency.value
      currentCurrency.value = currencyCode
      selectedCurrency.value = currencyCode

      globalCurrencyState.value.lastUpdated = new Date().toISOString()

      // Persist to localStorage
      if (persist) {
        loadingStates.value.preferences = true

        try {
          localStorage.setItem('preferred_currency', currencyCode)
          localStorage.setItem('currency_preferences', JSON.stringify({
            currency: currencyCode,
            method: options.method || 'manual',
            confidence: options.confidence || 'high',
            timestamp: new Date().toISOString()
          }))
        } catch (storageError) {
          console.warn('Failed to persist currency preference:', storageError.message)
        }

        loadingStates.value.preferences = false
      }

      // Show success toast
      if (showToast && oldCurrency !== currencyCode) {
        const symbol = currencySymbols.value[currencyCode] || currencyCode
        toast.success(`Currency changed to ${symbol}`, {
          timeout: 3000,
          closeOnClick: true
        })
      }

      errorStates.value.preferences = null

      return { success: true, currency: currencyCode }

    } catch (error) {
      const errorMessage = 'Failed to set currency'
      errorStates.value.preferences = errorMessage

      if (showToast) {
        toast.error(errorMessage, { timeout: 5000 })
      }

      console.error('Set currency error:', error)
      return { success: false, error: errorMessage }

    } finally {
      isLoading.value = false
    }
  }

  // Load supported currencies and symbols
  const loadSupportedCurrencies = async (retryCount = 0) => {
    loadingStates.value.symbols = true
    errorStates.value.symbols = null

    try {
      const response = await fetch('/api/currency', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Cache-Control': 'no-cache'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`)
      }

      const data = await response.json()

      if (data.success && data.data) {
        // Update currency symbols
        const symbols = {}
        data.data.forEach(currency => {
          symbols[currency.code] = currency.symbol
        })

        globalCurrencyState.value.currencySymbols = symbols
        globalCurrencyState.value.supportedCurrencies = data.data
        globalCurrencyState.value.lastUpdated = new Date().toISOString()

        errorStates.value.symbols = null

        return { success: true, currencies: data.data }
      } else {
        throw new Error(data.error || 'Failed to load currencies')
      }

    } catch (error) {
      const errorMessage = 'Failed to load supported currencies'
      errorStates.value.symbols = errorMessage

      console.error('Load currencies error:', error)

      // Retry logic
      if (retryCount < RETRY_CONFIG.maxRetries) {
        const delay = RETRY_CONFIG.retryDelay * Math.pow(RETRY_CONFIG.backoffMultiplier, retryCount)

        setTimeout(() => {
          loadSupportedCurrencies(retryCount + 1)
        }, delay)
      }

      return { success: false, error: errorMessage }

    } finally {
      loadingStates.value.symbols = false
    }
  }

  // Load saved preferences from localStorage
  const loadSavedPreferences = () => {
    try {
      const savedCurrency = localStorage.getItem('preferred_currency')
      const savedPrefs = localStorage.getItem('currency_preferences')

      if (savedCurrency && isValidCurrencyCode(savedCurrency)) {
        setCurrentCurrency(savedCurrency, {
          persist: false,
          skipValidation: true,
          showToast: false
        })

        if (savedPrefs) {
          try {
            const prefs = JSON.parse(savedPrefs)
            globalCurrencyState.value.detectionMethod = prefs.method || 'saved'
          } catch (e) {
            console.warn('Failed to parse saved preferences:', e.message)
          }
        }
      }
    } catch (error) {
      console.warn('Failed to load saved preferences:', error.message)
    }
  }

  // Get currency symbol
  const getCurrencySymbol = (currencyCode) => {
    return currencySymbols.value[currencyCode] || currencyCode || '$'
  }

  // Validate currency code
  const isValidCurrencyCode = (code) => {
    if (!code || typeof code !== 'string') return false
    return /^[A-Z]{3}$/i.test(code)
  }

  // Format currency amount
  const formatCurrency = (amount, currencyCode = null, options = {}) => {
    const {
      minimumFractionDigits = 2,
      maximumFractionDigits = 2,
      showSymbol = true,
      locale = 'en-US'
    } = options

    const currency = currencyCode || currentCurrency.value
    const symbol = showSymbol ? getCurrencySymbol(currency) : ''

    try {
      const formattedAmount = new Intl.NumberFormat(locale, {
        minimumFractionDigits,
        maximumFractionDigits
      }).format(amount)

      return showSymbol ? `${symbol}${formattedAmount}` : formattedAmount
    } catch (error) {
      console.warn('Currency formatting failed:', error.message)
      const fallbackAmount = parseFloat(amount).toFixed(maximumFractionDigits)
      return showSymbol ? `${symbol}${fallbackAmount}` : fallbackAmount
    }
  }

  // Clear all errors
  const clearErrors = () => {
    error.value = null
    globalCurrencyState.value.error = null
    Object.keys(errorStates.value).forEach(key => {
      errorStates.value[key] = null
    })
  }

  // Get currency service health
  const getServiceHealth = async () => {
    try {
      const response = await fetch('/api/currency/health')
      const data = await response.json()
      return data
    } catch (error) {
      console.warn('Health check failed:', error.message)
      return { success: false, error: error.message }
    }
  }

  // Error boundary for this composable
  onErrorCaptured((error, instance, info) => {
    console.error('useCurrency error boundary:', { error, instance, info })
    error.value = 'Currency service encountered an error'
    globalCurrencyState.value.error = 'Currency service temporarily unavailable'

    toast.error('Currency service temporarily unavailable', {
      timeout: 5000,
      closeOnClick: true
    })

    return false // Prevent error from propagating further
  })

  // Initialize on mount
  onMounted(async () => {
    await loadSupportedCurrencies()
    loadSavedPreferences()

    // Auto-detect currency if no preference is saved
    if (!localStorage.getItem('preferred_currency')) {
      await detectCurrency({ showToast: false })
    }
  })

  // Watch for global changes
  watch(() => globalCurrencyState.value.selectedCurrency, (newCurrency) => {
    selectedCurrency.value = newCurrency
  })

  return {
    // State
    currentCurrency,
    selectedCurrency,
    supportedCurrencies,
    currencySymbols,
    isLoading,
    isGlobalLoading,
    error,
    globalError,
    detectionMethod,

    // Loading states
    isOperationLoading,

    // Error states
    getOperationError,

    // Methods
    detectCurrency,
    setCurrentCurrency,
    loadSupportedCurrencies,
    loadSavedPreferences,
    getCurrencySymbol,
    formatCurrency,
    isValidCurrencyCode,
    clearErrors,
    getServiceHealth,

    // Constants
    RETRY_CONFIG
  }
}