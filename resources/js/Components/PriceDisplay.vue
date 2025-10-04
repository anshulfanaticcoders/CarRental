<template>
  <div
    class="price-display"
    :class="{
      'price-display--loading': isLoading,
      'price-display--error': hasError,
      'price-display--compact': compact,
      'price-display--large': large
    }"
  >
    <!-- Loading State -->
    <div v-if="isLoading" class="price-display__loading">
      <div class="price-display__skeleton">
        <div class="price-display__skeleton-symbol"></div>
        <div class="price-display__skeleton-amount"></div>
        <div v-if="showOriginal" class="price-display__skeleton-original"></div>
      </div>
      <div v-if="showLoadingText" class="price-display__loading-text">
        {{ loadingText }}
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="hasError" class="price-display__error">
      <div class="price-display__error-content">
        <div class="price-display__error-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
        </div>
        <div class="price-display__error-text">
          <div class="price-display__error-title">{{ errorTitle }}</div>
          <div v-if="showErrorMessage" class="price-display__error-message">{{ errorMessage }}</div>
        </div>
      </div>
      <div v-if="showRetryButton && retryable" class="price-display__error-actions">
        <button
          @click="retryConversion"
          class="price-display__retry-button"
          :disabled="isRetrying"
        >
          <svg v-if="!isRetrying" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"></polyline>
            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path>
          </svg>
          <svg v-else class="animate-spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 1 1-6.219-8.56"></path>
          </svg>
          {{ retryButtonText }}
        </button>
      </div>
      <!-- Fallback display -->
      <div v-if="showFallback" class="price-display__fallback">
        <span class="price-display__fallback-symbol">{{ fallbackSymbol }}</span>
        <span class="price-display__fallback-amount">{{ formatFallbackAmount(originalAmount) }}</span>
      </div>
    </div>

    <!-- Success State -->
    <div v-else-if="convertedPrice !== null" class="price-display__content">
      <div class="price-display__main">
        <span class="price-display__symbol">{{ currencySymbol }}</span>
        <span class="price-display__amount">{{ formattedAmount }}</span>
      </div>

      <!-- Original price (if showing conversion) -->
      <div v-if="showOriginal && originalAmount !== null && originalCurrency !== currentCurrency" class="price-display__original">
        <span class="price-display__original-amount">{{ formatOriginalAmount(originalAmount) }}</span>
        <span class="price-display__original-currency">{{ originalCurrency }}</span>
      </div>

      <!-- Conversion info -->
      <div v-if="showConversionInfo && conversionInfo" class="price-display__info">
        <span class="price-display__conversion-rate">
          1 {{ originalCurrency }} = {{ formatRate(conversionInfo.rate) }} {{ currentCurrency }}
        </span>
        <span v-if="showTimestamp && conversionInfo.timestamp" class="price-display__timestamp">
          {{ formatTimestamp(conversionInfo.timestamp) }}
        </span>
      </div>

      <!-- Currency indicator -->
      <div v-if="showCurrencyIndicator" class="price-display__currency-indicator">
        <span class="price-display__currency-code">{{ currentCurrency }}</span>
        <span v-if="conversionMethod" class="price-display__conversion-method">
          {{ getConversionMethodLabel(conversionMethod) }}
        </span>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="price-display__empty">
      <div class="price-display__empty-text">{{ emptyText }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, watch, onErrorCaptured } from 'vue'
import { useCurrency } from '@/composables/useCurrency'
import { useExchangeRates } from '@/composables/useExchangeRates'
import { useToast } from 'vue-toastification'

// Props
const props = defineProps({
  amount: {
    type: [Number, String],
    required: true,
    validator: (value) => !isNaN(parseFloat(value))
  },
  fromCurrency: {
    type: String,
    required: true
  },
  toCurrency: {
    type: String,
    default: null
  },
  showOriginal: {
    type: Boolean,
    default: false
  },
  showConversionInfo: {
    type: Boolean,
    default: false
  },
  showTimestamp: {
    type: Boolean,
    default: false
  },
  showCurrencyIndicator: {
    type: Boolean,
    default: false
  },
  compact: {
    type: Boolean,
    default: false
  },
  large: {
    type: Boolean,
    default: false
  },
  loadingText: {
    type: String,
    default: 'Converting...'
  },
  errorTitle: {
    type: String,
    default: 'Conversion Error'
  },
  errorMessage: {
    type: String,
    default: ''
  },
  showErrorMessage: {
    type: Boolean,
    default: false
  },
  showRetryButton: {
    type: Boolean,
    default: true
  },
  retryable: {
    type: Boolean,
    default: true
  },
  showFallback: {
    type: Boolean,
    default: true
  },
  showLoadingText: {
    type: Boolean,
    default: false
  },
  emptyText: {
    type: String,
    default: 'Price not available'
  },
  autoConvert: {
    type: Boolean,
    default: true
  },
  refreshInterval: {
    type: Number,
    default: 0 // 0 means no auto refresh
  },
  precision: {
    type: Number,
    default: 2,
    validator: (value) => value >= 0 && value <= 10
  }
})

