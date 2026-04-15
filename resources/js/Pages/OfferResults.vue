<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'
import Footer from '@/Components/Footer.vue'
import { Toaster } from '@/Components/ui/sonner'
import BookingExtrasStep from '@/Components/BookingExtras/BookingExtrasStep.vue'
import BookingCheckoutStep from '@/Components/BookingCheckoutStep.vue'
import OfferCompactMap from '@/Components/Skyscanner/OfferCompactMap.vue'
import { useCurrencyConversion } from '@/composables/useCurrencyConversion'
import { resolveOfferMapDetails } from '@/features/skyscanner/utils/offerMapDetails'

type Luggage = {
  small?: number | null
  medium?: number | null
  large?: number | null
}

type Vehicle = {
  provider_vehicle_id?: string | null
  display_name?: string | null
  brand?: string | null
  model?: string | null
  category?: string | null
  image_url?: string | null
  supplier_name?: string | null
  supplier_code?: string | null
  sipp_code?: string | null
  transmission?: string | null
  fuel_type?: string | null
  air_conditioning?: boolean | null
  seats?: number | null
  doors?: number | null
  luggage?: Luggage | null
}

type Supplier = {
  code?: string | null
  name?: string | null
}

type Pricing = {
  currency?: string | null
  total_price?: number | null
  price_per_day?: number | null
  deposit_amount?: number | null
  deposit_currency?: string | null
}

type CancellationPolicy = {
  available?: boolean | null
  days_before_pickup?: number | null
}

type Policies = {
  mileage_policy?: string | null
  mileage_limit_km?: number | null
  fuel_policy?: string | null
  cancellation?: CancellationPolicy | null
}

type LocationDetails = {
  provider_location_id?: string | null
  name?: string | null
  address?: string | null
  city?: string | null
  state?: string | null
  country?: string | null
  country_code?: string | null
  location_type?: string | null
  iata?: string | null
  phone?: string | null
  pickup_instructions?: string | null
  dropoff_instructions?: string | null
  latitude?: number | null
  longitude?: number | null
}

type Search = {
  pickup_date?: string | null
  pickup_time?: string | null
  dropoff_date?: string | null
  dropoff_time?: string | null
  driver_age?: string | number | null
  currency?: string | null
}

type Product = {
  type?: string | null
  name?: string | null
  subtitle?: string | null
  total?: number | null
  price_per_day?: number | null
  deposit?: number | null
  deposit_currency?: string | null
  is_basic?: boolean | null
}

type Quote = {
  quote_id: string
  case_id?: string | null
  vehicle?: Vehicle
  supplier?: Supplier
  pricing?: Pricing
  policies?: Policies
  pickup_location_details?: LocationDetails
  dropoff_location_details?: LocationDetails
  search?: Search
  products?: Product[]
}

type OfferResults = {
  selected_quote_id?: string | null
  search?: Search
  quotes?: Quote[]
}

type BookingContext = {
  vehicle: Record<string, unknown>
  initial_package?: string | null
  initial_protection_code?: string | null
  optional_extras?: Array<Record<string, unknown>>
  location_name?: string | null
  pickup_location?: string | null
  dropoff_location?: string | null
  dropoff_latitude?: string | number | null
  dropoff_longitude?: string | number | null
  pickup_date?: string | null
  pickup_time?: string | null
  dropoff_date?: string | null
  dropoff_time?: string | null
  number_of_days?: number | null
  location_instructions?: string | null
  location_details?: Record<string, unknown> | null
  driver_requirements?: Record<string, unknown> | null
  terms?: Array<Record<string, unknown>> | null
  payment_percentage?: number | null
}

type CheckoutData = {
  package: string
  protection_code?: string | string[] | null
  protection_amount?: number | null
  extras: Record<string, number>
  detailedExtras: Array<Record<string, unknown>>
  totals: {
    grandTotal: string | number
    payableAmount: string | number
    pendingAmount: string | number
  }
  totals_currency?: string | null
  vehicle_total?: string | number | null
  vehicle_total_currency?: string | null
  selected_deposit_type?: string | null
}

type QuoteStatus = {
  valid?: boolean
  expired?: boolean
  reason?: string | null
  message?: string | null
  search_again_url?: string | null
}

const props = defineProps<{
  quote: Quote
  offerResults: OfferResults
  bookingContext: BookingContext | null
  bookingContexts: Record<string, BookingContext>
  quoteStatus?: QuoteStatus
}>()

const bookingStep = ref<'results' | 'extras' | 'checkout'>('results')
const activeBookingContext = ref<BookingContext | null>(null)
const selectedPackage = ref('BAS')
const selectedProtectionCode = ref<string | null>(null)
const selectedCheckoutData = ref<CheckoutData | null>(null)
const { selectedCurrency, convertPrice, fetchExchangeRates, getCurrencySymbol } = useCurrencyConversion()

const vehicle = computed(() => props.quote.vehicle ?? {})
const pricing = computed(() => props.quote.pricing ?? {})
const policies = computed(() => props.quote.policies ?? {})
const pickupLocation = computed(() => props.quote.pickup_location_details ?? {})
const dropoffLocation = computed(() => props.quote.dropoff_location_details ?? {})
const search = computed(() => props.quote.search ?? {})
const featuredProduct = computed(() => props.quote.products?.[0] ?? null)
const displayedQuotes = computed(() => props.offerResults.quotes ?? [])
const alternativeQuotes = computed(() => displayedQuotes.value.filter((offer) => offer.quote_id !== props.quote.quote_id))
const quoteStatus = computed(() => props.quoteStatus ?? {})
const isExpired = computed(() => quoteStatus.value.expired === true)
const hasCompactMap = computed(() => resolveOfferMapDetails(pickupLocation.value, dropoffLocation.value).hasMap)