// Emits
const emit = defineEmits([
  'conversion-start',
  'conversion-success',
  'conversion-error',
  'retry-attempt'
])

// Composables
const {
  currentCurrency,
  getCurrencySymbol,
  isLoading: isCurrencyLoading,
  error: currencyError
} = useCurrency(props.toCurrency)

const {
  convertCurrency,
  isLoading: isConversionLoading,
  error: conversionError
} = useExchangeRates()

// Reactive state
const convertedPrice = ref(null)
const conversionInfo = ref(null)
const isRetrying = ref(false)
const lastConversionTime = ref(null)
const conversionAttempts = ref(0)

// Computed properties
const targetCurrency = computed(() => props.toCurrency || currentCurrency.value)

const isLoading = computed(() =>
  isCurrencyLoading.value || isConversionLoading.value || (props.autoConvert && convertedPrice.value === null && !hasError.value)
)

const hasError = computed(() =>
  !!currencyError.value || !!conversionError.value || conversionAttempts.value >= 3
)

const currencySymbol = computed(() => getCurrencySymbol(targetCurrency.value))

const formattedAmount = computed(() => {
  if (convertedPrice.value === null) return ''
  return formatCurrency(convertedPrice.value, targetCurrency.value)
})

const originalAmount = computed(() => parseFloat(props.amount))

const fallbackSymbol = computed(() => getCurrencySymbol(props.fromCurrency))

// Format currency amount
const formatCurrency = (amount, currencyCode) => {
  try {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: props.precision,
      maximumFractionDigits: props.precision
    }).format(amount)
  } catch (error) {
    console.warn('Currency formatting failed:', error.message)
    return amount.toFixed(props.precision)
  }
}

// Format original amount
const formatOriginalAmount = (amount) => {
  return formatCurrency(amount, props.fromCurrency)
}

// Format fallback amount
const formatFallbackAmount = (amount) => {
  return formatCurrency(amount, props.fromCurrency)
}

// Format exchange rate
const formatRate = (rate) => {
  return formatCurrency(rate, targetCurrency.value)
}

// Format timestamp
const formatTimestamp = (timestamp) => {
  try {
    const date = new Date(timestamp)
    return date.toLocaleTimeString()
  } catch (error) {
    return ''
  }
}

// Get conversion method label
const getConversionMethodLabel = (method) => {
  const labels = {
    'cache': 'Cached',
    'api': 'Live',
    'fallback': 'Estimate',
    'same_currency': 'Local'
  }
  return labels[method] || method
}

// Perform currency conversion
const performConversion = async (isRetry = false) => {
  if (!props.autoConvert) return

  if (isRetry) {
    isRetrying.value = true
    emit('retry-attempt', { attempt: conversionAttempts.value + 1 })
  }

  emit('conversion-start', {
    amount: originalAmount.value,
    from: props.fromCurrency,
    to: targetCurrency.value,
    isRetry
  })

  try {
    conversionAttempts.value++

    const result = await convertCurrency(
      originalAmount.value,
      props.fromCurrency,
      targetCurrency.value
    )

    if (result.success) {
      convertedPrice.value = result.convertedAmount
      conversionInfo.value = {
        rate: result.rate,
        timestamp: result.timestamp,
        method: result.conversionMethod
      }
      lastConversionTime.value = new Date()
      conversionAttempts.value = 0

      emit('conversion-success', {
        originalAmount: result.originalAmount,
        convertedAmount: result.convertedAmount,
        fromCurrency: result.fromCurrency,
        toCurrency: result.toCurrency,
        rate: result.rate,
        method: result.conversionMethod
      })
    } else {
      throw new Error(result.error || 'Conversion failed')
    }

  } catch (error) {
    console.error('Price conversion failed:', error)

    // Show error toast
    const toast = useToast()
    toast.error('Failed to convert price', {
      timeout: 3000,
      closeOnClick: true
    })

    emit('conversion-error', {
      error: error.message,
      amount: originalAmount.value,
      from: props.fromCurrency,
      to: targetCurrency.value,
      attempts: conversionAttempts.value
    })

  } finally {
    isRetrying.value = false
  }
}