const currentBookingContext = computed(() => activeBookingContext.value ?? props.bookingContext)
const selectedVehicle = computed<Record<string, unknown> | null>(() => currentBookingContext.value?.vehicle ?? null)
const selectedOptionalExtras = computed(() => currentBookingContext.value?.optional_extras ?? [])
const bookingCurrencyCode = computed(() => {
  const vehiclePricing = selectedVehicle.value?.pricing as Record<string, unknown> | undefined
  return `${vehiclePricing?.currency ?? pricing.value.currency ?? search.value.currency ?? 'EUR'}`
})
const bookingCurrencySymbol = computed(() => resolveCurrencySymbol(bookingCurrencyCode.value))
const displayCurrencyCode = computed(() => `${selectedCurrency.value ?? pricing.value.currency ?? search.value.currency ?? 'EUR'}`)

const displayName = computed(() => vehicle.value.display_name ?? [vehicle.value.brand, vehicle.value.model].filter(Boolean).join(' '))
const pageTitle = computed(() => `${displayName.value || 'Selected Offer'} | Vrooem`)

const luggageSummary = computed(() => {
  const luggage = vehicle.value.luggage ?? {}
  const parts = [
    luggage.small != null ? `${luggage.small} small` : null,
    luggage.medium != null ? `${luggage.medium} medium` : null,
    luggage.large != null ? `${luggage.large} large` : null,
  ].filter(Boolean)

  return parts.length > 0 ? parts.join(' / ') : 'Not specified'
})

const formatAmount = (amount?: number | null, currency?: string | null) => {
  if (amount == null) {
    return 'Not available'
  }

  const safeCurrency = currency || 'EUR'

  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: safeCurrency,
      maximumFractionDigits: 2,
    }).format(amount)
  } catch {
    return `${safeCurrency} ${amount}`
  }
}

const formatDisplayAmount = (amount?: number | null, sourceCurrency?: string | null) => {
  if (amount == null) {
    return 'Not available'
  }

  const normalizedSource = `${sourceCurrency || pricing.value.currency || search.value.currency || 'EUR'}`
  const normalizedDisplay = displayCurrencyCode.value || normalizedSource
  const convertedAmount = convertPrice(amount, normalizedSource)

  try {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: normalizedDisplay,
      maximumFractionDigits: 2,
    }).format(convertedAmount)
  } catch {
    return `${getCurrencySymbol(normalizedDisplay)}${convertedAmount.toFixed(2)}`
  }
}

const formatDateTime = (date?: string | null, time?: string | null) => {
  if (!date) {
    return 'Not specified'
  }

  return time ? `${date} at ${time}` : date
}

const formatLocationMeta = (details: LocationDetails) => {
  const parts = [details.location_type, details.iata, details.country_code].filter(Boolean)
  return parts.length > 0 ? parts.join(' • ') : 'Location details available'
}

const quotePrice = (quote: Quote) => formatDisplayAmount(quote.pricing?.total_price, quote.pricing?.currency || props.offerResults.search?.currency)

const resolveCurrencySymbol = (currency: string) => {
  const symbolMap: Record<string, string> = {
    EUR: '€',
    USD: '$',
    GBP: '£',
    AED: 'د.إ',
    MAD: 'د.م',
    INR: '₹',
  }

  return symbolMap[currency] ?? currency
}