// Retry conversion
const retryConversion = () => {
  performConversion(true)
}

// Auto refresh
let refreshInterval = null

const startAutoRefresh = () => {
  if (refreshInterval || props.refreshInterval <= 0) return

  refreshInterval = setInterval(() => {
    if (props.autoConvert && !hasError.value) {
      performConversion()
    }
  }, props.refreshInterval)
}

const stopAutoRefresh = () => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
}

// Watch for changes
watch([() => props.amount, () => props.fromCurrency, targetCurrency], () => {
  if (props.autoConvert) {
    conversionAttempts.value = 0
    performConversion()
  }
}, { immediate: true })

watch(() => props.refreshInterval, (newInterval) => {
  stopAutoRefresh()
  if (newInterval > 0) {
    startAutoRefresh()
  }
})

// Error boundary
onErrorCaptured((error, instance, info) => {
  console.error('PriceDisplay error boundary:', { error, instance, info })

  const toast = useToast()
  toast.error('Price display encountered an error', {
    timeout: 3000,
    closeOnClick: true
  })

  return false
})

// Cleanup on unmount
import { onUnmounted } from 'vue'

onUnmounted(() => {
  stopAutoRefresh()
})

// Initialize
if (props.refreshInterval > 0) {
  startAutoRefresh()
}
</script>

<style scoped>
.price-display {
  @apply inline-flex flex-col items-start;
}

.price-display--compact {
  @apply flex-row items-center gap-1;
}

.price-display--large {
  @apply items-start gap-2;
}

/* Loading State */
.price-display__loading {
  @apply flex flex-col items-center gap-2;
}

.price-display--compact .price-display__loading {
  @apply flex-row gap-2;
}

.price-display__skeleton {
  @apply flex items-center gap-1;
}

.price-display__skeleton-symbol {
  @apply w-4 h-4 bg-gray-200 rounded animate-pulse;
}

.price-display__skeleton-amount {
  @apply w-16 h-6 bg-gray-200 rounded animate-pulse;
}

.price-display__skeleton-original {
  @apply w-12 h-4 bg-gray-100 rounded animate-pulse mt-1;
}

.price-display__loading-text {
  @apply text-xs text-gray-500;
}

/* Error State */
.price-display__error {
  @apply flex flex-col items-center gap-2 text-red-600;
}

.price-display--compact .price-display__error {
  @apply flex-row;
}

.price-display__error-content {
  @apply flex items-center gap-2;
}

.price-display__error-icon {
  @apply flex-shrink-0;
}

.price-display__error-text {
  @apply flex flex-col;
}

.price-display__error-title {
  @apply font-medium text-sm;
}

.price-display__error-message {
  @apply text-xs text-red-500;
}

.price-display__error-actions {
  @apply mt-1;
}

.price-display__retry-button {
  @apply flex items-center gap-1 px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors disabled:opacity-50;
}

.price-display__fallback {
  @apply flex items-center gap-1 text-sm text-gray-600 mt-1;
}

.price-display__fallback-symbol {
  @apply font-medium;
}

.price-display__fallback-amount {
  @apply font-semibold;
}

/* Success State */
.price-display__content {
  @apply flex flex-col items-start gap-1;
}

.price-display--compact .price-display__content {
  @apply flex-row items-center gap-1;
}

.price-display__main {
  @apply flex items-baseline gap-1;
}

.price-display__symbol {
  @apply font-medium text-sm;
}

.price-display__amount {
  @apply font-bold;
}

.price-display--large .price-display__amount {
  @apply text-2xl;
}

.price-display__original {
  @apply flex items-baseline gap-1 text-xs text-gray-500 line-through;
}

.price-display__original-currency {
  @apply font-medium;
}

.price-display__info {
  @apply flex flex-col items-start gap-1 text-xs text-gray-400;
}

.price-display__conversion-rate {
  @apply text-xs text-gray-400;
}

.price-display__timestamp {
  @apply text-xs text-gray-400;
}

.price-display__currency-indicator {
  @apply flex items-center gap-1 text-xs text-gray-500;
}

.price-display__currency-code {
  @apply font-medium;
}

.price-display__conversion-method {
  @apply text-gray-400;
}

/* Empty State */
.price-display__empty {
  @apply text-gray-400 text-sm;
}

/* Responsive */
@media (max-width: 768px) {
  .price-display__loading-text,
  .price-display__error-message,
  .price-display__conversion-info {
    @apply hidden;
  }

  .price-display--large .price-display__amount {
    @apply text-lg;
  }
}

/* Animation */
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>