const startBooking = (quoteId: string) => {
  if (isExpired.value) {
    return
  }

  const context = props.bookingContexts?.[quoteId]

  if (!context) {
    return
  }

  activeBookingContext.value = context
  selectedPackage.value = context.initial_package ?? 'BAS'
  selectedProtectionCode.value = context.initial_protection_code ?? null
  selectedCheckoutData.value = null
  bookingStep.value = 'extras'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleBackToResults = () => {
  bookingStep.value = 'results'
  activeBookingContext.value = null
  selectedCheckoutData.value = null
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleProceedToCheckout = (data: CheckoutData) => {
  selectedCheckoutData.value = data
  bookingStep.value = 'checkout'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleBackToExtras = () => {
  bookingStep.value = 'extras'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const canBookQuote = (quoteId: string) => !isExpired.value && Boolean(props.bookingContexts?.[quoteId])

onMounted(() => {
  fetchExchangeRates()
})
</script>

<template>
  <Head :title="pageTitle" />
  <AuthenticatedHeaderLayout />
  <Toaster class="pointer-events-auto" />

  <section v-if="bookingStep !== 'results'" class="or-stepper-bar">
    <div class="full-w-container">
      <div class="flex items-center">
        <div class="flex items-center gap-2.5 cursor-pointer group" @click="handleBackToResults">
          <div class="or-step-dot or-step-done">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div class="hidden sm:block">
            <span class="or-step-label text-[#153b4f] group-hover:underline">Offer</span>
            <span class="or-step-sub">Selected deal</span>
          </div>
        </div>

        <div class="or-step-line flex-1 mx-3"><div class="or-step-line-fill" style="width:100%"></div></div>

        <div class="flex items-center gap-2.5" :class="bookingStep === 'checkout' ? 'cursor-pointer' : ''" @click="bookingStep === 'checkout' ? handleBackToExtras() : null">
          <div class="or-step-dot" :class="bookingStep === 'checkout' ? 'or-step-done' : bookingStep === 'extras' ? 'or-step-active' : 'or-step-pending'">
            <svg v-if="bookingStep === 'checkout'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V10M18 20V4M6 20v-6"/></svg>
          </div>
          <div class="hidden sm:block">
            <span class="or-step-label" :class="bookingStep === 'extras' || bookingStep === 'checkout' ? 'text-[#153b4f]' : 'text-gray-400'">Customize</span>
            <span class="or-step-sub">Extras & options</span>
          </div>
        </div>

        <div class="or-step-line flex-1 mx-3"><div class="or-step-line-fill" :style="{ width: bookingStep === 'checkout' ? '100%' : '0%' }"></div></div>

        <div class="flex items-center gap-2.5">
          <div class="or-step-dot" :class="bookingStep === 'checkout' ? 'or-step-active' : 'or-step-pending'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          </div>
          <div class="hidden sm:block">
            <span class="or-step-label" :class="bookingStep === 'checkout' ? 'text-[#153b4f]' : 'text-gray-400'">Checkout</span>
            <span class="or-step-sub">Secure payment</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section v-if="bookingStep === 'results'" class="or-hero">
    <div class="full-w-container or-hero-inner">
      <nav class="or-breadcrumb">
        <Link href="/">Home</Link>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span>Search results</span>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
        <span class="or-breadcrumb-current">Selected offer</span>
      </nav>
      <div class="or-hero-grid">
        <div class="or-hero-title">
          <span class="or-eyebrow">Your offer</span>
          <h1>{{ pickupLocation.name || 'Selected car rental' }}</h1>
          <p>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <span>{{ [pickupLocation.city, pickupLocation.country].filter(Boolean).join(', ') || 'Pickup location' }}</span>
            <span class="or-hero-sep">•</span>
            <span>{{ displayedQuotes.length }} {{ displayedQuotes.length === 1 ? 'offer' : 'offers' }} available</span>
          </p>
        </div>
        <div class="or-hero-dates">
          <div class="or-date-card">
            <span class="or-date-label">Pickup</span>
            <span class="or-date-value">{{ search.pickup_date || '—' }}</span>
            <span class="or-date-time">{{ search.pickup_time || '' }}</span>
          </div>
          <div class="or-date-arrow" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </div>
          <div class="or-date-card">
            <span class="or-date-label">Return</span>
            <span class="or-date-value">{{ search.dropoff_date || '—' }}</span>
            <span class="or-date-time">{{ search.dropoff_time || '' }}</span>
          </div>
          <div class="or-days-pill">{{ currentBookingContext?.number_of_days || 1 }} days</div>
        </div>
      </div>
    </div>
  </section>

  <div class="or-page">
    <section v-if="bookingStep === 'results'" class="or-body">
      <div class="full-w-container or-body-inner">
        <div v-if="isExpired" class="or-alert">
          <div class="or-alert-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="or-alert-body">
            <p class="or-alert-eyebrow">Offer expired</p>
            <p class="or-alert-message">{{ quoteStatus.message || 'This offer has expired. Run the search again to see current prices and availability.' }}</p>
          </div>
          <Link v-if="quoteStatus.search_again_url" :href="quoteStatus.search_again_url" class="or-alert-btn">
            Search again
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </Link>
        </div>

        <div class="or-grid">
          <div class="or-col-main">
            <article class="or-vehicle">
              <div class="or-vehicle-media" :class="{ 'or-vehicle-media-split': hasCompactMap }">
                <div class="or-vehicle-media-image">
                  <img v-if="vehicle.image_url" :src="vehicle.image_url" :alt="displayName || 'Vehicle image'" />
                  <div v-else class="or-vehicle-empty">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H5.24a2 2 0 00-1.8 1.1l-.8 1.63A6 6 0 002 12.42V16h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                    <span>No image available</span>
                  </div>
                </div>
                <div v-if="hasCompactMap" class="or-vehicle-media-map">
                  <OfferCompactMap :pickup-location="pickupLocation" :dropoff-location="dropoffLocation" />
                </div>
                <div class="or-vehicle-badges">
                  <span class="or-badge or-badge-selected">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                    Selected offer
                  </span>
                  <span v-if="vehicle.category" class="or-badge or-badge-muted">{{ vehicle.category }}</span>
                </div>
              </div>
              <div class="or-vehicle-body">
                <div class="or-vehicle-head">
                  <div class="or-vehicle-title">
                    <h2>{{ displayName || 'Vehicle offer' }}</h2>
                    <p>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                      {{ quote.supplier?.name || vehicle.supplier_name || 'Supplier' }}
                    </p>
                  </div>
                  <div class="or-vehicle-price">
                    <span class="or-price-eyebrow">Total from</span>
                    <span class="or-price-value">{{ formatDisplayAmount(pricing.total_price, pricing.currency || search.currency) }}</span>
                    <span class="or-price-sub">{{ formatDisplayAmount(pricing.price_per_day, pricing.currency || search.currency) }} / day</span>
                  </div>
                </div>

                <div class="or-spec-grid">
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-fuel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 012-2h8a2 2 0 012 2v18M3 22h12M6 7h6M18 8l3-3v11a2 2 0 01-4 0V8a2 2 0 012-2"/></svg></div>
                    <div class="or-spec-text"><span>Fuel</span><strong>{{ vehicle.fuel_type || '—' }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-ac"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93L4.93 19.07"/></svg></div>
                    <div class="or-spec-text"><span>AC</span><strong>{{ vehicle.air_conditioning ? 'Included' : 'No' }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-seat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2M17 11a4 4 0 100-8"/></svg></div>
                    <div class="or-spec-text"><span>Seats</span><strong>{{ vehicle.seats ?? 'N/A' }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-door"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16M5 20V6a2 2 0 012-2h10a2 2 0 012 2v14M10 12h.01"/></svg></div>
                    <div class="or-spec-text"><span>Doors</span><strong>{{ vehicle.doors ?? 'N/A' }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-trans"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><path d="M6 8v8M6 8h12a2 2 0 012 2v6"/></svg></div>
                    <div class="or-spec-text"><span>Transmission</span><strong>{{ vehicle.transmission || '—' }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-lug"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="7" width="14" height="13" rx="2"/><path d="M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M9 12v5M15 12v5"/></svg></div>
                    <div class="or-spec-text"><span>Luggage</span><strong>{{ luggageSummary }}</strong></div>
                  </div>
                </div>

                <div class="or-vehicle-foot">
                  <button type="button" class="or-btn-primary" :disabled="!canBookQuote(quote.quote_id)" @click="startBooking(quote.quote_id)">
                    <span>{{ isExpired ? 'Offer expired' : 'Continue to booking' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                  </button>
                </div>
              </div>
            </article>

            <article class="or-panel">
              <header class="or-panel-head">
                <div class="or-panel-icon or-icon-blue">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                </div>
                <div class="or-panel-title">
                  <h3>Trip details</h3>
                  <p>Your search criteria</p>
                </div>
              </header>
              <div class="or-trip-grid">
                <div class="or-trip-item">
                  <span class="or-label">Pickup</span>
                  <strong class="or-value">{{ formatDateTime(search.pickup_date, search.pickup_time) }}</strong>
                </div>
                <div class="or-trip-item">
                  <span class="or-label">Drop-off</span>
                  <strong class="or-value">{{ formatDateTime(search.dropoff_date, search.dropoff_time) }}</strong>
                </div>
                <div class="or-trip-item">
                  <span class="or-label">Driver age</span>
                  <strong class="or-value">{{ search.driver_age || 'N/A' }}</strong>
                </div>
              </div>
            </article>

            <div class="or-offices">
              <article class="or-panel">
                <header class="or-panel-head">
                  <div class="or-panel-icon or-icon-green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  </div>
                  <div class="or-panel-title">
                    <h3>Pickup office</h3>
                    <p>{{ formatLocationMeta(pickupLocation) }}</p>
                  </div>
                </header>
                <div class="or-office">
                  <p class="or-office-name">{{ pickupLocation.name || 'Not specified' }}</p>
                  <p class="or-office-addr">{{ pickupLocation.address || 'Address not specified' }}</p>
                  <p v-if="pickupLocation.phone" class="or-office-phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                    {{ pickupLocation.phone }}
                  </p>
                  <p v-if="pickupLocation.pickup_instructions" class="or-office-note">{{ pickupLocation.pickup_instructions }}</p>
                </div>
              </article>

              <article class="or-panel">
                <header class="or-panel-head">
                  <div class="or-panel-icon or-icon-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg>
                  </div>
                  <div class="or-panel-title">
                    <h3>Drop-off office</h3>
                    <p>{{ formatLocationMeta(dropoffLocation) }}</p>
                  </div>
                </header>
                <div class="or-office">
                  <p class="or-office-name">{{ dropoffLocation.name || 'Not specified' }}</p>
                  <p class="or-office-addr">{{ dropoffLocation.address || 'Address not specified' }}</p>
                  <p v-if="dropoffLocation.phone" class="or-office-phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                    {{ dropoffLocation.phone }}
                  </p>
                  <p v-if="dropoffLocation.dropoff_instructions" class="or-office-note">{{ dropoffLocation.dropoff_instructions }}</p>
                </div>
              </article>
            </div>

            <article class="or-panel">
              <header class="or-panel-head">
                <div class="or-panel-icon or-icon-cyan">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>
                </div>
                <div class="or-panel-title flex-1">
                  <h3>Other available offers</h3>
                  <p>Same search, alternative rates</p>
                </div>
                <span class="or-count">{{ alternativeQuotes.length }}</span>
              </header>
              <div v-if="alternativeQuotes.length > 0" class="or-alts">
                <article v-for="offer in alternativeQuotes" :key="offer.quote_id" class="or-alt">
                  <div class="or-alt-media">
                    <img v-if="offer.vehicle?.image_url" :src="offer.vehicle.image_url" :alt="offer.vehicle?.display_name || 'Vehicle'" />
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H5.24a2 2 0 00-1.8 1.1l-.8 1.63A6 6 0 002 12.42V16h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                  </div>
                  <div class="or-alt-info">
                    <h4>{{ offer.vehicle?.display_name || 'Vehicle offer' }}</h4>
                    <p>
                      <span v-if="offer.vehicle?.sipp_code">{{ offer.vehicle.sipp_code }}</span>
                      <span v-if="offer.vehicle?.transmission"> · {{ offer.vehicle.transmission }}</span>
                      <span v-if="offer.vehicle?.fuel_type"> · {{ offer.vehicle.fuel_type }}</span>
                    </p>
                  </div>
                  <div class="or-alt-price">
                    <strong>{{ quotePrice(offer) }}</strong>
                    <span>Total</span>
                  </div>
                  <button type="button" class="or-btn-ghost" :disabled="!canBookQuote(offer.quote_id)" @click="startBooking(offer.quote_id)">
                    {{ isExpired ? 'Expired' : 'Book' }}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                  </button>
                </article>
              </div>
              <p v-else class="or-empty">No alternative offers were returned for this search.</p>
            </article>
          </div>

          <aside class="or-col-side">
            <div class="or-summary">
              <div class="or-summary-head">
                <span class="or-summary-eyebrow">Total rental price</span>
                <div class="or-summary-total">{{ formatDisplayAmount(pricing.total_price, pricing.currency || search.currency) }}</div>
                <p class="or-summary-days">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  For {{ currentBookingContext?.number_of_days || 1 }} days
                </p>
              </div>

              <div class="or-summary-rows">
                <div class="or-summary-row">
                  <span>Per day</span>
                  <strong>{{ formatDisplayAmount(pricing.price_per_day, pricing.currency || search.currency) }}</strong>
                </div>
                <div class="or-summary-row">
                  <span>Deposit</span>
                  <strong>{{ formatDisplayAmount(pricing.deposit_amount, pricing.deposit_currency || pricing.currency || search.currency) }}</strong>
                </div>
                <div class="or-summary-row or-summary-row-stack">
                  <span>Mileage</span>
                  <strong>
                    {{ policies.mileage_policy || 'Not specified' }}
                    <template v-if="policies.mileage_limit_km != null"> ({{ policies.mileage_limit_km }} km)</template>
                  </strong>
                </div>
                <div class="or-summary-row or-summary-row-stack">
                  <span>Fuel policy</span>
                  <strong>{{ policies.fuel_policy || 'Not specified' }}</strong>
                </div>
              </div>

              <div v-if="featuredProduct" class="or-product">
                <span class="or-product-eyebrow">Selected product</span>
                <p class="or-product-name">{{ featuredProduct.name || 'Standard offer' }}</p>
                <p v-if="featuredProduct.subtitle" class="or-product-sub">{{ featuredProduct.subtitle }}</p>
              </div>

              <button type="button" class="or-btn-primary or-btn-block" :disabled="!canBookQuote(quote.quote_id)" @click="startBooking(quote.quote_id)">
                <span>{{ isExpired ? 'Offer expired' : 'Continue to booking' }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
              </button>

              <p class="or-summary-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Secure checkout · Instant confirmation
              </p>
            </div>
          </aside>
        </div>
      </div>
    </section>

    <BookingExtrasStep
      v-else-if="bookingStep === 'extras' && selectedVehicle"
      class="full-w-container"
      :vehicle="selectedVehicle"
      :initial-package="selectedPackage"
      :initial-protection-code="selectedProtectionCode"
      :optional-extras="selectedOptionalExtras"
      :currency-symbol="bookingCurrencySymbol"
      :location-name="currentBookingContext.location_name || currentBookingContext.pickup_location || ''"
      :pickup-location="currentBookingContext.pickup_location || ''"
      :dropoff-location="currentBookingContext.dropoff_location || currentBookingContext.pickup_location || ''"
      :dropoff-latitude="currentBookingContext.dropoff_latitude ?? null"
      :dropoff-longitude="currentBookingContext.dropoff_longitude ?? null"
      :pickup-date="currentBookingContext.pickup_date || ''"
      :pickup-time="currentBookingContext.pickup_time || ''"
      :dropoff-date="currentBookingContext.dropoff_date || ''"
      :dropoff-time="currentBookingContext.dropoff_time || ''"
      :number-of-days="currentBookingContext.number_of_days || 1"
      :location-instructions="currentBookingContext.location_instructions || null"
      :location-details="currentBookingContext.location_details || null"
      :driver-requirements="currentBookingContext.driver_requirements || null"
      :terms="currentBookingContext.terms || null"
      :payment-percentage="currentBookingContext.payment_percentage || 15"
      @back="handleBackToResults"
      @proceed-to-checkout="handleProceedToCheckout"
    />

    <BookingCheckoutStep
      v-else-if="bookingStep === 'checkout' && selectedVehicle && selectedCheckoutData"
      class="full-w-container"
      :vehicle="selectedVehicle"
      :package="selectedCheckoutData.package"
      :protection-code="selectedCheckoutData.protection_code ?? null"
      :protection-amount="selectedCheckoutData.protection_amount ?? 0"
      :extras="selectedCheckoutData.extras"
      :detailed-extras="selectedCheckoutData.detailedExtras"
      :optional-extras="selectedOptionalExtras"
      :pickup-date="currentBookingContext.pickup_date || ''"
      :pickup-time="currentBookingContext.pickup_time || ''"
      :dropoff-date="currentBookingContext.dropoff_date || ''"
      :dropoff-time="currentBookingContext.dropoff_time || ''"
      :pickup-location="currentBookingContext.pickup_location || ''"
      :dropoff-location="currentBookingContext.dropoff_location || currentBookingContext.pickup_location || ''"
      :number-of-days="currentBookingContext.number_of_days || 1"
      :currency-symbol="bookingCurrencySymbol"
      :selected-currency-code="bookingCurrencyCode"
      :payment-percentage="currentBookingContext.payment_percentage || 15"
      :totals="selectedCheckoutData.totals"
      :totals-currency="selectedCheckoutData.totals_currency || bookingCurrencyCode"
      :vehicle-total="selectedCheckoutData.vehicle_total ?? 0"
      :vehicle-total-currency="selectedCheckoutData.vehicle_total_currency || bookingCurrencyCode"
      :location-details="currentBookingContext.location_details || null"
      :location-instructions="currentBookingContext.location_instructions || null"
      :driver-requirements="currentBookingContext.driver_requirements || null"
      :terms="currentBookingContext.terms || null"
      :selected-deposit-type="selectedCheckoutData.selected_deposit_type || null"
      @back="handleBackToExtras"
    />
  </div>

  <Footer />
</template>

<style scoped>
.or-page {
  --or-ease: cubic-bezier(0.22, 1, 0.36, 1);
  --or-dur: 0.3s;
  --or-brand: #153b4f;
  --or-brand-2: #1c4d66;
  --or-accent: #22d3ee;
  --or-shadow-sm: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
  --or-shadow-md: 0 4px 12px rgba(21, 59, 79, 0.08), 0 2px 4px rgba(21, 59, 79, 0.04);
  --or-shadow-lg: 0 12px 32px rgba(21, 59, 79, 0.12), 0 4px 8px rgba(21, 59, 79, 0.06);
  background: #f5f7fa;
  min-height: 60vh;
}

/* ── Stepper bar ─────────────────────────── */
.or-stepper-bar {
  background: linear-gradient(135deg, #ffffff 0%, #f0f4f8 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 14px 0;
  position: sticky;
  top: 0;
  z-index: 10;
  backdrop-filter: blur(10px);
}
.or-step-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.or-step-done { background: linear-gradient(135deg, #059669, #10b981); color: #fff; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3); }
.or-step-active { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; box-shadow: 0 2px 12px rgba(21, 59, 79, 0.35); }
.or-step-pending { background: #e2e8f0; color: #94a3b8; }
.or-step-label { display: block; font-size: 13px; font-weight: 700; line-height: 1.2; letter-spacing: -0.01em; }
.or-step-sub { display: block; font-size: 11px; font-weight: 500; color: #94a3b8; line-height: 1.3; }
.or-step-line { height: 3px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; }
.or-step-line-fill { height: 100%; background: linear-gradient(90deg, #059669, #10b981); border-radius: 9999px; transition: width 0.6s cubic-bezier(0.22, 1, 0.36, 1); }

/* ── Hero header ─────────────────────────── */
.or-hero {
  --ease: cubic-bezier(0.22, 1, 0.36, 1);
  position: relative;
  background: linear-gradient(135deg, #0b2230 0%, #153b4f 45%, #0b1b26 100%);
  color: #fff;
  padding: 28px 0 40px;
  overflow: hidden;
}
.or-hero::before {
  content: '';
  position: absolute;
  inset: 0;
  background:
    radial-gradient(ellipse 80% 60% at 15% 100%, rgba(34, 211, 238, 0.18) 0%, transparent 55%),
    radial-gradient(ellipse 60% 40% at 85% 0%, rgba(255, 255, 255, 0.06) 0%, transparent 50%);
  pointer-events: none;
}
.or-hero-inner { position: relative; z-index: 1; }

.or-breadcrumb {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: rgba(255, 255, 255, 0.55);
  margin-bottom: 18px;
}
.or-breadcrumb a {
  color: rgba(255, 255, 255, 0.7);
  text-decoration: none;
  transition: color var(--or-dur) var(--or-ease);
}
.or-breadcrumb a:hover { color: #22d3ee; }
.or-breadcrumb svg { width: 14px; height: 14px; opacity: 0.5; }
.or-breadcrumb-current { color: #fff; font-weight: 600; }

.or-hero-grid {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 24px;
  flex-wrap: wrap;
}
.or-eyebrow {
  display: inline-block;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  color: #22d3ee;
  margin-bottom: 8px;
}
.or-hero-title h1 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: clamp(1.6rem, 2.8vw, 2.25rem);
  font-weight: 700;
  line-height: 1.15;
  color: #fff;
  margin: 0 0 8px;
  letter-spacing: -0.01em;
}
.or-hero-title p {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
}
.or-hero-title p svg { width: 16px; height: 16px; color: #22d3ee; flex-shrink: 0; }
.or-hero-sep { opacity: 0.4; }

.or-hero-dates {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  backdrop-filter: blur(20px) saturate(1.4);
}
.or-date-card { display: flex; flex-direction: column; padding: 4px 10px; min-width: 110px; }
.or-date-label { font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255, 255, 255, 0.5); }
.or-date-value { font-size: 15px; font-weight: 700; color: #fff; margin-top: 2px; }
.or-date-time { font-size: 12px; color: rgba(255, 255, 255, 0.6); margin-top: 1px; }
.or-date-arrow { color: #22d3ee; }
.or-date-arrow svg { width: 18px; height: 18px; }
.or-days-pill {
  background: rgba(34, 211, 238, 0.15);
  border: 1px solid rgba(34, 211, 238, 0.3);
  color: #22d3ee;
  font-size: 12px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 9999px;
  white-space: nowrap;
}

/* ── Body ──────────────────────────────── */
.or-body { padding: 32px 0 48px; }
.or-body-inner { display: flex; flex-direction: column; gap: 20px; }

/* Expired alert */
.or-alert {
  display: flex;
  align-items: flex-start;
  gap: 14px;
  padding: 18px 20px;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #fcd34d;
  border-radius: 16px;
  box-shadow: var(--or-shadow-sm);
}
.or-alert-icon {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  background: #fbbf24;
  color: #78350f;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.or-alert-icon svg { width: 22px; height: 22px; }
.or-alert-body { flex: 1; min-width: 0; }
.or-alert-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #92400e; margin: 0 0 4px; }
.or-alert-message { font-size: 14px; color: #78350f; margin: 0; line-height: 1.5; }
.or-alert-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  background: #153b4f;
  color: #fff;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: transform var(--or-dur) var(--or-ease), background var(--or-dur) var(--or-ease), box-shadow var(--or-dur) var(--or-ease);
  flex-shrink: 0;
}
.or-alert-btn:hover { background: #0f2936; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(21, 59, 79, 0.25); }
.or-alert-btn svg { width: 16px; height: 16px; }

/* Main grid */
.or-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 20px;
  align-items: start;
}
@media (min-width: 1024px) {
  .or-grid { grid-template-columns: minmax(0, 1fr) 380px; }
  .or-col-side { position: sticky; top: 88px; }
}
.or-col-main { display: flex; flex-direction: column; gap: 20px; min-width: 0; }

/* ── Vehicle hero card ─────────────────── */
.or-vehicle {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  overflow: hidden;
  box-shadow: var(--or-shadow-sm);
  transition: box-shadow var(--or-dur) var(--or-ease);
}
.or-vehicle:hover { box-shadow: var(--or-shadow-md); }
.or-vehicle-media {
  position: relative;
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  aspect-ratio: 16 / 8;
  max-height: 320px;
  overflow: hidden;
}
.or-vehicle-media-split {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.95fr);
}
.or-vehicle-media-image {
  min-width: 0;
  min-height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.or-vehicle-media-image img { width: 100%; height: 100%; object-fit: cover; }
.or-vehicle-media-map {
  min-width: 0;
  min-height: 100%;
  background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
}
.or-vehicle-empty { display: flex; flex-direction: column; align-items: center; gap: 8px; color: #94a3b8; }
.or-vehicle-empty svg { width: 56px; height: 56px; }
.or-vehicle-empty span { font-size: 13px; font-weight: 500; }
.or-vehicle-badges { position: absolute; top: 14px; left: 14px; display: flex; gap: 8px; flex-wrap: wrap; }
.or-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.02em;
  backdrop-filter: blur(10px);
}
.or-badge svg { width: 12px; height: 12px; }
.or-badge-selected {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff;
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}
.or-badge-muted {
  background: rgba(255, 255, 255, 0.92);
  color: #153b4f;
  border: 1px solid rgba(21, 59, 79, 0.1);
}

.or-vehicle-body { padding: 24px; }
.or-vehicle-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
.or-vehicle-title h2 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1.5rem;
  font-weight: 700;
  color: #153b4f;
  line-height: 1.2;
  margin: 0 0 6px;
  letter-spacing: -0.01em;
}
.or-vehicle-title p {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
  margin: 0;
  font-weight: 500;
}
.or-vehicle-title p svg { width: 14px; height: 14px; color: #94a3b8; }
.or-vehicle-price { text-align: right; }
.or-price-eyebrow { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #94a3b8; }
.or-price-value { display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.75rem; font-weight: 800; color: #153b4f; line-height: 1.1; margin-top: 4px; letter-spacing: -0.02em; }
.or-price-sub { display: block; font-size: 12px; color: #64748b; margin-top: 2px; font-weight: 500; }

.or-spec-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 20px;
}
@media (min-width: 640px) { .or-spec-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
@media (min-width: 1280px) { .or-spec-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }

.or-spec {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: border-color var(--or-dur) var(--or-ease), background var(--or-dur) var(--or-ease), transform var(--or-dur) var(--or-ease);
}
.or-spec:hover { border-color: #153b4f; background: #f0f8fc; transform: translateY(-1px); }
.or-spec-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.or-spec-icon svg { width: 18px; height: 18px; }
.or-icon-fuel { background: #fef3c7; color: #d97706; }
.or-icon-ac { background: #cffafe; color: #0891b2; }
.or-icon-seat { background: #e0e7ff; color: #4f46e5; }
.or-icon-door { background: #fce7f3; color: #be185d; }
.or-icon-trans { background: #dcfce7; color: #059669; }
.or-icon-lug { background: #ffe4e6; color: #e11d48; }
.or-spec-text { display: flex; flex-direction: column; min-width: 0; }
.or-spec-text span { font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #94a3b8; line-height: 1.2; }
.or-spec-text strong { font-size: 13px; font-weight: 700; color: #0f172a; margin-top: 2px; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.or-vehicle-foot {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
  flex-wrap: wrap;
}

/* ── Buttons ─────────────────────────── */
.or-btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 12px 22px;
  background: linear-gradient(135deg, #153b4f 0%, #1c4d66 100%);
  color: #fff;
  border: 1px solid #153b4f;
  border-radius: 12px;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition: transform var(--or-dur) var(--or-ease), box-shadow var(--or-dur) var(--or-ease), background var(--or-dur) var(--or-ease);
  box-shadow: 0 4px 12px rgba(21, 59, 79, 0.18);
}
.or-btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(21, 59, 79, 0.28); }
.or-btn-primary:active:not(:disabled) { transform: translateY(0); }
.or-btn-primary:disabled { opacity: 0.55; cursor: not-allowed; box-shadow: none; }
.or-btn-primary svg { width: 16px; height: 16px; transition: transform var(--or-dur) var(--or-ease); }
.or-btn-primary:hover:not(:disabled) svg { transform: translateX(3px); }
.or-btn-block { width: 100%; padding: 14px 22px; font-size: 15px; margin-top: 18px; }

.or-btn-ghost {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 10px 16px;
  background: #fff;
  color: #153b4f;
  border: 1px solid #153b4f;
  border-radius: 10px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: all var(--or-dur) var(--or-ease);
  white-space: nowrap;
}
.or-btn-ghost:hover:not(:disabled) { background: #153b4f; color: #fff; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(21, 59, 79, 0.22); }
.or-btn-ghost:disabled { opacity: 0.55; cursor: not-allowed; }
.or-btn-ghost svg { width: 14px; height: 14px; transition: transform var(--or-dur) var(--or-ease); }
.or-btn-ghost:hover:not(:disabled) svg { transform: translateX(2px); }

/* ── Panels (Trip, Offices, Alternatives) ── */
.or-panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  box-shadow: var(--or-shadow-sm);
  transition: box-shadow var(--or-dur) var(--or-ease);
}
.or-panel:hover { box-shadow: var(--or-shadow-md); }
.or-panel-head {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}
.or-panel-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.or-panel-icon svg { width: 20px; height: 20px; }
.or-icon-blue { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #1e40af; }
.or-icon-green { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }
.or-icon-amber { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #92400e; }
.or-icon-cyan { background: linear-gradient(135deg, #cffafe, #a5f3fc); color: #0e7490; }
.or-panel-title { min-width: 0; }
.or-panel-title h3 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: #153b4f;
  margin: 0;
  line-height: 1.2;
}
.or-panel-title p { font-size: 12px; color: #94a3b8; margin: 2px 0 0; line-height: 1.3; }
.or-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 28px;
  height: 28px;
  padding: 0 10px;
  background: #f1f5f9;
  color: #475569;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 700;
}

/* Trip details */
.or-trip-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 14px;
}
.or-trip-item {
  padding: 12px 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  transition: border-color var(--or-dur) var(--or-ease), background var(--or-dur) var(--or-ease);
}
.or-trip-item:hover { border-color: #153b4f; background: #f0f8fc; }
.or-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #94a3b8; }
.or-value { display: block; font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 3px; }

/* Offices */
.or-offices { display: grid; grid-template-columns: 1fr; gap: 16px; }
@media (min-width: 768px) { .or-offices { grid-template-columns: 1fr 1fr; } }
.or-office p { margin: 0; }
.or-office-name { font-size: 14px; font-weight: 700; color: #0f172a; }
.or-office-addr { font-size: 13px; color: #64748b; margin-top: 4px !important; line-height: 1.5; }
.or-office-phone { display: inline-flex; align-items: center; gap: 6px; margin-top: 10px !important; font-size: 13px; font-weight: 600; color: #153b4f; }
.or-office-phone svg { width: 14px; height: 14px; }
.or-office-note { font-size: 12px; color: #94a3b8; margin-top: 10px !important; line-height: 1.5; padding-top: 10px; border-top: 1px dashed #e2e8f0; }

/* Alternative offers */
.or-alts { display: flex; flex-direction: column; gap: 12px; }
.or-alt {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr) auto auto;
  align-items: center;
  gap: 14px;
  padding: 12px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  transition: transform var(--or-dur) var(--or-ease), border-color var(--or-dur) var(--or-ease), box-shadow var(--or-dur) var(--or-ease), background var(--or-dur) var(--or-ease);
}
.or-alt:hover { transform: translateY(-2px); border-color: #153b4f; background: #fff; box-shadow: 0 8px 20px rgba(21, 59, 79, 0.1); }
.or-alt-media {
  width: 72px;
  height: 60px;
  border-radius: 10px;
  background: #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  flex-shrink: 0;
  color: #94a3b8;
}
.or-alt-media img { width: 100%; height: 100%; object-fit: cover; }
.or-alt-media svg { width: 28px; height: 28px; }
.or-alt-info { min-width: 0; }
.or-alt-info h4 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 14px; font-weight: 700; color: #153b4f; margin: 0; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.or-alt-info p { font-size: 12px; color: #64748b; margin: 4px 0 0; line-height: 1.3; }
.or-alt-price { text-align: right; }
.or-alt-price strong { display: block; font-size: 16px; font-weight: 800; color: #0f172a; font-family: 'Plus Jakarta Sans', sans-serif; line-height: 1.1; }
.or-alt-price span { display: block; font-size: 11px; color: #94a3b8; margin-top: 2px; }

@media (max-width: 640px) {
  .or-alt {
    grid-template-columns: 64px minmax(0, 1fr);
    grid-template-areas: 'media info' 'price price' 'btn btn';
  }
  .or-alt-media { grid-area: media; }
  .or-alt-info { grid-area: info; }
  .or-alt-price { grid-area: price; text-align: left; display: flex; align-items: baseline; gap: 6px; }
  .or-alt-price span { margin-top: 0; }
  .or-alt .or-btn-ghost { grid-area: btn; justify-content: center; }
}

.or-empty { font-size: 13px; color: #94a3b8; text-align: center; padding: 24px; background: #f8fafc; border: 1px dashed #e2e8f0; border-radius: 12px; margin: 0; }

/* ── Price summary sidebar ─────────────── */
.or-summary {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 24px;
  box-shadow: var(--or-shadow-md);
  position: relative;
  overflow: hidden;
}
.or-summary::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #153b4f, #22d3ee);
}
.or-summary-head { padding-bottom: 18px; border-bottom: 1px solid #e2e8f0; margin-bottom: 18px; }
.or-summary-eyebrow { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase; color: #94a3b8; }
.or-summary-total {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2.25rem;
  font-weight: 800;
  color: #153b4f;
  line-height: 1.1;
  margin-top: 6px;
  letter-spacing: -0.02em;
}
.or-summary-days {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  color: #64748b;
  margin: 8px 0 0;
  font-weight: 500;
}
.or-summary-days svg { width: 14px; height: 14px; color: #94a3b8; }

.or-summary-rows { display: flex; flex-direction: column; gap: 12px; }
.or-summary-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; font-size: 13px; }
.or-summary-row span { color: #64748b; font-weight: 500; flex-shrink: 0; }
.or-summary-row strong { color: #0f172a; font-weight: 700; text-align: right; }
.or-summary-row-stack { flex-direction: column; gap: 4px; padding: 10px 12px; background: #f8fafc; border-radius: 10px; }
.or-summary-row-stack span { font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #94a3b8; }
.or-summary-row-stack strong { font-size: 13px; text-align: left; }

.or-product {
  margin-top: 18px;
  padding: 14px;
  background: linear-gradient(135deg, #f0f8fc 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
  border-radius: 12px;
}
.or-product-eyebrow { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; color: #0e7490; }
.or-product-name { font-size: 13px; font-weight: 700; color: #153b4f; margin: 4px 0 0; }
.or-product-sub { font-size: 12px; color: #64748b; margin: 4px 0 0; line-height: 1.4; }

.or-summary-note {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: #94a3b8;
  margin: 14px 0 0;
  justify-content: center;
  width: 100%;
  font-weight: 500;
}
.or-summary-note svg { width: 13px; height: 13px; color: #10b981; }

/* ── Mobile ─────────────────────────── */
@media (max-width: 1023px) {
  .or-hero { padding: 22px 0 30px; }
  .or-hero-grid { flex-direction: column; align-items: flex-start; }
  .or-hero-dates { width: 100%; justify-content: space-between; }
  .or-body { padding: 20px 0 40px; }
  .or-vehicle-media { aspect-ratio: auto; max-height: none; }
  .or-vehicle-media-split { grid-template-columns: minmax(0, 1fr); }
  .or-vehicle-media-image { min-height: 240px; }
  .or-vehicle-media-map {
    min-height: 220px;
    border-top: 1px solid #e2e8f0;
  }
}
@media (max-width: 640px) {
  .or-hero { padding: 18px 0 24px; }
  .or-hero-dates { flex-wrap: wrap; gap: 8px; padding: 10px; border-radius: 14px; }
  .or-date-card { min-width: 90px; padding: 2px 6px; }
  .or-date-arrow { display: none; }
  .or-vehicle-body { padding: 18px; }
  .or-vehicle-head { flex-direction: column; }
  .or-vehicle-price { text-align: left; }
  .or-price-value { font-size: 1.5rem; }
  .or-summary-total { font-size: 1.85rem; }
}
</style>
