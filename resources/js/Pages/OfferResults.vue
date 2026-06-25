<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'
import Footer from '@/Components/Footer.vue'
import { Toaster } from '@/Components/ui/sonner'
import BookingExtrasStep from '@/Components/BookingExtras/BookingExtrasStep.vue'
import BookingCheckoutStep from '@/Components/BookingCheckoutStep.vue'
import CarListingCard from '@/Components/CarListingCard.vue'
import { useCurrencyConversion } from '@/composables/useCurrencyConversion'
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry'

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
  language?: string | null
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
  currency?: string | null
}

type AppliedOffer = {
  id?: string | number | null
  name?: string | null
  title?: string | null
  description?: string | null
  effect_type?: string | null
}

type InsuranceOption = {
  id?: string | null
  name?: string | null
  coverage_type?: string | null
  included?: boolean | null
  daily_rate?: number | null
  total_price?: number | null
  currency?: string | null
  excess_amount?: number | null
  deposit_amount?: number | null
  description?: string | null
}

type CoverageSnapshot = Record<string, {
  included?: boolean | null
  excess_amount?: number | null
  deposit_amount?: number | null
  currency?: string | null
  description?: string | null
}>

type OfferFact = {
  key: string
  label: string
  detail: string
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
  extras_preview?: Array<Record<string, unknown>> | null
  insurance_options?: InsuranceOption[] | null
  coverages?: CoverageSnapshot | null
  free_esim_included?: boolean | null
  applied_offers?: AppliedOffer[] | null
  inclusions?: string[] | null
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
  search_session_id?: string | null
  gateway_search_id?: string | null
  provider_pickup_id?: string | number | null
  unified_location_id?: string | number | null
  dropoff_unified_location_id?: string | number | null
  driver_age?: string | number | null
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
  dropoff_location_details?: Record<string, unknown> | null
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

type SharedPageProps = {
  locale?: string
  translations?: {
    offerresults?: Record<string, string>
  }
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
const relatedCardsLimit = ref(20)
const page = usePage<SharedPageProps>()
const { getCurrencySymbol } = useCurrencyConversion()

const vehicle = computed(() => props.quote.vehicle ?? {})
const pricing = computed(() => props.quote.pricing ?? {})
const policies = computed(() => props.quote.policies ?? {})
const pickupLocation = computed(() => props.quote.pickup_location_details ?? {})
const dropoffLocation = computed(() => props.quote.dropoff_location_details ?? {})
const search = computed(() => props.quote.search ?? {})
const displayedQuotes = computed(() => props.offerResults.quotes ?? [])
const alternativeQuotes = computed(() => displayedQuotes.value.filter((offer) => offer.quote_id !== props.quote.quote_id))
const visibleAlternativeQuotes = computed(() => alternativeQuotes.value.slice(0, relatedCardsLimit.value))
const canLoadMoreAlternativeQuotes = computed(() => relatedCardsLimit.value < alternativeQuotes.value.length)
const quoteStatus = computed(() => props.quoteStatus ?? {})
const isExpired = computed(() => quoteStatus.value.expired === true)

const currentBookingContext = computed(() => activeBookingContext.value ?? props.bookingContext)
const selectedVehicle = computed<Record<string, unknown> | null>(() => currentBookingContext.value?.vehicle ?? null)
const selectedOptionalExtras = computed(() => currentBookingContext.value?.optional_extras ?? [])
const bookingCurrencyCode = computed(() => {
  const vehiclePricing = selectedVehicle.value?.pricing as Record<string, unknown> | undefined
  return `${vehiclePricing?.currency ?? pricing.value.currency ?? search.value.currency ?? 'EUR'}`
})
const bookingCurrencySymbol = computed(() => resolveCurrencySymbol(bookingCurrencyCode.value))
const displayCurrencyCode = computed(() => `${pricing.value.currency ?? search.value.currency ?? 'EUR'}`)
const currentLocale = computed(() => page.props.locale ?? search.value.language ?? 'en')

const _t = (key: string, fallback: string, replacements: Record<string, string | number> = {}) => {
  let translation = page.props.translations?.offerresults?.[key] || fallback

  Object.entries(replacements).forEach(([token, value]) => {
    translation = translation.replace(`:${token}`, `${value}`)
  })

  return translation
}

const isRecord = (value: unknown): value is Record<string, unknown> => value !== null && typeof value === 'object' && !Array.isArray(value)
const asRecordArray = (value: unknown): Array<Record<string, unknown>> => Array.isArray(value) ? value.filter(isRecord) : []
const asRecord = (value: unknown): Record<string, unknown> => isRecord(value) ? value : {}
const toFiniteNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') {
    return null
  }

  const numeric = Number(value)
  return Number.isFinite(numeric) ? numeric : null
}
const stringValue = (value: unknown): string | null => {
  const text = `${value ?? ''}`.trim()
  return text === '' ? null : text
}
const checkoutSearchSessionId = computed(() => stringValue(currentBookingContext.value?.search_session_id ?? selectedVehicle.value?.search_session_id ?? null))
const checkoutGatewaySearchId = computed(() => stringValue(currentBookingContext.value?.gateway_search_id ?? selectedVehicle.value?.gateway_search_id ?? null))

const displayName = computed(() => vehicle.value.display_name ?? [vehicle.value.brand, vehicle.value.model].filter(Boolean).join(' '))
const pageTitle = computed(() => `${displayName.value || _t('selected_offer_title', 'Selected Offer')} | Vrooem`)
const offerAvailabilityText = computed(() => {
  const count = displayedQuotes.value.length
  const key = count === 1 ? 'offer_available' : 'offers_available'
  const fallback = count === 1 ? ':count offer available' : ':count offers available'

  return _t(key, fallback, { count })
})

const luggageSummary = computed(() => {
  const luggage = vehicle.value.luggage ?? {}
  const parts = [
    luggage.small != null ? `${luggage.small} ${_t('luggage_small', 'small')}` : null,
    luggage.medium != null ? `${luggage.medium} ${_t('luggage_medium', 'medium')}` : null,
    luggage.large != null ? `${luggage.large} ${_t('luggage_large', 'large')}` : null,
  ].filter(Boolean)

  return parts.length > 0 ? parts.join(' / ') : _t('not_specified', 'Not specified')
})

const supplierDisplayName = computed(() => quoteSupplierName(props.quote))
const supplierInitials = computed(() => {
  const initials = supplierDisplayName.value
    .split(/\s+/)
    .map((part) => part.charAt(0))
    .join('')
    .slice(0, 2)
    .toUpperCase()

  return initials || 'VR'
})
const primaryOfferVehicle = computed<Record<string, unknown>>(() => props.bookingContext?.vehicle ?? {})
const offerProducts = computed(() => asRecordArray(primaryOfferVehicle.value.products ?? props.quote.products))
const offerExtras = computed(() => asRecordArray(primaryOfferVehicle.value.extras_preview ?? primaryOfferVehicle.value.extras ?? props.quote.extras_preview))
const featuredProduct = computed<Product | null>(() => {
  const product = offerProducts.value[0]

  if (!product) {
    return null
  }

  return {
    type: stringValue(product.type),
    name: stringValue(product.name),
    subtitle: stringValue(product.subtitle),
    total: toFiniteNumber(product.total),
    price_per_day: toFiniteNumber(product.price_per_day),
    deposit: toFiniteNumber(product.deposit),
    deposit_currency: stringValue(product.deposit_currency),
    is_basic: product.is_basic === true,
    currency: stringValue(product.currency),
  }
})

const partnerSourceLabel = computed(() => {
  const source = `${props.quote.case_id || vehicle.value.provider_code || vehicle.value.supplier_code || ''}`.toLowerCase()

  if (source.includes('trabber')) {
    return _t('trabber_verified_offer', 'Trabber verified offer')
  }

  if (source.includes('skyscanner')) {
    return _t('skyscanner_verified_offer', 'Skyscanner verified offer')
  }

  return _t('partner_verified_offer', 'Partner verified offer')
})

const hasFreeEsim = computed(() => {
  if (props.quote.free_esim_included === true) {
    return true
  }

  const appliedOfferText = (props.quote.applied_offers ?? [])
    .map((offer) => [offer.name, offer.title, offer.description, offer.effect_type].filter(Boolean).join(' '))
    .join(' ')
  const inclusionText = (props.quote.inclusions ?? []).join(' ')

  return `${appliedOfferText} ${inclusionText}`.toLowerCase().includes('esim')
})

const mileageSummary = computed(() => {
  const policy = policies.value.mileage_policy
  const limit = policies.value.mileage_limit_km

  if (`${policy || ''}`.toLowerCase() === 'unlimited') {
    return _t('unlimited_mileage', 'Unlimited mileage')
  }

  if (limit != null) {
    return `${limit} km ${_t('per_day_lower', 'per day')}`
  }

  return policy || _t('not_specified', 'Not specified')
})

const transmissionSummary = computed(() => {
  const transmission = `${vehicle.value.transmission || ''}`.trim()
  const normalized = transmission.toLowerCase()

  if (normalized === 'automatic') {
    return _t('auto', 'Auto')
  }

  if (normalized === 'manual') {
    return _t('manual', 'Manual')
  }

  return transmission || '-'
})

const fuelPolicySummary = computed(() => policies.value.fuel_policy || _t('not_specified', 'Not specified'))

const rentalSummary = computed(() => {
  const days = dayCountText(currentBookingContext.value?.number_of_days || 1)

  return search.value.driver_age
    ? `${days}, ${_t('age_label', 'age')} ${search.value.driver_age}`
    : days
})

const paymentPercentage = computed(() => currentBookingContext.value?.payment_percentage || 15)
const roundCurrencyAmount = (amount: number) => Math.round((amount + Number.EPSILON) * 100) / 100
const estimatedPayNowAmount = computed(() => {
  const total = toFiniteNumber(pricing.value.total_price)
  return total === null ? null : roundCurrencyAmount((total * paymentPercentage.value) / 100)
})
const estimatedPayLaterAmount = computed(() => {
  const total = toFiniteNumber(pricing.value.total_price)
  return total === null || estimatedPayNowAmount.value === null
    ? null
    : roundCurrencyAmount(total - estimatedPayNowAmount.value)
})

const formatAmount = (amount?: number | null, currency?: string | null) => {
  if (amount == null) {
    return _t('not_available', 'Not available')
  }

  const safeCurrency = currency || 'EUR'

  try {
    return new Intl.NumberFormat(currentLocale.value, {
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
    return _t('not_available', 'Not available')
  }

  const normalizedSource = `${sourceCurrency || pricing.value.currency || search.value.currency || 'EUR'}`
  const normalizedDisplay = normalizedSource || displayCurrencyCode.value

  try {
    return new Intl.NumberFormat(currentLocale.value, {
      style: 'currency',
      currency: normalizedDisplay,
      maximumFractionDigits: 2,
    }).format(amount)
  } catch {
    return `${getCurrencySymbol(normalizedDisplay)}${amount.toFixed(2)}`
  }
}

const offerInsuranceOptions = computed(() => asRecordArray(primaryOfferVehicle.value.insurance_options ?? props.quote.insurance_options))
const offerCoverages = computed(() => asRecord(primaryOfferVehicle.value.coverages ?? props.quote.coverages))
const formatFactAmount = (amount: unknown, currency?: unknown) => {
  const numeric = toFiniteNumber(amount)
  return numeric === null ? null : formatDisplayAmount(numeric, stringValue(currency) || pricing.value.currency || search.value.currency || 'EUR')
}
const offerPackageFacts = computed<OfferFact[]>(() => offerProducts.value.map((product, index) => {
  const label = stringValue(product.name) || stringValue(product.type) || _t('package', 'Package')
  const total = formatFactAmount(product.total, product.currency)
  const subtitle = stringValue(product.subtitle)

  return {
    key: `product-${stringValue(product.type) || index}`,
    label,
    detail: [subtitle, total].filter(Boolean).join(' · ') || _t('available_on_offer', 'Available on this offer'),
  }
}))
const offerInsuranceFacts = computed<OfferFact[]>(() => offerInsuranceOptions.value.map((option, index) => {
  const label = stringValue(option.name) || stringValue(option.coverage_type) || _t('insurance_cover', 'Insurance cover')
  const currency = stringValue(option.currency) || pricing.value.currency || search.value.currency || 'EUR'
  const total = toFiniteNumber(option.total_price)
  const dailyRate = toFiniteNumber(option.daily_rate)
  const included = option.included === true || total === 0
  const price = included
    ? _t('included', 'Included')
    : (formatFactAmount(total, currency) ?? (dailyRate !== null ? `${formatDisplayAmount(dailyRate, currency)} / ${_t('day_lower', 'day')}` : null))
  const excess = formatFactAmount(option.excess_amount, currency)
  const detail = [stringValue(option.description), price, excess ? `${_t('excess', 'Excess')} ${excess}` : null].filter(Boolean).join(' · ')

  return {
    key: `insurance-${stringValue(option.id) || stringValue(option.coverage_type) || index}`,
    label,
    detail: detail || _t('available_on_offer', 'Available on this offer'),
  }
}))
const offerCoverageFacts = computed<OfferFact[]>(() => Object.entries(offerCoverages.value).map(([key, coverage]) => {
  const item = asRecord(coverage)
  const currency = stringValue(item.currency) || pricing.value.currency || search.value.currency || 'EUR'
  const excess = formatFactAmount(item.excess_amount, currency)
  const deposit = formatFactAmount(item.deposit_amount, currency)
  const detail = [
    stringValue(item.description),
    item.included === true ? _t('included', 'Included') : null,
    excess ? `${_t('excess', 'Excess')} ${excess}` : null,
    deposit ? `${_t('deposit', 'Deposit')} ${deposit}` : null,
  ].filter(Boolean).join(' · ')

  return {
    key: `coverage-${key}`,
    label: `${key.toUpperCase()} ${_t('coverage', 'coverage')}`,
    detail: detail || _t('available_on_offer', 'Available on this offer'),
  }
}))
const offerProtectionFacts = computed<OfferFact[]>(() => [
  ...offerInsuranceFacts.value,
  ...offerCoverageFacts.value,
])
const offerExtraFacts = computed<OfferFact[]>(() => offerExtras.value.map((extra, index) => {
  const label = stringValue(extra.name) || stringValue(extra.code) || _t('extra', 'Extra')
  const total = formatFactAmount(extra.total_for_booking ?? extra.total_price ?? extra.price ?? extra.amount, extra.currency)
  const detail = [stringValue(extra.description), total].filter(Boolean).join(' · ')

  return {
    key: `extra-${stringValue(extra.id) || stringValue(extra.code) || index}`,
    label,
    detail: detail || _t('available_on_offer', 'Available on this offer'),
  }
}))
const formatDateTime = (date?: string | null, time?: string | null) => {
  if (!date) {
    return _t('not_specified', 'Not specified')
  }

  return time ? `${date} ${_t('at_time', 'at')} ${time}` : date
}

const formatLocationMeta = (details: LocationDetails) => {
  const parts = [details.location_type, details.iata, details.country_code].filter(Boolean)
  return parts.length > 0 ? parts.join(' / ') : _t('location_details_available', 'Location details available')
}

const formatCoordinate = (details: LocationDetails) => {
  if (details.latitude == null || details.longitude == null) {
    return null
  }

  return `${Number(details.latitude).toFixed(5)}, ${Number(details.longitude).toFixed(5)}`
}

const locationTypeLabel = (details: LocationDetails) => {
  const type = `${details.location_type || ''}`.toLowerCase()

  if (type.includes('airport')) {
    return _t('airport', 'Airport')
  }

  if (type.includes('terminal')) {
    return _t('terminal', 'Terminal')
  }

  if (type.includes('city')) {
    return _t('city', 'City')
  }

  return details.location_type || _t('office', 'Office')
}

const quotePrice = (quote: Quote) => formatDisplayAmount(quote.pricing?.total_price, quote.pricing?.currency || props.offerResults.search?.currency)

const quoteSupplierName = (_quote: Quote) => 'Vrooem'
const quoteDailyPrice = (quote: Quote) => formatDisplayAmount(quote.pricing?.price_per_day, quote.pricing?.currency || props.offerResults.search?.currency)
const resolveCurrencySymbol = (currency: string) => registryCurrencySymbol(currency)
const dayCountText = (days?: number | null) => {
  const count = days || 1

  return _t(count === 1 ? 'day_count' : 'days_count', count === 1 ? ':count day' : ':count days', { count })
}
const forDayCountText = (days?: number | null) => {
  const count = days || 1

  return _t(count === 1 ? 'for_day_count' : 'for_days_count', count === 1 ? 'For :count day' : 'For :count days', { count })
}

const relatedSearchForm = computed(() => ({
  date_from: search.value.pickup_date ?? '',
  date_to: search.value.dropoff_date ?? '',
  start_time: search.value.pickup_time ?? '09:00',
  end_time: search.value.dropoff_time ?? '09:00',
  age: search.value.driver_age ?? '',
  currency: search.value.currency ?? displayCurrencyCode.value,
  location: pickupLocation.value.name ?? '',
  where: pickupLocation.value.name ?? '',
  dropoff_where: dropoffLocation.value.name ?? pickupLocation.value.name ?? '',
}))

const quoteCardVehicle = (quote: Quote): Record<string, unknown> => {
  const contextVehicle = (props.bookingContexts?.[quote.quote_id]?.vehicle ?? {}) as Record<string, unknown>
  const quoteVehicle = quote.vehicle ?? {}
  const quotePricing = quote.pricing ?? {}
  const source = `${contextVehicle.source ?? quote.supplier?.code ?? quoteVehicle.supplier_code ?? 'internal'}`

  return {
    ...contextVehicle,
    id: contextVehicle.id ?? quoteVehicle.provider_vehicle_id ?? quote.quote_id,
    quoteid: contextVehicle.quoteid ?? quote.quote_id,
    source,
    provider_code: contextVehicle.provider_code ?? quote.supplier?.code ?? quoteVehicle.supplier_code,
    display_name: contextVehicle.display_name ?? quoteVehicle.display_name ?? [quoteVehicle.brand, quoteVehicle.model].filter(Boolean).join(' '),
    brand: contextVehicle.brand ?? quoteVehicle.brand,
    model: contextVehicle.model ?? quoteVehicle.model,
    category: contextVehicle.category ?? quoteVehicle.category,
    image: contextVehicle.image ?? quoteVehicle.image_url,
    currency: contextVehicle.currency ?? quotePricing.currency ?? search.value.currency,
    total_price: contextVehicle.total_price ?? quotePricing.total_price,
    price_per_day: contextVehicle.price_per_day ?? quotePricing.price_per_day,
    pricing: {
      ...((contextVehicle.pricing as Record<string, unknown> | undefined) ?? {}),
      currency: (contextVehicle.pricing as Record<string, unknown> | undefined)?.currency ?? quotePricing.currency ?? search.value.currency,
      total_price: (contextVehicle.pricing as Record<string, unknown> | undefined)?.total_price ?? quotePricing.total_price,
      price_per_day: (contextVehicle.pricing as Record<string, unknown> | undefined)?.price_per_day ?? quotePricing.price_per_day,
    },
    supplier_name: quoteSupplierName(quote),
    partner_supplier_name: quoteSupplierName(quote),
    sipp_code: contextVehicle.sipp_code ?? quoteVehicle.sipp_code,
    transmission: contextVehicle.transmission ?? quoteVehicle.transmission,
    fuel: contextVehicle.fuel ?? quoteVehicle.fuel_type,
    fuel_policy: contextVehicle.fuel_policy ?? quote.policies?.fuel_policy,
    seating_capacity: contextVehicle.seating_capacity ?? quoteVehicle.seats,
    doors: contextVehicle.doors ?? quoteVehicle.doors,
    mileage: contextVehicle.mileage ?? quote.policies?.mileage_policy,
    benefits: contextVehicle.benefits ?? {},
    products: contextVehicle.products ?? quote.products ?? [],
    extras: contextVehicle.extras ?? quote.extras_preview ?? [],
    extras_preview: contextVehicle.extras_preview ?? quote.extras_preview ?? [],
    insurance_options: contextVehicle.insurance_options ?? quote.insurance_options ?? [],
    coverages: contextVehicle.coverages ?? quote.coverages ?? {},
  }
}

const handleRelatedCardSelection = (quoteId: string, selection: Record<string, unknown>) => {
  startBooking(quoteId, selection)
}

const loadMoreAlternativeQuotes = () => {
  relatedCardsLimit.value += 20
}

const startBooking = (quoteId: string, selection: Record<string, unknown> | null = null) => {
  if (isExpired.value) {
    return
  }

  const context = props.bookingContexts?.[quoteId]

  if (!context) {
    return
  }

  activeBookingContext.value = context
  selectedPackage.value = `${selection?.package ?? context.initial_package ?? 'BAS'}`
  selectedProtectionCode.value = selection?.protection_code != null
    ? `${selection.protection_code}`
    : context.initial_protection_code ?? null
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
            <span class="or-step-label text-[#153b4f] group-hover:underline">{{ _t('step_offer', 'Offer') }}</span>
            <span class="or-step-sub">{{ _t('step_selected_deal', 'Selected deal') }}</span>
          </div>
        </div>

        <div class="or-step-line flex-1 mx-3"><div class="or-step-line-fill" style="width:100%"></div></div>

        <div class="flex items-center gap-2.5" :class="bookingStep === 'checkout' ? 'cursor-pointer' : ''" @click="bookingStep === 'checkout' ? handleBackToExtras() : null">
          <div class="or-step-dot" :class="bookingStep === 'checkout' ? 'or-step-done' : bookingStep === 'extras' ? 'or-step-active' : 'or-step-pending'">
            <svg v-if="bookingStep === 'checkout'" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V10M18 20V4M6 20v-6"/></svg>
          </div>
          <div class="hidden sm:block">
            <span class="or-step-label" :class="bookingStep === 'extras' || bookingStep === 'checkout' ? 'text-[#153b4f]' : 'text-gray-400'">{{ _t('step_customize', 'Customize') }}</span>
            <span class="or-step-sub">{{ _t('step_extras_options', 'Extras & options') }}</span>
          </div>
        </div>

        <div class="or-step-line flex-1 mx-3"><div class="or-step-line-fill" :style="{ width: bookingStep === 'checkout' ? '100%' : '0%' }"></div></div>

        <div class="flex items-center gap-2.5">
          <div class="or-step-dot" :class="bookingStep === 'checkout' ? 'or-step-active' : 'or-step-pending'">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
          </div>
          <div class="hidden sm:block">
            <span class="or-step-label" :class="bookingStep === 'checkout' ? 'text-[#153b4f]' : 'text-gray-400'">{{ _t('step_checkout', 'Checkout') }}</span>
            <span class="or-step-sub">{{ _t('step_secure_payment', 'Secure payment') }}</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="or-page">
    <section v-if="bookingStep === 'results'" class="or-body">
      <div class="or-offer-container or-body-inner">
        <div v-if="isExpired" class="or-alert">
          <div class="or-alert-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          </div>
          <div class="or-alert-body">
            <p class="or-alert-eyebrow">{{ _t('offer_expired', 'Offer expired') }}</p>
            <p class="or-alert-message">{{ quoteStatus.message || _t('offer_expired_message', 'This offer has expired. Run the search again to see current prices and availability.') }}</p>
          </div>
          <Link v-if="quoteStatus.search_again_url" :href="quoteStatus.search_again_url" class="or-alert-btn">
            {{ _t('search_again', 'Search again') }}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
          </Link>
        </div>

        <div class="or-grid">
          <div class="or-col-main">
            <article class="or-vehicle">
              <div class="or-vehicle-media">
                <span class="or-car-badge">{{ _t('selected_offer', 'Selected offer') }}</span>
                <img v-if="vehicle.image_url" :src="vehicle.image_url" :alt="displayName || _t('vehicle_image', 'Vehicle image')" />
                <div v-else class="or-vehicle-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H5.24a2 2 0 00-1.8 1.1l-.8 1.63A6 6 0 002 12.42V16h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                  <span>{{ _t('no_image_available', 'No image available') }}</span>
                </div>
              </div>

              <div class="or-vehicle-body">
                <div class="or-supplier-row">
                  <span class="or-supplier"><span class="or-supplier-mark">{{ supplierInitials }}</span>{{ supplierDisplayName }}</span>
                  <span class="or-rating">{{ offerAvailabilityText }}</span>
                </div>
                <h2 class="or-car-title">{{ displayName || _t('vehicle_offer', 'Vehicle offer') }}</h2>
                <p class="or-car-subtitle">
                  {{ _t('offer_review_copy', 'Review the selected vehicle, supplier, and key specs before checkout.') }}
                </p>

                <div class="or-spec-grid">
                  <div class="or-spec">
                    <span class="or-spec-icon or-icon-seat" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 19v-6a4 4 0 0 1 8 0v6"/><path d="M5 21h14"/><circle cx="12" cy="6" r="3"/></svg>
                    </span>
                    <span class="or-spec-text">
                      <span>{{ _t('seats', 'Seats') }}</span>
                      <strong>{{ vehicle.seats != null ? vehicle.seats : _t('not_applicable', 'N/A') }}</strong>
                    </span>
                  </div>
                  <div class="or-spec">
                    <span class="or-spec-icon or-icon-lug" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="7" width="14" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/><path d="M9 12v4M15 12v4"/></svg>
                    </span>
                    <span class="or-spec-text">
                      <span>{{ _t('bags', 'Bags') }}</span>
                      <strong>{{ luggageSummary }}</strong>
                    </span>
                  </div>
                  <div class="or-spec">
                    <span class="or-spec-icon or-icon-trans" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><path d="M8 6h8"/><path d="M6 8v8"/><path d="M18 8v3a3 3 0 0 1-3 3H6"/></svg>
                    </span>
                    <span class="or-spec-text">
                      <span>{{ _t('gearbox', 'Gearbox') }}</span>
                      <strong>{{ transmissionSummary }}</strong>
                    </span>
                  </div>
                  <div class="or-spec">
                    <span class="or-spec-icon or-icon-mileage" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 15a8 8 0 0 1 16 0"/><path d="M12 15l4-5"/><path d="M7 15h10"/><path d="M5 19h14"/></svg>
                    </span>
                    <span class="or-spec-text">
                      <span>{{ _t('mileage', 'Mileage') }}</span>
                      <strong>{{ mileageSummary }}</strong>
                    </span>
                  </div>
                </div>

                <div class="or-confidence" aria-label="Checkout confidence">
                  <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7 10 17l-5-5"/></svg>
                    {{ _t('selected_car_retained', 'Selected car retained') }}
                  </span>
                  <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8z"/><path d="m9 12 2 2 4-4"/></svg>
                    {{ _t('partner_tracking_preserved', 'Partner tracking preserved') }}
                  </span>
                  <span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/></svg>
                    {{ _t('stripe_checkout_next', 'Stripe checkout next') }}
                  </span>
                </div>
              </div>
            </article>

            <section class="or-panel or-trip-review" aria-label="Trip and pickup details">
              <div class="or-section-head">
                <div>
                  <span class="or-section-label">{{ _t('trip_details', 'Trip details') }}</span>
                  <h3>{{ _t('pickup_return_and_policies', 'Pickup, return, and counter policies') }}</h3>
                  <p>{{ _t('trip_details_single_place', 'Check timings, office address, and counter terms before payment.') }}</p>
                </div>
              </div>
              <div class="or-trip-detail-grid">
                <div class="or-trip-detail">
                  <span class="or-trip-detail-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M16 3v4M8 3v4M4 10h16"/></svg>
                  </span>
                  <span class="or-trip-detail-copy">
                    <small>{{ _t('rental_dates', 'Rental dates') }}</small>
                    <strong>{{ formatDateTime(search.pickup_date, search.pickup_time) }}</strong>
                    <em>{{ formatDateTime(search.dropoff_date, search.dropoff_time) }} · {{ rentalSummary }}</em>
                  </span>
                </div>
                <div class="or-trip-detail">
                  <span class="or-trip-detail-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 4.5-8 12-8 12S4 14.5 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.6"/></svg>
                  </span>
                  <span class="or-trip-detail-copy">
                    <small>{{ _t('pickup_office', 'Pickup office') }}</small>
                    <strong>{{ pickupLocation.name || _t('not_specified', 'Not specified') }}</strong>
                    <em>{{ pickupLocation.address || pickupLocation.pickup_instructions || formatLocationMeta(pickupLocation) }}</em>
                    <em v-if="pickupLocation.phone || formatCoordinate(pickupLocation)">{{ [pickupLocation.phone, formatCoordinate(pickupLocation)].filter(Boolean).join(' / ') }}</em>
                  </span>
                </div>
                <div class="or-trip-detail">
                  <span class="or-trip-detail-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-15-6.7L3 13"/></svg>
                  </span>
                  <span class="or-trip-detail-copy">
                    <small>{{ _t('return_office', 'Return office') }}</small>
                    <strong>{{ dropoffLocation.name || pickupLocation.name || _t('same_as_pickup', 'Same as pickup') }}</strong>
                    <em>{{ dropoffLocation.address || dropoffLocation.dropoff_instructions || pickupLocation.address || _t('return_same_office', 'Return the car to the selected office.') }}</em>
                  </span>
                </div>
                <div class="or-trip-detail">
                  <span class="or-trip-detail-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>
                  </span>
                  <span class="or-trip-detail-copy">
                    <small>{{ _t('counter_policies', 'Counter policies') }}</small>
                    <strong>{{ _t('fuel_policy_value', 'Fuel policy: :policy', { policy: fuelPolicySummary }) }}</strong>
                    <em>{{ _t('mileage_value', 'Mileage: :value', { value: mileageSummary }) }}</em>
                    <em>{{ _t('deposit_value', 'Deposit: :value', { value: formatDisplayAmount(pricing.deposit_amount, pricing.deposit_currency || pricing.currency || search.currency) }) }}</em>
                  </span>
                </div>
              </div>
            </section>

            <div class="or-post-grid">
            <section class="or-panel or-includes-panel">
              <div class="or-section-head">
                <div>
                  <span class="or-section-label">{{ _t('offer_details', 'Offer details') }}</span>
                  <h3>{{ _t('what_this_offer_includes', 'What this offer includes') }}</h3>
                  <p>{{ _t('offer_includes_single_place', 'Package, protection, extras, and charges are grouped here for a quick scan.') }}</p>
                </div>
              </div>
              <div class="or-include-groups">
                <div class="or-include-group">
                  <div class="or-include-head">
                    <span class="or-include-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 17h14l-1.5-5.5A2 2 0 0 0 15.6 10H8.4a2 2 0 0 0-1.9 1.5L5 17Z"/><path d="M7 17v2M17 17v2"/><path d="M8 14h8"/></svg>
                    </span>
                    <div>
                      <small>{{ _t('rental_package', 'Rental package') }}</small>
                      <strong>{{ featuredProduct?.name || _t('standard_offer', 'Standard offer') }}</strong>
                    </div>
                  </div>
                  <ul class="or-include-list">
                    <li v-for="fact in offerPackageFacts" :key="fact.key">
                      <span>{{ fact.label }}</span>
                      <em>{{ fact.detail }}</em>
                    </li>
                    <li v-if="offerPackageFacts.length === 0">
                      <span>{{ featuredProduct?.name || _t('standard_offer', 'Standard offer') }}</span>
                      <em>{{ featuredProduct?.subtitle || _t('standard_supplier_cover', 'Standard supplier cover') }}</em>
                    </li>
                  </ul>
                </div>

                <div class="or-include-group">
                  <div class="or-include-head">
                    <span class="or-include-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8Z"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <div>
                      <small>{{ _t('protection', 'Protection') }}</small>
                      <strong>{{ _t('cover_and_deposit', 'Cover and deposit') }}</strong>
                    </div>
                  </div>
                  <ul class="or-include-list">
                    <li v-for="fact in offerProtectionFacts" :key="fact.key">
                      <span>{{ fact.label }}</span>
                      <em>{{ fact.detail }}</em>
                    </li>
                    <li v-if="offerProtectionFacts.length === 0">
                      <span>{{ _t('supplier_cover', 'Supplier cover') }}</span>
                      <em>{{ _t('available_on_offer', 'Available on this offer') }}</em>
                    </li>
                    <li>
                      <span>{{ _t('security_deposit', 'Security deposit') }}</span>
                      <em>{{ formatDisplayAmount(pricing.deposit_amount, pricing.deposit_currency || pricing.currency || search.currency) }}</em>
                    </li>
                  </ul>
                </div>

                <div class="or-include-group">
                  <div class="or-include-head">
                    <span class="or-include-icon" aria-hidden="true">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m17 19-5 3-5-3"/><path d="M5 12h14"/></svg>
                    </span>
                    <div>
                      <small>{{ _t('extras', 'Extras') }}</small>
                      <strong>{{ _t('addons_and_inclusions', 'Add-ons and inclusions') }}</strong>
                    </div>
                  </div>
                  <ul class="or-include-list">
                    <li v-for="fact in offerExtraFacts" :key="fact.key">
                      <span>{{ fact.label }}</span>
                      <em>{{ fact.detail }}</em>
                    </li>
                    <li v-if="hasFreeEsim">
                      <span>{{ _t('free_esim', 'Free eSIM') }}</span>
                      <em>{{ _t('included', 'Included') }}</em>
                    </li>
                    <li v-if="offerExtraFacts.length === 0 && !hasFreeEsim">
                      <span>{{ _t('no_required_extras', 'No required add-ons') }}</span>
                      <em>{{ _t('included', 'Included') }}</em>
                    </li>
                  </ul>
                </div>
              </div>
            </section>

            <section v-if="alternativeQuotes.length > 0" class="or-panel or-panel-pad or-related-panel">
              <div class="or-section-head">
                <div>
                  <span class="or-section-label">{{ _t('alternatives', 'Alternatives') }}</span>
                  <h3>{{ _t('you_might_also_like', 'You might also like') }}</h3>
                  <p>{{ _t('selected_offer_stays_hero', 'Choose another available car without losing partner tracking.') }}</p>
                </div>
                <span class="or-count">{{ visibleAlternativeQuotes.length }} / {{ alternativeQuotes.length }}</span>
              </div>

              <div v-if="alternativeQuotes.length > 0" class="or-related-grid">
                <CarListingCard
                  v-for="offer in visibleAlternativeQuotes"
                  :key="offer.quote_id"
                  :vehicle="quoteCardVehicle(offer)"
                  :form="relatedSearchForm"
                  view-mode="grid"
                  :favorite-status="false"
                  :favorite-loading="false"
                  :pop-effect="false"
                  @select-package="handleRelatedCardSelection(offer.quote_id, $event)"
                >
                  <template #dailyPrice>
                    <span>{{ quoteDailyPrice(offer) }}</span>
                  </template>
                </CarListingCard>
              </div>

              <div v-if="canLoadMoreAlternativeQuotes" class="or-load-more-wrap">
                <button type="button" class="or-load-more" @click="loadMoreAlternativeQuotes">
                  {{ _t('load_more', 'Load more') }}
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                </button>
              </div>
            </section>
          </div>
          </div>

          <aside class="or-col-side">
            <div class="or-summary">
              <div class="or-summary-top">
                <div class="or-summary-status">
                  <span>{{ _t('checkout_summary', 'Checkout summary') }}</span>
                  <strong>{{ dayCountText(currentBookingContext?.number_of_days || 1) }}</strong>
                </div>
                <span class="or-summary-eyebrow">{{ _t('total_rental_price', 'Total rental price') }}</span>
                <strong class="or-summary-total">{{ formatDisplayAmount(pricing.total_price, pricing.currency || search.currency) }}</strong>
                <p class="or-summary-days">{{ forDayCountText(currentBookingContext?.number_of_days || 1) }}</p>
              </div>

              <div class="or-summary-body">
                <div v-if="featuredProduct" class="or-summary-row">
                  <span class="or-summary-row-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 17h14l-1.5-5.5A2 2 0 0 0 15.6 10H8.4a2 2 0 0 0-1.9 1.5L5 17Z"/><path d="M7 17v2M17 17v2"/><path d="M8 14h8"/></svg>
                  </span>
                  <span class="or-summary-row-copy">
                    <span class="or-summary-row-label">{{ featuredProduct.name || _t('standard_offer', 'Standard offer') }}</span>
                  </span>
                  <strong>{{ formatDisplayAmount(featuredProduct.total ?? pricing.total_price, featuredProduct.currency || pricing.currency || search.currency) }}</strong>
                </div>
                <div v-for="(extra, index) in offerExtras.slice(0, 1)" :key="`summary-extra-${stringValue(extra.id) || stringValue(extra.code) || index}`" class="or-summary-row">
                  <span class="or-summary-row-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 2v20"/><path d="m17 5-5-3-5 3"/><path d="m17 19-5 3-5-3"/><path d="M5 12h14"/></svg>
                  </span>
                  <span class="or-summary-row-copy">
                    <span class="or-summary-row-label">{{ stringValue(extra.name) || stringValue(extra.code) || _t('extra', 'Extra') }}</span>
                    <small v-if="stringValue(extra.description)">{{ stringValue(extra.description) }}</small>
                  </span>
                  <strong>{{ formatFactAmount(extra.total_for_booking ?? extra.total_price ?? extra.price ?? extra.amount, extra.currency) || _t('included', 'Included') }}</strong>
                </div>
                <div class="or-summary-row">
                  <span class="or-summary-row-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M16 12h2"/><path d="M7 10h5"/></svg>
                  </span>
                  <span class="or-summary-row-copy">
                    <span class="or-summary-row-label">{{ _t('pay_on_arrival', 'Pay on arrival') }}</span>
                    <small>{{ _t('paid_at_counter', 'Paid at pickup counter') }}</small>
                  </span>
                  <strong>{{ formatDisplayAmount(estimatedPayLaterAmount, pricing.currency || search.currency) }}</strong>
                </div>
                <div class="or-due-card">
                  <span>{{ _t('pay_now_percent', 'Pay now :percent%', { percent: paymentPercentage }) }}</span>
                  <strong>{{ formatDisplayAmount(estimatedPayNowAmount, pricing.currency || search.currency) }}</strong>
                </div>

                <button type="button" class="or-btn-primary or-btn-block" :disabled="!canBookQuote(quote.quote_id)" @click="startBooking(quote.quote_id)">
                  <span>{{ isExpired ? _t('offer_expired', 'Offer expired') : _t('continue_to_checkout', 'Continue to checkout') }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </button>

                <p class="or-summary-note">
                  {{ _t('secure_checkout_package_note', 'Stripe protected payment. Supplier booking is created only after payment confirmation.') }}
                </p>
              </div>
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
      :search-session-id="checkoutSearchSessionId"
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
      :search-session-id="checkoutSearchSessionId"
      :gateway-search-id="checkoutGatewaySearchId"
      :provider-pickup-id="currentBookingContext.provider_pickup_id ?? selectedVehicle.provider_pickup_id ?? null"
      :unified-location-id="currentBookingContext.unified_location_id ?? selectedVehicle.unified_location_id ?? currentBookingContext.location_details?.unified_location_id ?? null"
      :dropoff-unified-location-id="currentBookingContext.dropoff_unified_location_id ?? selectedVehicle.dropoff_unified_location_id ?? currentBookingContext.dropoff_location_details?.unified_location_id ?? null"
      :driver-age="currentBookingContext.driver_age ?? selectedVehicle.driver_age ?? search.driver_age ?? null"
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
  font-family: "IBM Plex Sans", sans-serif;
  background: #f5f7fa;
  min-height: 60vh;
}

/* â”€â”€ Stepper bar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
.or-stepper-bar {
  background: linear-gradient(135deg, #ffffff 0%, #f0f4f8 100%);
  border-bottom: 1px solid #e2e8f0;
  padding: 14px 0;
  position: sticky;
  top: 0;
  z-index: 10;
  backdrop-filter: blur(10px);
}
.or-step-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: background 0.4s cubic-bezier(0.22, 1, 0.36, 1), color 0.4s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.4s cubic-bezier(0.22, 1, 0.36, 1); }
.or-step-done { background: linear-gradient(135deg, #059669, #10b981); color: #fff; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.3); }
.or-step-active { background: linear-gradient(135deg, #153b4f, #1c4d66); color: #fff; box-shadow: 0 2px 12px rgba(21, 59, 79, 0.35); }
.or-step-pending { background: #e2e8f0; color: #94a3b8; }
.or-step-label { display: block; font-size: 13px; font-weight: 700; line-height: 1.2; letter-spacing: 0; }
.or-step-sub { display: block; font-size: 11px; font-weight: 500; color: #94a3b8; line-height: 1.3; }
.or-step-line { height: 3px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; }
.or-step-line-fill { height: 100%; background: linear-gradient(90deg, #059669, #10b981); border-radius: 9999px; transition: width 0.6s cubic-bezier(0.22, 1, 0.36, 1); }

/* â”€â”€ Hero header â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
  letter-spacing: 0;
  text-transform: uppercase;
  color: #22d3ee;
  margin-bottom: 8px;
}
.or-hero-title h1 {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2rem;
  font-weight: 700;
  line-height: 1.15;
  color: #fff;
  margin: 0 0 8px;
  letter-spacing: 0;
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
.or-date-label { font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: rgba(255, 255, 255, 0.5); }
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

/* â”€â”€ Body â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
.or-alert-eyebrow { font-size: 11px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #92400e; margin: 0 0 4px; }
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

/* â”€â”€ Vehicle hero card â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
  display: flex;
  padding: 16px;
  overflow: hidden;
}
.or-vehicle-media-layout {
  display: flex;
  align-items: stretch;
  gap: 16px;
  width: 100%;
}
.or-vehicle-media-image {
  flex: 1 1 auto;
  min-width: 0;
  min-height: 300px;
  border-radius: 18px;
  overflow: hidden;
  background: linear-gradient(135deg, #eef4f8 0%, #dae5ee 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}
.or-vehicle-media-image img { width: 100%; height: 100%; object-fit: contain; }
.or-vehicle-media-side {
  flex: 0 0 260px;
  display: flex;
  align-items: stretch;
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
  letter-spacing: 0;
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
  letter-spacing: 0;
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
.or-price-eyebrow { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #94a3b8; }
.or-price-value { display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.75rem; font-weight: 800; color: #153b4f; line-height: 1.1; margin-top: 4px; letter-spacing: 0; }
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
.or-spec-text span { font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #94a3b8; line-height: 1.2; }
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

/* â”€â”€ Buttons â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
  transition: background var(--or-dur) var(--or-ease), color var(--or-dur) var(--or-ease), transform var(--or-dur) var(--or-ease), box-shadow var(--or-dur) var(--or-ease);
  white-space: nowrap;
}
.or-btn-ghost:hover:not(:disabled) { background: #153b4f; color: #fff; transform: translateY(-1px); box-shadow: 0 6px 14px rgba(21, 59, 79, 0.22); }
.or-btn-ghost:disabled { opacity: 0.55; cursor: not-allowed; }
.or-btn-ghost svg { width: 14px; height: 14px; transition: transform var(--or-dur) var(--or-ease); }
.or-btn-ghost:hover:not(:disabled) svg { transform: translateX(2px); }

/* â”€â”€ Panels (Trip, Offices, Alternatives) â”€â”€ */
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
.or-label { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #94a3b8; }
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

/* â”€â”€ Price summary sidebar â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
.or-summary-eyebrow { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #94a3b8; }
.or-summary-total {
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-size: 2.25rem;
  font-weight: 800;
  color: #153b4f;
  line-height: 1.1;
  margin-top: 6px;
  letter-spacing: 0;
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
.or-summary-row-stack span { font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #94a3b8; }
.or-summary-row-stack strong { font-size: 13px; text-align: left; }

.or-product {
  margin-top: 18px;
  padding: 14px;
  background: linear-gradient(135deg, #f0f8fc 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
  border-radius: 12px;
}
.or-product-eyebrow { display: block; font-size: 10px; font-weight: 700; letter-spacing: 0; text-transform: uppercase; color: #0e7490; }
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

/* â”€â”€ Mobile â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
@media (max-width: 1023px) {
  .or-hero { padding: 22px 0 30px; }
  .or-hero-grid { flex-direction: column; align-items: flex-start; }
  .or-hero-dates { width: 100%; justify-content: space-between; }
  .or-body { padding: 20px 0 40px; }
  .or-vehicle-media-layout {
    flex-direction: column;
    gap: 12px;
  }
  .or-vehicle-media-image {
    min-height: 240px;
  }
  .or-vehicle-media-side {
    flex: 0 0 auto;
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

/* Approved offer-page redesign */
.or-page {
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 44%, #f1f5f9 100%);
  overflow-x: hidden;
  position: relative;
  z-index: 2;
}

.or-offer-container {
  width: min(92%, 1200px);
  margin-inline: auto;
}

.or-partner-band {
  position: relative;
  color: #fff;
  background:
    radial-gradient(120% 120% at 12% 8%, rgba(34, 211, 238, 0.18), transparent 42%),
    radial-gradient(90% 90% at 88% 86%, rgba(15, 23, 42, 0.5), transparent 55%),
    linear-gradient(135deg, #0b2230 0%, #153b4f 46%, #0b1b26 100%);
  overflow: hidden;
}

.or-partner-band::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.035) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.035) 1px, transparent 1px);
  background-size: 46px 46px;
  mask-image: radial-gradient(120% 80% at 50% 0%, #000 35%, transparent 78%);
  pointer-events: none;
}

.or-partner-inner {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
  padding-block: 40px 76px;
}

.or-partner-inner > * {
  min-width: 0;
}

.or-crumbs {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  color: rgba(255, 255, 255, 0.66);
  font-size: 0.88rem;
  margin-bottom: 18px;
}

.or-crumbs a {
  color: rgba(255, 255, 255, 0.78);
  text-decoration: none;
  transition: color 0.25s var(--or-ease);
}

.or-crumbs a:hover {
  color: #22d3ee;
}

.or-crumbs svg {
  width: 14px;
  height: 14px;
  flex: 0 0 auto;
}

.or-source-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  width: fit-content;
  padding: 7px 13px;
  border: 1px solid rgba(34, 211, 238, 0.28);
  border-radius: 999px;
  color: #22d3ee;
  background: rgba(34, 211, 238, 0.08);
  font-size: 0.74rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-source-pill svg {
  width: 14px;
  height: 14px;
}

.or-hero-title {
  max-width: 18ch;
  margin: 18px 0 0;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 3rem;
  line-height: 1.06;
  font-weight: 800;
  letter-spacing: 0;
  color: #fff;
  text-wrap: balance;
  overflow-wrap: anywhere;
}

.or-hero-title em {
  display: block;
  font-style: normal;
  color: #22d3ee;
}

.or-hero-sub {
  max-width: 58ch;
  margin: 14px 0 0;
  color: rgba(255, 255, 255, 0.76);
  font-size: 1rem;
  line-height: 1.65;
  text-wrap: pretty;
}

.or-trip-strip {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
  align-self: end;
  padding: 12px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.06);
  backdrop-filter: blur(18px) saturate(1.25);
  min-width: 0;
}

.or-trip-pill {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 10px;
  align-items: center;
  padding: 12px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.06);
  min-width: 0;
}

.or-trip-pill > svg {
  width: 18px;
  height: 18px;
  color: #22d3ee;
}

.or-trip-pill span {
  display: block;
  color: rgba(255, 255, 255, 0.54);
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-trip-pill strong {
  display: block;
  margin-top: 3px;
  color: #fff;
  font-size: 0.92rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}

.or-body {
  position: relative;
  z-index: 2;
  margin-top: 24px;
  padding: 0 0 72px;
}

.or-body-inner {
  display: grid;
  gap: 18px;
}

.or-notice-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  position: relative;
  z-index: 4;
  gap: 14px;
  padding: 14px 16px;
  border: 1px solid rgba(34, 211, 238, 0.22);
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(207, 250, 254, 0.92), rgba(255, 255, 255, 0.98));
  box-shadow: var(--or-shadow-lg);
  min-width: 0;
}

.or-notice-copy {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
  color: #153b4f;
  font-weight: 600;
}

.or-notice-copy svg {
  width: 20px;
  height: 20px;
  color: #0891b2;
  flex: 0 0 auto;
}

.or-notice-meta {
  color: #475569;
  font-size: 0.92rem;
}

.or-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 22px;
  align-items: start;
}

.or-col-main {
  display: grid;
  gap: 18px;
  min-width: 0;
}

.or-panel,
.or-vehicle {
  border: 1px solid rgba(226, 232, 240, 0.95);
  border-radius: 18px;
  background: #fff;
  box-shadow: var(--or-shadow-sm);
  min-width: 0;
}

.or-panel-pad {
  padding: 20px;
}

.or-vehicle {
  position: relative;
  display: grid;
  grid-template-columns: 1fr;
  overflow: hidden;
  border-color: rgba(21, 59, 79, 0.16);
  box-shadow: var(--or-shadow-lg);
}

.or-vehicle::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1;
  height: 4px;
  background: linear-gradient(90deg, #153b4f 0%, #22d3ee 100%);
}

.or-vehicle-media {
  position: relative;
  min-height: 360px;
  display: grid;
  place-items: center;
  padding: 28px;
  border-bottom: 1px solid #e2e8f0;
  background:
    radial-gradient(80% 90% at 50% 30%, rgba(34, 211, 238, 0.14), transparent 60%),
    linear-gradient(135deg, #eef6fa 0%, #f8fafc 52%, #dceef6 100%);
}

.or-vehicle-media img {
  width: min(100%, 860px);
  height: 100%;
  max-height: 390px;
  object-fit: cover;
  border-radius: 16px;
  box-shadow: 0 22px 48px rgba(21, 59, 79, 0.16);
}

.or-vehicle-empty {
  display: grid;
  place-items: center;
  gap: 8px;
  color: #64748b;
  text-align: center;
}

.or-vehicle-empty svg {
  width: 56px;
  height: 56px;
}

.or-vehicle-body {
  padding: 24px;
}

.or-badge-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
  min-width: 0;
}

.or-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 11px;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #f8fafc;
  color: #334155;
  font-size: 0.8rem;
  font-weight: 600;
}

.or-badge svg {
  width: 14px;
  height: 14px;
}

.or-badge-good {
  border-color: rgba(16, 185, 129, 0.24);
  background: rgba(16, 185, 129, 0.09);
  color: #047857;
}

.or-badge-cyan {
  border-color: rgba(34, 211, 238, 0.28);
  background: rgba(34, 211, 238, 0.1);
  color: #0e7490;
}

.or-vehicle-head {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 16px;
  margin-bottom: 20px;
}

.or-vehicle-title h2 {
  margin: 0;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1.4rem;
  line-height: 1.2;
  font-weight: 700;
  color: #153b4f;
  letter-spacing: 0;
  text-wrap: balance;
  overflow-wrap: anywhere;
}

.or-vehicle-title p {
  display: flex;
  align-items: center;
  gap: 8px;
  margin: 9px 0 0;
  min-width: 0;
  color: #64748b;
  font-size: 0.92rem;
  font-weight: 500;
  overflow-wrap: anywhere;
}

.or-vehicle-title p svg {
  width: 16px;
  height: 16px;
  color: #94a3b8;
  flex: 0 0 auto;
}

.or-price-block {
  width: 100%;
  padding: 15px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #f8fafc;
}

.or-price-block span {
  display: block;
  color: #64748b;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-price-block strong {
  display: block;
  margin-top: 5px;
  color: #153b4f;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: 0;
  line-height: 1.05;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}

.or-price-block small {
  display: block;
  margin-top: 5px;
  color: #64748b;
  font-size: 0.86rem;
  font-weight: 500;
  font-variant-numeric: tabular-nums;
}

.or-spec-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  min-width: 0;
  margin-top: 0;
}

.or-spec {
  display: grid;
  grid-template-columns: 38px minmax(0, 1fr);
  gap: 10px;
  align-items: center;
  min-height: 70px;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
  transition: transform 0.3s var(--or-ease), border-color 0.3s var(--or-ease), background 0.3s var(--or-ease);
}

.or-spec:hover {
  transform: translateY(-1px);
  border-color: rgba(21, 59, 79, 0.34);
  background: #f0f8fc;
}

.or-spec-icon {
  width: 38px;
  height: 38px;
  display: grid;
  place-items: center;
  border-radius: 12px;
}

.or-spec-icon svg {
  width: 18px;
  height: 18px;
}

.or-icon-seat,
.or-icon-mileage {
  background: #dceef6;
  color: #153b4f;
}

.or-spec-text {
  min-width: 0;
}

.or-spec-text span {
  display: block;
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-spec-text strong {
  display: block;
  margin-top: 3px;
  color: #1e293b;
  font-size: 0.92rem;
  font-weight: 600;
  line-height: 1.28;
  overflow-wrap: anywhere;
}

.or-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-bottom: 16px;
}

.or-section-head h3 {
  margin: 0;
  font-family: "Plus Jakarta Sans", sans-serif;
  color: #153b4f;
  font-size: 1.12rem;
  font-weight: 700;
  letter-spacing: 0;
}

.or-section-head p {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.5;
}

.or-office-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px;
}

.or-office-card {
  display: grid;
  gap: 10px;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #f8fafc;
}

.or-office-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
}

.or-office-card h4 {
  margin: 0;
  color: #0f172a;
  font-size: 0.98rem;
  font-weight: 600;
}

.or-office-card p {
  margin: 5px 0 0;
  color: #64748b;
  font-size: 0.92rem;
  line-height: 1.55;
}

.or-office-type {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 9px;
  border-radius: 999px;
  color: #047857;
  background: rgba(16, 185, 129, 0.1);
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-office-type svg {
  width: 13px;
  height: 13px;
}

.or-mini-data {
  display: grid;
  grid-template-columns: 1fr;
  gap: 8px;
  margin-top: 4px;
}

.or-mini-data span {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #475569;
  font-size: 0.92rem;
  overflow-wrap: anywhere;
}

.or-mini-data svg {
  width: 15px;
  height: 15px;
  color: #153b4f;
  flex: 0 0 auto;
}

.or-included-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
}

.or-included {
  display: grid;
  grid-template-columns: 40px minmax(0, 1fr);
  gap: 12px;
  align-items: start;
  padding: 16px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
}

.or-included > svg {
  width: 40px;
  height: 40px;
  padding: 10px;
  border-radius: 12px;
  color: #153b4f;
  background: #dceef6;
}

.or-included strong {
  display: block;
  color: #0f172a;
  font-size: 0.96rem;
  font-weight: 600;
}

.or-included span {
  display: block;
  margin-top: 4px;
  color: #64748b;
  font-size: 0.9rem;
  line-height: 1.5;
}

.or-alts {
  display: grid;
  gap: 10px;
}

.or-alt {
  display: grid;
  grid-template-columns: 86px minmax(0, 1fr);
  gap: 12px;
  align-items: center;
  padding: 12px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
  transition: transform 0.3s var(--or-ease), border-color 0.3s var(--or-ease), box-shadow 0.3s var(--or-ease);
}

.or-alt:hover {
  transform: translateY(-2px);
  border-color: rgba(21, 59, 79, 0.32);
  box-shadow: var(--or-shadow-sm);
}

.or-alt-media {
  width: 86px;
  aspect-ratio: 4 / 3;
  display: grid;
  place-items: center;
  overflow: hidden;
  border-radius: 12px;
  background: #f1f5f9;
  color: #94a3b8;
}

.or-alt-media img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.or-alt-info {
  min-width: 0;
}

.or-alt-info h4 {
  margin: 0;
  color: #0f172a;
  font-size: 0.96rem;
  font-weight: 600;
  overflow-wrap: anywhere;
}

.or-alt-info p {
  display: -webkit-box;
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.86rem;
  overflow: hidden;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.or-alt-price {
  display: flex;
  grid-column: 1 / -1;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  text-align: left;
}

.or-alt-price strong {
  color: #153b4f;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-weight: 700;
  letter-spacing: 0;
  font-variant-numeric: tabular-nums;
}

.or-alt-price span {
  display: none;
}

.or-icon-action {
  min-width: 44px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: 1px solid #153b4f;
  border-radius: 12px;
  background: #fff;
  color: #153b4f;
  font-size: 0.86rem;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.3s var(--or-ease), border-color 0.3s var(--or-ease), box-shadow 0.3s var(--or-ease), background 0.3s var(--or-ease), color 0.3s var(--or-ease);
}

.or-icon-action:hover:not(:disabled) {
  transform: translateY(-1px);
  background: #153b4f;
  color: #fff;
  box-shadow: var(--or-shadow-sm);
}

.or-icon-action:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.or-icon-action svg {
  width: 16px;
  height: 16px;
}

.or-summary {
  position: sticky;
  top: 94px;
  overflow: hidden;
  padding: 0;
  border: 1px solid rgba(21, 59, 79, 0.14);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 24px 56px rgba(21, 59, 79, 0.2), 0 6px 14px rgba(21, 59, 79, 0.08);
}

.or-summary::before {
  content: none;
}

.or-summary-top {
  position: relative;
  padding: 24px 22px 26px;
  color: #fff;
  overflow: hidden;
  background:
    radial-gradient(90% 120% at 85% 0%, rgba(34, 211, 238, 0.28), transparent 55%),
    linear-gradient(135deg, #0b2230 0%, #153b4f 55%, #0b1b26 100%);
}

.or-summary-eyebrow {
  display: block;
  color: #22d3ee;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-summary-total {
  display: block;
  margin-top: 10px;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 2.15rem;
  font-weight: 800;
  line-height: 1;
  letter-spacing: 0;
  color: #fff;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}

.or-summary-days {
  display: flex;
  align-items: center;
  gap: 7px;
  margin: 11px 0 0;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.86rem;
  font-weight: 500;
}

.or-summary-days svg {
  width: 14px;
  height: 14px;
  color: #22d3ee;
}

.or-summary-body {
  display: grid;
  gap: 14px;
  padding: 18px;
}

.or-summary-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 14px;
  color: #64748b;
  font-size: 0.92rem;
}

.or-summary-row strong {
  color: #1e293b;
  font-weight: 600;
  text-align: right;
  font-variant-numeric: tabular-nums;
}

.or-product {
  margin-top: 2px;
  padding: 13px;
  border: 1px solid #bae6fd;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0f8fc 0%, #e0f2fe 100%);
}

.or-product-eyebrow {
  display: block;
  color: #0e7490;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0;
}

.or-product-name {
  margin: 4px 0 0;
  color: #153b4f;
  font-size: 0.9rem;
  font-weight: 700;
}

.or-product-sub {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.4;
}

.or-btn-primary {
  min-height: 54px;
  border-radius: 14px;
  background: linear-gradient(135deg, #153b4f, #1c4d66);
  transition: transform 0.3s var(--or-ease), box-shadow 0.3s var(--or-ease), background 0.3s var(--or-ease);
}

.or-btn-primary:hover:not(:disabled) {
  box-shadow: 0 16px 32px rgba(21, 59, 79, 0.3), 0 0 0 4px rgba(34, 211, 238, 0.22);
}

.or-summary-note {
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 10px;
  justify-content: initial;
  width: auto;
  margin: 0;
  padding: 12px;
  border-radius: 14px;
  background: #f8fafc;
  color: #475569;
  font-size: 0.9rem;
  line-height: 1.45;
}

.or-summary-note svg {
  width: 34px;
  height: 34px;
  padding: 8px;
  border-radius: 10px;
  color: #047857;
  background: rgba(16, 185, 129, 0.1);
}

.or-alert {
  border-radius: 16px;
}

@keyframes orFadeUp {
  from {
    opacity: 0;
    transform: translateY(18px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}

.or-partner-copy,
.or-trip-strip,
.or-notice-row,
.or-col-main > *,
.or-summary {
  animation: orFadeUp 0.7s var(--or-ease) both;
}

.or-trip-strip {
  animation-delay: 0.12s;
}

.or-notice-row {
  animation-delay: 0.18s;
}

.or-col-main > *:nth-child(1) {
  animation-delay: 0.24s;
}

.or-col-main > *:nth-child(2) {
  animation-delay: 0.3s;
}

.or-col-main > *:nth-child(3) {
  animation-delay: 0.36s;
}

.or-col-main > *:nth-child(4) {
  animation-delay: 0.42s;
}

.or-summary {
  animation-delay: 0.28s;
}

@media (min-width: 640px) {
  .or-trip-strip {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .or-spec-grid,
  .or-included-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .or-mini-data {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .or-alt {
    grid-template-columns: 96px minmax(0, 1fr) auto;
  }

  .or-alt-price {
    grid-column: auto;
    flex-direction: column;
    align-items: flex-end;
  }
}

@media (min-width: 900px) {
  .or-partner-inner {
    grid-template-columns: minmax(0, 1fr) 500px;
    align-items: end;
    padding-block: 52px 84px;
  }

  .or-grid {
    grid-template-columns: minmax(0, 1fr) 340px;
  }

  .or-vehicle-head {
    grid-template-columns: minmax(0, 1fr) 260px;
    align-items: start;
  }

  .or-price-block {
    text-align: right;
  }

  .or-office-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1180px) {
  .or-grid {
    grid-template-columns: minmax(0, 1fr) 360px;
  }

  .or-spec-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 899px) {
  .or-summary {
    position: static;
  }

  .or-hero-title {
    font-size: 2.25rem;
  }

  :global(.floating-widget) {
    display: none !important;
  }
}

@media (max-width: 520px) {
  .or-offer-container,
  .full-w-container {
    width: min(94%, 1440px);
  }

  .or-vehicle-body,
  .or-panel-pad,
  .or-summary-body,
  .or-summary-top {
    padding: 16px;
  }

  .or-vehicle-media {
    min-height: 260px;
    padding: 16px;
  }

  .or-spec-grid {
    grid-template-columns: 1fr;
  }

  .or-notice-copy {
    align-items: flex-start;
  }

  .or-office-top {
    flex-direction: column;
  }
}

.or-offer-container {
  width: min(92%, 1440px);
}

.or-grid {
  display: flex;
  flex-direction: column;
  gap: 22px;
  align-items: stretch;
}

.or-col-main {
  order: 1;
  display: block;
  min-width: 0;
}

.or-col-side {
  order: 2;
  min-width: 0;
}

.or-post-grid {
  order: 3;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 18px;
  flex: 1 0 100%;
  min-width: 0;
}

.or-related-panel {
  overflow: hidden;
}

.or-related-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 18px;
  min-width: 0;
}

.or-related-grid :deep(.car-card) {
  height: 100%;
  font-family: "IBM Plex Sans", sans-serif;
  letter-spacing: 0;
}

.or-related-grid :deep(.car-name),
.or-related-grid :deep(.car-price),
.or-related-grid :deep(.plan-name),
.or-related-grid :deep(.plan-daily-price),
.or-related-grid :deep(.plan-total-price),
.or-related-grid :deep(.plan-select-btn),
.or-related-grid :deep(.plans-modal h2),
.or-related-grid :deep(.header-btn.primary) {
  font-family: "Plus Jakarta Sans", sans-serif;
  letter-spacing: 0;
}

.or-related-grid :deep(.car-class),
.or-related-grid :deep(.spec-tag),
.or-related-grid :deep(.feature-tag),
.or-related-grid :deep(.car-currency),
.or-related-grid :deep(.plan-type),
.or-related-grid :deep(.plan-features li),
.or-related-grid :deep(.car-badge) {
  font-family: "IBM Plex Sans", sans-serif;
  letter-spacing: 0;
}

.or-related-grid :deep(.car-favorite) {
  display: none;
}

.or-load-more-wrap {
  display: flex;
  justify-content: center;
  margin-top: 22px;
}

.or-load-more {
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 18px;
  border: 1px solid #153b4f;
  border-radius: 999px;
  background: #fff;
  color: #153b4f;
  font-weight: 700;
  cursor: pointer;
  transition: transform 0.3s var(--or-ease), border-color 0.3s var(--or-ease), box-shadow 0.3s var(--or-ease), background 0.3s var(--or-ease), color 0.3s var(--or-ease);
}

.or-load-more:hover {
  transform: translateY(-1px);
  background: #153b4f;
  color: #fff;
  box-shadow: var(--or-shadow-sm);
}

.or-load-more:focus-visible {
  outline: 3px solid rgba(34, 211, 238, 0.45);
  outline-offset: 3px;
}

.or-load-more svg {
  width: 16px;
  height: 16px;
}

@media (min-width: 900px) {
  .or-grid {
    flex-direction: row;
    flex-wrap: wrap;
    align-items: flex-start;
  }

  .or-col-main {
    flex: 1 1 0;
  }

  .or-col-side {
    flex: 0 1 360px;
    max-width: 360px;
    align-self: flex-start;
  }
}

@media (min-width: 1180px) {
  .or-post-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .or-related-panel {
    grid-column: 1 / -1;
  }

  .or-related-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (min-width: 900px) and (max-width: 1179px) {
  .or-related-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (prefers-reduced-motion: reduce) {
  .or-partner-copy,
  .or-trip-strip,
  .or-notice-row,
  .or-post-grid,
  .or-col-main > *,
  .or-summary {
    animation: none;
  }

  .or-spec,
  .or-alt,
  .or-icon-action,
  .or-btn-primary,
  .or-load-more {
    transition-duration: 1ms;
  }
}

/* Approved live offer design */
.or-page {
  --or-brand: #153b4f;
  --or-brand-dark: #0b2230;
  --or-brand-deep: #071923;
  --or-brand-soft: #f0f8fc;
  --or-brand-2: #1c4d66;
  --or-accent: #22d3ee;
  --or-accent-dark: #0891b2;
  --or-line: #e2e8f0;
  --or-slate-50: #f8fafc;
  --or-slate-100: #f1f5f9;
  --or-slate-500: #64748b;
  --or-slate-600: #475569;
  --or-slate-700: #334155;
  --or-slate-900: #0f172a;
  --or-success: #10b981;
  --or-shadow-sm: 0 8px 24px rgba(21, 59, 79, 0.08);
  --or-shadow-md: 0 16px 40px rgba(21, 59, 79, 0.12);
  --or-shadow-lg: 0 26px 56px rgba(21, 59, 79, 0.16);
  --or-ease: cubic-bezier(0.22, 1, 0.36, 1);
  --or-dur: 0.3s;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 44%, #f1f5f9 100%);
  color: var(--or-slate-900);
  font-family: "IBM Plex Sans", sans-serif;
  overflow-x: hidden;
}

.or-offer-container {
  width: min(92%, 1440px);
  margin-inline: auto;
}

.or-partner-band {
  position: relative;
  color: #fff;
  background:
    radial-gradient(circle at 10% 16%, rgba(34, 211, 238, 0.26), transparent 34%),
    radial-gradient(circle at 80% 72%, rgba(46, 167, 173, 0.18), transparent 38%),
    linear-gradient(135deg, #0b2230 0%, #153b4f 50%, #071923 100%);
  overflow: hidden;
}

.or-partner-band::before {
  content: none;
}

.or-partner-inner {
  min-height: 340px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(340px, 430px);
  align-items: end;
  gap: clamp(28px, 6vw, 76px);
  padding-block: 38px 54px;
}

.or-partner-inner > * {
  min-width: 0;
}

.or-progress-preview {
  width: min(100%, 520px);
  height: 42px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  align-items: center;
  gap: 6px;
  padding: 5px;
  margin-bottom: 28px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.07);
  backdrop-filter: blur(18px) saturate(1.25);
}

.or-progress-preview span {
  min-width: 0;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 0 12px;
  border-radius: 999px;
  color: rgba(255, 255, 255, 0.68);
  font-size: 0.84rem;
  font-weight: 700;
  white-space: nowrap;
}

.or-progress-preview span::before {
  content: "";
  width: 7px;
  height: 7px;
  flex: 0 0 auto;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.28);
}

.or-progress-preview .is-active {
  color: #fff;
  background: rgba(255, 255, 255, 0.09);
}

.or-progress-preview .is-active::before {
  background: var(--or-accent);
  box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.14);
}

.or-source-pill {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  margin: 0 0 13px;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: var(--or-accent);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.13em;
  text-transform: uppercase;
}

.or-source-pill::before {
  content: "";
  width: 22px;
  height: 1px;
  background: var(--or-accent);
}

.or-hero-title {
  max-width: 760px;
  margin: 0;
  color: #fff;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: clamp(2.25rem, 5.4vw, 4.65rem);
  font-weight: 800;
  line-height: 1.02;
  letter-spacing: 0;
  text-wrap: balance;
}

.or-hero-sub {
  max-width: 680px;
  margin: 16px 0 0;
  color: rgba(255, 255, 255, 0.78);
  font-size: clamp(1rem, 2vw, 1.12rem);
  line-height: 1.65;
  text-wrap: pretty;
}

.or-hero-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 24px;
}

.or-hero-chips span {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 13px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.07);
  color: rgba(255, 255, 255, 0.84);
  font-size: 0.86rem;
  font-weight: 700;
  backdrop-filter: blur(14px) saturate(1.25);
}

.or-hero-chips span::before {
  content: "";
  width: 7px;
  height: 7px;
  flex: 0 0 auto;
  border-radius: 50%;
  background: var(--or-accent);
  box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.12);
}

.or-trip-card {
  padding: 20px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 22px;
  background: rgba(255, 255, 255, 0.07);
  backdrop-filter: blur(20px) saturate(1.3);
  box-shadow: 0 24px 52px rgba(0, 0, 0, 0.16);
}

.or-route-status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 16px;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.86rem;
  font-weight: 700;
}

.or-route-status strong {
  color: #fff;
  font-family: "Plus Jakarta Sans", sans-serif;
}

.or-route-row {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  gap: 14px;
}

.or-route-point span {
  display: block;
  color: rgba(255, 255, 255, 0.58);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.11em;
  text-transform: uppercase;
}

.or-route-point strong {
  display: block;
  margin-top: 5px;
  color: #fff;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1rem;
  overflow-wrap: anywhere;
}

.or-route-line {
  width: 54px;
  height: 1px;
  background: linear-gradient(90deg, rgba(34, 211, 238, 0.08), var(--or-accent), rgba(34, 211, 238, 0.08));
}

.or-route-date {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
}

.or-route-date span {
  padding: 8px 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.08);
  color: rgba(255, 255, 255, 0.82);
  font-size: 0.8rem;
  font-weight: 700;
}

.or-body {
  position: relative;
  z-index: 2;
  margin-top: 0;
  padding: 0 0 72px;
}

.or-body-inner {
  display: grid;
  gap: 34px;
}

.or-proof-strip {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  margin-top: -24px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: var(--or-shadow-md);
  overflow: hidden;
}

.or-proof-item {
  min-width: 0;
  padding: 18px 20px;
  border-right: 1px solid rgba(226, 232, 240, 0.82);
}

.or-proof-item:last-child {
  border-right: 0;
}

.or-proof-item span {
  display: block;
  color: var(--or-slate-600);
  font-size: 0.84rem;
  font-weight: 700;
}

.or-proof-item strong {
  display: block;
  margin-top: 4px;
  color: var(--or-brand);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1rem;
  overflow-wrap: anywhere;
}

.or-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 390px;
  gap: 28px;
  align-items: start;
}

.or-col-main {
  display: grid;
  gap: 22px;
  min-width: 0;
}

.or-col-side {
  min-width: 0;
  margin-top: 0;
  position: static;
  align-self: start;
}

.or-post-grid {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 22px;
  min-width: 0;
}

.or-panel,
.or-vehicle {
  min-width: 0;
  border: 1px solid rgba(226, 232, 240, 0.88);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 10px 30px rgba(21, 59, 79, 0.06);
  overflow: hidden;
}

.or-panel-pad {
  padding: 22px 24px 24px;
}

.or-vehicle {
  display: grid;
  grid-template-columns: minmax(280px, 42%) 1fr;
  border-color: rgba(21, 59, 79, 0.16);
  box-shadow: var(--or-shadow-md);
}

.or-vehicle::before {
  content: none;
}

.or-vehicle-media {
  min-height: 360px;
  position: relative;
  display: grid;
  place-items: center;
  padding: 28px;
  border-bottom: 0;
  background: linear-gradient(145deg, #eef8fb, #fff 48%, #dceef6);
  overflow: hidden;
}

.or-vehicle-media::before {
  content: "";
  position: absolute;
  inset: auto -12% -32% -12%;
  height: 58%;
  background: radial-gradient(ellipse at center, rgba(21, 59, 79, 0.18), transparent 64%);
}

.or-vehicle-media::after {
  content: "";
  position: absolute;
  inset: 24px;
  border: 1px solid rgba(21, 59, 79, 0.08);
  border-radius: 22px;
  pointer-events: none;
}

.or-vehicle-media img {
  position: relative;
  z-index: 1;
  width: min(88%, 520px);
  height: auto;
  max-height: 282px;
  object-fit: contain;
  border-radius: 0;
  box-shadow: none;
  filter: drop-shadow(0 20px 24px rgba(21, 59, 79, 0.2));
}

.or-car-badge {
  position: absolute;
  top: 18px;
  left: 18px;
  z-index: 2;
  padding: 8px 12px;
  border-radius: 999px;
  background: #fff;
  color: var(--or-brand);
  font-size: 0.78rem;
  font-weight: 800;
  box-shadow: 0 8px 18px rgba(21, 59, 79, 0.08);
}

.or-vehicle-empty {
  position: relative;
  z-index: 1;
  display: grid;
  place-items: center;
  gap: 8px;
  color: var(--or-slate-600);
  text-align: center;
}

.or-vehicle-empty svg {
  width: 56px;
  height: 56px;
}

.or-vehicle-body {
  padding: 28px;
}

.or-supplier-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.or-supplier {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: var(--or-brand);
  font-weight: 800;
}

.or-supplier-mark {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 11px;
  color: #fff;
  background: linear-gradient(135deg, var(--or-brand), #2ea7ad);
  font-size: 0.82rem;
  box-shadow: 0 10px 20px rgba(21, 59, 79, 0.14);
}

.or-rating {
  padding: 7px 10px;
  border-radius: 999px;
  color: var(--or-brand);
  background: var(--or-brand-soft);
  font-size: 0.84rem;
  font-weight: 800;
  white-space: nowrap;
}

.or-car-title {
  margin: 0;
  color: var(--or-slate-900);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: clamp(1.75rem, 3vw, 2.48rem);
  font-weight: 800;
  line-height: 1.08;
  text-wrap: balance;
}

.or-car-subtitle {
  margin: 10px 0 22px;
  color: var(--or-slate-600);
  font-size: 1rem;
  line-height: 1.55;
  text-wrap: pretty;
}

.or-spec-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  min-width: 0;
  margin-top: 0;
}

.or-spec {
  min-height: 78px;
  display: block;
  padding: 13px;
  border: 1px solid var(--or-line);
  border-radius: 14px;
  background: var(--or-slate-50);
  transition: transform var(--or-dur) var(--or-ease), border-color var(--or-dur) var(--or-ease), background-color var(--or-dur) var(--or-ease);
}

.or-spec:hover {
  transform: translateY(-1px);
  border-color: rgba(21, 59, 79, 0.34);
  background: var(--or-brand-soft);
}

.or-spec span {
  display: block;
  color: var(--or-slate-500);
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.or-spec strong {
  display: block;
  margin-top: 5px;
  color: var(--or-brand);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 0.98rem;
  line-height: 1.25;
  overflow-wrap: anywhere;
}

.or-confidence {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  margin-top: 20px;
}

.or-confidence span,
.or-summary-checks span {
  display: flex;
  align-items: center;
  gap: 9px;
  color: var(--or-slate-700);
  font-size: 0.84rem;
  font-weight: 700;
}

.or-confidence span {
  min-height: 48px;
  padding: 10px 12px;
  border: 1px solid rgba(226, 232, 240, 0.92);
  border-radius: 14px;
  background: #fff;
}

.or-confidence span::before,
.or-summary-checks span::before {
  content: "";
  width: 8px;
  height: 8px;
  flex: 0 0 auto;
  border-radius: 50%;
  background: var(--or-success);
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
}

.or-timeline {
  display: grid;
  grid-template-columns: 1fr 1fr;
}

.or-time-box {
  padding: 23px 24px;
  border-right: 1px solid var(--or-line);
}

.or-time-box:last-child {
  border-right: 0;
}

.or-section-label {
  color: var(--or-slate-500);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.or-time-box h3,
.or-section-head h3 {
  margin: 7px 0 6px;
  color: var(--or-brand);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1.12rem;
  font-weight: 800;
  line-height: 1.25;
  text-wrap: balance;
}

.or-time-box p,
.or-section-head p {
  margin: 0;
  color: var(--or-slate-600);
  line-height: 1.5;
  text-wrap: pretty;
}

.or-time-box small {
  display: block;
  margin-top: 10px;
  color: var(--or-slate-500);
  font-weight: 700;
}

.or-section-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 16px;
}

.or-included-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.or-included {
  min-height: 74px;
  display: block;
  padding: 16px;
  border: 1px solid var(--or-line);
  border-radius: 16px;
  background: #fff;
}

.or-included strong {
  display: block;
  color: var(--or-slate-900);
  font-size: 0.96rem;
  font-weight: 700;
}

.or-included span {
  display: block;
  margin-top: 4px;
  color: var(--or-slate-600);
  font-size: 0.9rem;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.or-summary {
  position: sticky;
  top: 94px;
  overflow: hidden;
  border: 1px solid rgba(21, 59, 79, 0.13);
  border-radius: 24px;
  background: #fff;
  box-shadow: var(--or-shadow-lg);
}

.or-summary-top {
  padding: 24px;
  color: #fff;
  background: linear-gradient(135deg, var(--or-brand-dark), var(--or-brand));
}

.or-summary-status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 18px;
  color: rgba(255, 255, 255, 0.75);
  font-size: 0.86rem;
  font-weight: 700;
}

.or-summary-status strong {
  color: var(--or-accent);
}

.or-summary-eyebrow {
  display: block;
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0;
  text-transform: none;
}

.or-summary-total {
  display: block;
  margin-top: 8px;
  color: #fff;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: clamp(2.15rem, 4vw, 2.7rem);
  font-weight: 800;
  line-height: 1;
  letter-spacing: 0;
  font-variant-numeric: tabular-nums;
  overflow-wrap: anywhere;
}

.or-summary-days {
  margin: 10px 0 0;
  color: rgba(255, 255, 255, 0.72);
  font-size: 0.9rem;
  font-weight: 700;
}

.or-summary-body {
  display: block;
  padding: 22px 24px 24px;
}

.or-summary-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  padding: 12px 0;
  border-bottom: 1px solid rgba(226, 232, 240, 0.78);
  color: var(--or-slate-600);
  font-size: 0.92rem;
}

.or-summary-row strong {
  color: var(--or-slate-900);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-weight: 800;
  text-align: right;
  white-space: nowrap;
}

.or-due-card {
  margin: 18px 0;
  padding: 16px;
  border: 1px solid #bbf7d0;
  border-radius: 17px;
  background: linear-gradient(135deg, #ecfdf5, #f0fdfa);
}

.or-due-card span {
  display: block;
  color: #047857;
  font-size: 0.78rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.or-due-card strong {
  display: block;
  margin-top: 4px;
  color: #065f46;
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1.75rem;
}

.or-product {
  margin: 0 0 18px;
  padding: 13px;
  border: 1px solid #bae6fd;
  border-radius: 14px;
  background: linear-gradient(135deg, #f0f8fc 0%, #e0f2fe 100%);
}

.or-summary-checks {
  display: grid;
  gap: 9px;
  margin: 16px 0 18px;
}

.or-btn-primary {
  min-height: 54px;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--or-brand), var(--or-brand-2));
  box-shadow: 0 14px 28px rgba(21, 59, 79, 0.18);
  transition: transform var(--or-dur) var(--or-ease), box-shadow var(--or-dur) var(--or-ease), filter var(--or-dur) var(--or-ease);
}

.or-btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 20px 38px rgba(21, 59, 79, 0.22);
  filter: saturate(1.08);
}

.or-btn-primary:focus-visible,
.or-load-more:focus-visible,
.or-alert-btn:focus-visible {
  outline: 3px solid rgba(34, 211, 238, 0.45);
  outline-offset: 3px;
}

.or-btn-block {
  width: 100%;
  margin-top: 0;
  padding: 0 22px;
}

.or-summary-note {
  display: block;
  margin: 14px 0 0;
  padding: 0;
  border-radius: 0;
  background: transparent;
  color: var(--or-slate-600);
  font-size: 0.84rem;
  line-height: 1.5;
  text-align: center;
}

.or-related-panel {
  overflow: hidden;
}

.or-related-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 18px;
  min-width: 0;
}

.or-count {
  flex: 0 0 auto;
  padding: 8px 10px;
  border-radius: 999px;
  color: var(--or-brand);
  background: var(--or-brand-soft);
  font-size: 0.8rem;
  font-weight: 800;
}

.or-empty {
  margin: 0;
  padding: 24px;
  border: 1px dashed var(--or-line);
  border-radius: 14px;
  background: var(--or-slate-50);
  color: var(--or-slate-600);
  text-align: center;
}

.or-load-more-wrap {
  display: flex;
  justify-content: center;
  margin-top: 22px;
}

.or-load-more {
  min-height: 46px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 18px;
  border: 1px solid var(--or-brand);
  border-radius: 999px;
  background: #fff;
  color: var(--or-brand);
  font-weight: 700;
  cursor: pointer;
  transition: transform var(--or-dur) var(--or-ease), box-shadow var(--or-dur) var(--or-ease), background-color var(--or-dur) var(--or-ease), color var(--or-dur) var(--or-ease);
}

.or-load-more:hover {
  transform: translateY(-1px);
  background: var(--or-brand);
  color: #fff;
  box-shadow: var(--or-shadow-sm);
}

.or-load-more svg {
  width: 16px;
  height: 16px;
}

@media (min-width: 1180px) {
  .or-post-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .or-related-panel {
    grid-column: 1 / -1;
  }

  .or-related-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 1100px) {
  .or-partner-inner,
  .or-grid,
  .or-vehicle {
    grid-template-columns: 1fr;
  }

  .or-summary {
    position: static;
  }

  .or-proof-strip {
    grid-template-columns: repeat(2, 1fr);
  }

  .or-proof-item:nth-child(2) {
    border-right: 0;
  }

  .or-proof-item:nth-child(-n+2) {
    border-bottom: 1px solid rgba(226, 232, 240, 0.82);
  }
}

@media (max-width: 760px) {
  .or-partner-inner {
    min-height: auto;
    padding-block: 28px 44px;
  }

  .or-progress-preview {
    display: none;
  }

  .or-hero-title {
    font-size: 2.3rem;
  }

  .or-route-row,
  .or-proof-strip,
  .or-timeline,
  .or-spec-grid,
  .or-confidence,
  .or-included-grid {
    grid-template-columns: 1fr;
  }

  .or-route-line {
    width: 1px;
    height: 28px;
    margin-left: 10px;
  }

  .or-proof-item,
  .or-proof-item:nth-child(2),
  .or-proof-item:nth-child(-n+2) {
    border-right: 0;
    border-bottom: 1px solid rgba(226, 232, 240, 0.82);
  }

  .or-proof-item:last-child {
    border-bottom: 0;
  }

  .or-time-box {
    border-right: 0;
    border-bottom: 1px solid var(--or-line);
  }

  .or-time-box:last-child {
    border-bottom: 0;
  }

  .or-panel-pad,
  .or-vehicle-body,
  .or-summary-top,
  .or-summary-body {
    padding-inline: 18px;
  }

  .or-supplier-row {
    align-items: flex-start;
    flex-direction: column;
  }

  .or-rating {
    white-space: normal;
  }

  .or-vehicle-media {
    min-height: 290px;
    padding: 18px;
  }

  .or-vehicle-media::after {
    inset: 16px;
  }

  .or-related-grid {
    grid-template-columns: 1fr;
  }

  :global(.floating-widget) {
    display: none !important;
  }
}

@media (prefers-reduced-motion: reduce) {
  .or-partner-copy,
  .or-trip-card,
  .or-proof-strip,
  .or-post-grid,
  .or-col-main > *,
  .or-summary {
    animation: none;
  }

  .or-spec,
  .or-btn-primary,
  .or-load-more,
  .or-hero-chips span {
    transition-duration: 1ms;
  }
}

/* Calmer offer page revision */
.or-page {
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 52%, #f6fafc 100%);
}

.or-partner-band {
  background:
    linear-gradient(135deg, rgba(11, 34, 48, 0.96) 0%, rgba(21, 59, 79, 0.98) 55%, rgba(7, 25, 35, 0.98) 100%);
}

.or-partner-inner {
  min-height: 272px;
  align-items: center;
  gap: clamp(32px, 7vw, 92px);
  padding-block: 30px 42px;
}

.or-progress-preview {
  width: min(100%, 410px);
  height: 36px;
  margin-bottom: 24px;
  background: rgba(255, 255, 255, 0.055);
  backdrop-filter: none;
}

.or-progress-preview span {
  height: 26px;
  font-size: 0.78rem;
}

.or-source-pill {
  margin-bottom: 11px;
  color: rgba(34, 211, 238, 0.92);
}

.or-hero-title {
  max-width: 680px;
  font-size: clamp(2.35rem, 4.1vw, 3.9rem);
  line-height: 1.08;
}

.or-hero-sub {
  max-width: 610px;
  margin-top: 14px;
  color: rgba(255, 255, 255, 0.75);
  font-size: 1.02rem;
  line-height: 1.7;
}

.or-hero-chips {
  gap: 18px;
  margin-top: 22px;
}

.or-hero-chips span {
  min-height: auto;
  padding: 0;
  border: 0;
  background: transparent;
  color: rgba(255, 255, 255, 0.76);
  font-size: 0.86rem;
  backdrop-filter: none;
}

.or-hero-chips span::before {
  width: 6px;
  height: 6px;
  box-shadow: none;
}

.or-trip-card {
  padding: 0 0 0 32px;
  border: 0;
  border-left: 1px solid rgba(255, 255, 255, 0.18);
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  backdrop-filter: none;
}

.or-route-status {
  margin-bottom: 18px;
  padding-bottom: 14px;
}

.or-route-line {
  width: 42px;
  background: rgba(34, 211, 238, 0.7);
}

.or-route-date span {
  padding: 0;
  background: transparent;
  color: rgba(255, 255, 255, 0.72);
}

.or-body {
  padding: 42px 0 72px;
}

.or-body-inner {
  gap: 28px;
}

.or-grid {
  grid-template-columns: minmax(0, 1fr) minmax(320px, 360px);
  gap: 30px;
}

.or-panel,
.or-vehicle {
  border-color: rgba(226, 232, 240, 0.95);
  border-radius: 18px;
  background: #fff;
  box-shadow: 0 8px 26px rgba(21, 59, 79, 0.055);
}

.or-vehicle {
  grid-template-columns: minmax(300px, 38%) minmax(0, 1fr);
  box-shadow: 0 14px 40px rgba(21, 59, 79, 0.085);
}

.or-vehicle-media {
  min-height: 330px;
  padding: 24px;
  background: linear-gradient(145deg, #f0f8fc 0%, #ffffff 72%);
}

.or-vehicle-media::before,
.or-vehicle-media::after {
  content: none;
}

.or-vehicle-media img {
  width: min(82%, 390px);
  max-height: 245px;
  filter: drop-shadow(0 18px 20px rgba(21, 59, 79, 0.14));
}

.or-car-badge {
  top: 16px;
  left: 16px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 6px 16px rgba(21, 59, 79, 0.06);
}

.or-vehicle-body {
  padding: 30px 32px;
}

.or-supplier-row {
  margin-bottom: 18px;
}

.or-supplier-mark {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  box-shadow: none;
}

.or-rating {
  background: #eef8fb;
  color: #1c4d66;
}

.or-car-title {
  max-width: 660px;
  font-size: clamp(1.7rem, 2.35vw, 2.15rem);
  line-height: 1.12;
}

.or-car-subtitle {
  max-width: 650px;
  margin-bottom: 24px;
}

.or-spec-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0;
  margin-top: 4px;
  padding-block: 18px;
  border-top: 1px solid var(--or-line);
  border-bottom: 1px solid var(--or-line);
}

.or-spec {
  min-height: auto;
  padding: 0 16px;
  border: 0;
  border-right: 1px solid var(--or-line);
  border-radius: 0;
  background: transparent;
}

.or-spec:first-child {
  padding-left: 0;
}

.or-spec:last-child {
  padding-right: 0;
  border-right: 0;
}

.or-spec:hover {
  transform: none;
  background: transparent;
  border-color: var(--or-line);
}

.or-spec strong {
  font-size: 1rem;
}

.or-confidence {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 18px;
  margin-top: 20px;
}

.or-confidence span {
  min-height: auto;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: var(--or-slate-600);
  font-size: 0.86rem;
}

.or-confidence span::before {
  width: 6px;
  height: 6px;
  box-shadow: none;
}

.or-timeline {
  border-radius: 18px;
}

.or-time-box {
  padding: 22px 24px;
}

.or-summary {
  top: 92px;
  margin-top: 0;
  border-radius: 18px;
  box-shadow: 0 12px 34px rgba(21, 59, 79, 0.08);
}

.or-summary-top {
  padding: 22px 22px 20px;
  color: var(--or-slate-900);
  background: #fff;
  border-bottom: 1px solid var(--or-line);
}

.or-summary-status {
  margin-bottom: 18px;
  color: var(--or-slate-600);
}

.or-summary-status strong {
  color: var(--or-brand);
}

.or-summary-eyebrow {
  color: var(--or-slate-600);
}

.or-summary-total {
  color: var(--or-brand);
  font-size: clamp(2.1rem, 3.2vw, 2.55rem);
}

.or-summary-days {
  color: var(--or-slate-600);
}

.or-summary-body {
  padding: 20px 22px 22px;
}

.or-due-card {
  border-color: rgba(34, 211, 238, 0.26);
  background: #f0f8fc;
}

.or-due-card span {
  color: var(--or-brand-2);
}

.or-due-card strong {
  color: var(--or-brand);
}

.or-product {
  border-color: rgba(226, 232, 240, 0.95);
  background: #fff;
}

.or-summary-checks span::before {
  width: 6px;
  height: 6px;
  box-shadow: none;
}

.or-panel-pad {
  padding: 24px;
}

.or-included {
  border-radius: 14px;
  background: #fbfdff;
}

@media (max-width: 1100px) {
  .or-partner-inner,
  .or-grid,
  .or-vehicle {
    grid-template-columns: 1fr;
  }

  .or-trip-card {
    padding: 20px 0 0;
    border-left: 0;
    border-top: 1px solid rgba(255, 255, 255, 0.16);
  }

  .or-vehicle-media {
    min-height: 300px;
  }
}

@media (max-width: 760px) {
  .or-partner-inner {
    padding-block: 24px 32px;
  }

  .or-hero-title {
    font-size: 2.15rem;
    line-height: 1.16;
  }

  .or-hero-chips {
    gap: 10px 14px;
  }

  .or-body {
    padding-top: 24px;
  }

  .or-vehicle-body,
  .or-panel-pad,
  .or-summary-top,
  .or-summary-body {
    padding-inline: 18px;
  }

  .or-spec-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px 0;
  }

  .or-spec:nth-child(2) {
    padding-right: 0;
    border-right: 0;
  }

  .or-spec:nth-child(3) {
    padding-left: 0;
  }

  .or-car-title {
    line-height: 1.18;
  }
}

/* Same-to-preview offer layout correction */
.or-partner-band {
  display: none !important;
}

.or-page {
  background: linear-gradient(180deg, rgba(240, 248, 252, 0.72) 0%, #fff 42%, #f8fafc 100%);
  overflow-x: clip;
  overflow-y: visible;
}

.or-body {
  padding: 34px 0 72px !important;
}

.or-body-inner {
  gap: 22px;
}

.or-grid {
  display: grid !important;
  grid-template-columns: minmax(0, 1fr) 390px !important;
  gap: 28px !important;
  align-items: start !important;
}

.or-col-main {
  display: grid !important;
  gap: 22px !important;
  min-width: 0;
}

.or-col-side {
  min-width: 0;
  max-width: none;
  position: static;
  align-self: stretch;
}

.or-post-grid {
  display: grid !important;
  grid-template-columns: minmax(0, 1fr) !important;
  gap: 22px !important;
  grid-column: auto !important;
  min-width: 0;
}

.or-two-col {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 22px;
}

.or-panel,
.or-vehicle,
.or-summary {
  border: 1px solid rgba(226, 232, 240, 0.88);
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 10px 30px rgba(21, 59, 79, 0.06);
}

.or-vehicle {
  grid-template-columns: minmax(280px, 42%) 1fr;
}

.or-vehicle-media {
  min-height: 360px;
  background: linear-gradient(145deg, #eef8fb, #fff 48%, #dceef6);
}

.or-vehicle-media::before {
  content: "";
  position: absolute;
  inset: auto -12% -32% -12%;
  height: 58%;
  background: radial-gradient(ellipse at center, rgba(21, 59, 79, 0.18), transparent 64%);
}

.or-vehicle-media::after {
  content: "";
  position: absolute;
  inset: 24px;
  border: 1px solid rgba(21, 59, 79, 0.08);
  border-radius: 22px;
  pointer-events: none;
}

.or-vehicle-media img {
  width: min(88%, 520px);
  max-height: 282px;
  filter: drop-shadow(0 20px 24px rgba(21, 59, 79, 0.2));
}

.or-vehicle-body {
  padding: 28px;
}

.or-car-title {
  font-size: clamp(1.75rem, 3vw, 2.48rem);
  line-height: 1.08;
}

.or-spec-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px;
  padding: 0;
  border: 0;
}

.or-spec {
  min-height: 78px;
  padding: 13px;
  border: 1px solid var(--or-line);
  border-radius: 14px;
  background: var(--or-slate-50);
}

.or-spec:first-child,
.or-spec:nth-child(3) {
  padding-left: 13px;
}

.or-spec:last-child,
.or-spec:nth-child(2) {
  padding-right: 13px;
  border-right: 1px solid var(--or-line);
}

.or-confidence {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.or-confidence span {
  min-height: 48px;
  padding: 10px 12px;
  border: 1px solid rgba(226, 232, 240, 0.92);
  border-radius: 14px;
  background: #fff;
}

.or-section-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin: 0;
  padding: 22px 24px;
  border-bottom: 1px solid rgba(226, 232, 240, 0.82);
}

.or-section-head h3 {
  margin: 4px 0 0;
  color: var(--or-brand);
  font-size: 1.24rem;
  line-height: 1.25;
}

.or-section-head p {
  margin-top: 8px;
  max-width: 680px;
}

.or-choice-list {
  display: grid;
  gap: 12px;
  padding: 18px 24px 24px;
}

.or-choice {
  width: 100%;
  min-height: 92px;
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: 15px;
  align-items: center;
  padding: 16px;
  border: 1px solid var(--or-line);
  border-radius: 17px;
  background: #fff;
}

.or-choice.is-selected {
  border-color: rgba(34, 211, 238, 0.75);
  background: linear-gradient(180deg, #f0fbff, #fff);
  box-shadow: 0 14px 32px rgba(8, 145, 178, 0.1);
}

.or-choice-radio {
  width: 24px;
  height: 24px;
  border: 2px solid #cbd5e1;
  border-radius: 50%;
  background: #fff;
}

.or-choice.is-selected .or-choice-radio {
  border: 7px solid var(--or-brand);
  box-shadow: 0 0 0 4px rgba(34, 211, 238, 0.16);
}

.or-choice-copy h4 {
  margin: 0;
  color: var(--or-slate-900);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1.02rem;
}

.or-choice-copy p {
  margin: 5px 0 0;
  color: var(--or-slate-600);
}

.or-simple-list {
  display: grid;
  gap: 12px;
  padding: 18px 24px 24px;
}

.or-simple-row {
  min-height: 54px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 16px;
  padding: 12px 0;
  border-bottom: 1px solid rgba(226, 232, 240, 0.78);
}

.or-simple-row:last-child {
  border-bottom: 0;
}

.or-simple-row span {
  color: var(--or-slate-600);
}

.or-simple-row small {
  display: block;
  margin-top: 2px;
  color: var(--or-slate-500);
  font-weight: 600;
}

.or-simple-row strong {
  color: var(--or-brand);
  font-family: "Plus Jakarta Sans", sans-serif;
  white-space: nowrap;
}

.or-summary {
  position: sticky;
  top: 96px;
  overflow: hidden;
  border-color: rgba(21, 59, 79, 0.13);
  border-radius: 24px;
  box-shadow: var(--or-shadow-lg);
}

.or-summary-top {
  padding: 24px;
  color: #fff;
  background: linear-gradient(135deg, var(--or-brand-dark), var(--or-brand));
  border-bottom: 0;
}

.or-summary-status {
  margin-bottom: 18px;
  color: rgba(255, 255, 255, 0.75);
}

.or-summary-status strong {
  color: var(--or-accent);
}

.or-summary-eyebrow,
.or-summary-days {
  color: rgba(255, 255, 255, 0.7);
}

.or-summary-total {
  color: #fff;
  font-size: clamp(2.15rem, 4vw, 2.7rem);
}

.or-summary-body {
  padding: 22px 24px 24px;
}

.or-due-card {
  margin: 18px 0;
  border-color: #bbf7d0;
  background: linear-gradient(135deg, #ecfdf5, #f0fdfa);
}

.or-due-card span {
  color: #047857;
}

.or-due-card strong {
  color: #065f46;
}

.or-btn-primary {
  background: linear-gradient(135deg, var(--or-brand), var(--or-brand-2));
}

@media (max-width: 1060px) {
  .or-grid,
  .or-vehicle,
  .or-two-col {
    grid-template-columns: 1fr !important;
  }

  .or-summary {
    position: static;
  }
}

@media (max-width: 760px) {
  .or-body {
    padding-top: 24px !important;
  }

  .or-spec-grid,
  .or-confidence {
    grid-template-columns: 1fr;
  }

  .or-section-head,
  .or-vehicle-body,
  .or-choice-list,
  .or-simple-list,
  .or-summary-top,
  .or-summary-body {
    padding-inline: 18px;
  }

  .or-vehicle-media {
    min-height: 290px;
  }

  .or-car-title {
    line-height: 1.16;
  }
}

/* Professional detail-card polish */
.or-spec-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  gap: 14px !important;
}

.or-spec {
  min-height: 96px !important;
  display: grid !important;
  grid-template-columns: 48px minmax(0, 1fr) !important;
  align-items: center !important;
  gap: 14px !important;
  padding: 16px !important;
  border: 1px solid rgba(176, 212, 230, 0.86) !important;
  border-radius: 18px !important;
  background: linear-gradient(180deg, #ffffff 0%, #f7fbfd 100%) !important;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 8px 18px rgba(21, 59, 79, 0.045);
}

.or-spec:hover {
  transform: translateY(-2px) !important;
  border-color: rgba(21, 59, 79, 0.26) !important;
  background: #f0f8fc !important;
  box-shadow: 0 14px 28px rgba(21, 59, 79, 0.08);
}

.or-spec-icon {
  width: 48px !important;
  height: 48px !important;
  display: grid !important;
  place-items: center !important;
  border-radius: 16px !important;
  color: var(--or-brand) !important;
  background: linear-gradient(135deg, #e7f5fa, #ffffff) !important;
  box-shadow: 0 8px 18px rgba(21, 59, 79, 0.08);
  font-size: initial !important;
  font-weight: initial !important;
  letter-spacing: 0 !important;
  text-transform: none !important;
}

.or-spec-icon svg {
  width: 22px !important;
  height: 22px !important;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.or-icon-lug { color: #0e7490 !important; background: linear-gradient(135deg, #cffafe, #f8fafc) !important; }
.or-icon-trans { color: #047857 !important; background: linear-gradient(135deg, #dcfce7, #f8fafc) !important; }
.or-icon-mileage { color: #1c4d66 !important; background: linear-gradient(135deg, #dceef6, #f8fafc) !important; }

.or-spec-text {
  min-width: 0 !important;
  color: inherit !important;
  font-size: initial !important;
  font-weight: initial !important;
  letter-spacing: 0 !important;
  text-transform: none !important;
}

.or-spec-text > span {
  display: block !important;
  color: var(--or-slate-500) !important;
  font-size: 0.72rem !important;
  font-weight: 800 !important;
  letter-spacing: 0.08em !important;
  line-height: 1.2 !important;
  text-transform: uppercase !important;
}

.or-spec-text > strong {
  display: block !important;
  margin-top: 6px !important;
  color: var(--or-brand) !important;
  font-family: "Plus Jakarta Sans", sans-serif !important;
  font-size: 1.06rem !important;
  font-weight: 800 !important;
  line-height: 1.3 !important;
  white-space: normal !important;
  overflow: visible !important;
  overflow-wrap: anywhere;
  text-overflow: clip !important;
}

.or-confidence {
  display: flex !important;
  flex-wrap: wrap !important;
  gap: 10px !important;
  margin-top: 22px !important;
}

.or-confidence span {
  min-height: 46px !important;
  flex: 1 1 180px;
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 10px 12px !important;
  border: 1px solid rgba(176, 212, 230, 0.7) !important;
  border-radius: 999px !important;
  background: #fff !important;
  color: var(--or-slate-700) !important;
  font-size: 0.86rem !important;
  line-height: 1.35 !important;
}

.or-confidence span::before {
  content: none !important;
}

.or-confidence svg {
  width: 18px;
  height: 18px;
  flex: 0 0 auto;
  color: #059669;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.or-summary-status span {
  color: rgba(255, 255, 255, 0.78);
}

.or-summary-eyebrow {
  display: block !important;
  color: rgba(255, 255, 255, 0.72) !important;
  font-size: 0.78rem !important;
  font-weight: 800 !important;
  letter-spacing: 0.06em !important;
  line-height: 1.2 !important;
  text-transform: uppercase !important;
}

.or-summary-row {
  display: grid !important;
  grid-template-columns: 36px minmax(0, 1fr) auto !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 14px 0 !important;
}

.or-summary-row-icon {
  width: 36px;
  height: 36px;
  display: grid !important;
  place-items: center;
  border-radius: 12px;
  background: #f0f8fc;
  color: var(--or-brand);
  flex: 0 0 auto;
}

.or-summary-row-icon svg {
  width: 18px;
  height: 18px;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.or-summary-row-copy {
  min-width: 0;
  display: block !important;
  color: inherit !important;
  font-size: initial !important;
  font-weight: initial !important;
  flex-shrink: 1 !important;
}

.or-summary-row-label {
  display: block !important;
  color: var(--or-slate-700) !important;
  font-size: 0.94rem !important;
  font-weight: 800 !important;
  line-height: 1.25 !important;
  overflow-wrap: anywhere;
}

.or-summary-row-copy small {
  display: block;
  margin-top: 3px;
  color: var(--or-slate-500);
  font-size: 0.78rem;
  font-weight: 600;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.or-summary-row strong {
  align-self: center;
  color: var(--or-slate-900) !important;
  font-size: 0.98rem;
}

.or-due-card {
  padding: 18px !important;
  border-radius: 20px !important;
}

@media (max-width: 1280px) {
  .or-grid {
    grid-template-columns: minmax(0, 1fr) 370px !important;
  }

  .or-spec {
    min-height: 104px !important;
  }
}

@media (max-width: 1060px) {
  .or-grid {
    grid-template-columns: 1fr !important;
  }

  .or-summary-row {
    grid-template-columns: 36px minmax(0, 1fr) auto !important;
  }
}

@media (max-width: 760px) {
  .or-spec-grid {
    grid-template-columns: 1fr !important;
  }

  .or-spec {
    min-height: 88px !important;
  }

  .or-confidence span {
    flex-basis: 100%;
    border-radius: 16px !important;
  }

  .or-summary-row {
    grid-template-columns: 34px minmax(0, 1fr) !important;
  }

  .or-summary-row strong {
    grid-column: 2;
    justify-self: start;
    margin-top: 4px;
  }
}

/* Premium customer checkout pass */
.or-vehicle {
  border-radius: 26px !important;
  box-shadow: 0 18px 48px rgba(21, 59, 79, 0.08) !important;
}

.or-vehicle-media {
  background:
    radial-gradient(circle at 50% 38%, rgba(34, 211, 238, 0.16), transparent 36%),
    linear-gradient(145deg, #f7fcfe 0%, #eef8fb 58%, #dceef6 100%) !important;
}

.or-vehicle-body {
  padding: 30px 30px 28px !important;
}

.or-supplier-row {
  margin-bottom: 16px !important;
}

.or-car-title {
  max-width: 560px !important;
  color: #0f172a !important;
  font-size: clamp(2rem, 2.45vw, 2.28rem) !important;
  line-height: 1.08 !important;
  letter-spacing: 0 !important;
}

.or-car-subtitle {
  max-width: 58ch !important;
  margin: 12px 0 18px !important;
  color: #475569 !important;
  font-size: 0.98rem !important;
  line-height: 1.58 !important;
}

.or-trip-snapshot {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin: 0 0 16px;
  padding: 10px;
  border: 1px solid rgba(176, 212, 230, 0.72);
  border-radius: 20px;
  background: linear-gradient(135deg, #ffffff 0%, #f0f8fc 100%);
}

.or-trip-snapshot-item {
  min-width: 0;
  display: grid;
  grid-template-columns: 34px minmax(0, 1fr);
  gap: 9px;
  align-items: center;
  padding: 10px;
  border-radius: 14px;
  color: var(--or-brand);
}

.or-trip-snapshot-item:nth-child(3) {
  grid-column: 1 / -1;
}

.or-trip-snapshot-icon {
  width: 34px;
  height: 34px;
  display: grid;
  place-items: center;
  border-radius: 12px;
  color: var(--or-brand);
  background: #fff;
  box-shadow: 0 6px 14px rgba(21, 59, 79, 0.08);
}

.or-trip-snapshot-icon svg {
  width: 17px;
  height: 17px;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.or-trip-snapshot small {
  display: block;
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  line-height: 1.1;
  text-transform: uppercase;
}

.or-trip-snapshot strong {
  display: block;
  margin-top: 4px;
  color: #153b4f;
  font-size: 0.83rem;
  font-weight: 800;
  line-height: 1.24;
  overflow: visible;
  text-overflow: clip;
  white-space: normal;
}

.or-spec-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  gap: 10px !important;
}

.or-spec {
  min-height: 74px !important;
  grid-template-columns: 36px minmax(0, 1fr) !important;
  gap: 9px !important;
  padding: 11px 12px !important;
  border-radius: 16px !important;
  border-color: rgba(226, 232, 240, 0.92) !important;
  background: #fff !important;
  box-shadow: 0 8px 18px rgba(21, 59, 79, 0.04) !important;
}

.or-spec-icon {
  width: 36px !important;
  height: 36px !important;
  border-radius: 13px !important;
  box-shadow: none !important;
}

.or-spec-icon svg {
  width: 18px !important;
  height: 18px !important;
}

.or-spec-text > span {
  font-size: 0.64rem !important;
  letter-spacing: 0.08em !important;
}

.or-spec-text > strong {
  margin-top: 4px !important;
  font-size: 0.95rem !important;
  line-height: 1.22 !important;
}

.or-confidence {
  display: flex !important;
  gap: 16px 18px !important;
  margin-top: 16px !important;
}

.or-confidence span {
  min-height: 0 !important;
  flex: 0 1 auto !important;
  padding: 0 !important;
  border: 0 !important;
  border-radius: 0 !important;
  background: transparent !important;
  color: #475569 !important;
  font-size: 0.84rem !important;
  font-weight: 800 !important;
}

.or-confidence svg {
  width: 16px;
  height: 16px;
}

.or-summary {
  border-radius: 26px !important;
  box-shadow: 0 22px 54px rgba(21, 59, 79, 0.12) !important;
}

.or-summary-top {
  padding: 22px 24px 24px !important;
  background:
    radial-gradient(80% 110% at 92% 0%, rgba(34, 211, 238, 0.34), transparent 55%),
    linear-gradient(135deg, #0b2230 0%, #153b4f 58%, #0b1b26 100%) !important;
}

.or-summary-total {
  font-size: clamp(2.18rem, 3.1vw, 2.6rem) !important;
}

.or-summary-body {
  padding: 20px 24px 24px !important;
}

@media (max-width: 1280px) {
  .or-spec-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }
}

@media (max-width: 1060px) {
  .or-trip-snapshot {
    grid-template-columns: 1fr;
  }

  .or-trip-snapshot strong {
    white-space: normal;
  }
}

@media (max-width: 760px) {
  .or-vehicle {
    border-radius: 22px !important;
  }

  .or-vehicle-body {
    padding: 24px 18px !important;
  }

  .or-car-title {
    font-size: 2.15rem !important;
  }

  .or-spec-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }

  .or-spec {
    grid-template-columns: 1fr !important;
    align-items: start !important;
    min-height: 118px !important;
  }

  .or-confidence {
    display: grid !important;
    gap: 10px !important;
  }

  .or-confidence span {
    align-items: flex-start !important;
  }
}

@media (max-width: 460px) {
  .or-spec-grid {
    grid-template-columns: 1fr !important;
  }

  .or-spec {
    grid-template-columns: 40px minmax(0, 1fr) !important;
    min-height: 82px !important;
  }
}

/* Balance media and content width after customer-polish pass */
.or-vehicle {
  grid-template-columns: minmax(260px, 34%) minmax(0, 1fr) !important;
}

.or-vehicle-media {
  min-height: 500px !important;
}

.or-vehicle-media img {
  width: min(86%, 380px) !important;
  max-height: 260px !important;
}

.or-trip-snapshot strong {
  max-width: 100%;
}

.or-spec {
  min-height: 74px !important;
}

@media (max-width: 1280px) {
  .or-vehicle {
    grid-template-columns: minmax(240px, 36%) minmax(0, 1fr) !important;
  }

  .or-spec-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
  }

  .or-spec {
    min-height: 74px !important;
  }
}

@media (max-width: 1060px) {
  .or-vehicle {
    grid-template-columns: 1fr !important;
  }

  .or-vehicle-media {
    min-height: 300px !important;
  }
}

/* Customer-facing balance pass */
.or-page {
  background:
    linear-gradient(180deg, rgba(240, 248, 252, 0.86) 0%, rgba(255, 255, 255, 0.98) 34%, #f8fafc 100%) !important;
}

.or-body {
  padding-top: 28px !important;
}

.or-grid {
  grid-template-columns: minmax(0, 1fr) minmax(320px, 360px) !important;
  gap: 26px !important;
}

.or-vehicle,
.or-panel,
.or-summary {
  border-color: rgba(205, 222, 232, 0.86) !important;
  box-shadow: 0 16px 44px rgba(21, 59, 79, 0.075) !important;
}

.or-vehicle {
  grid-template-columns: minmax(260px, 32%) minmax(0, 1fr) !important;
}

.or-vehicle-media {
  min-height: 430px !important;
  background:
    radial-gradient(circle at 50% 42%, rgba(34, 211, 238, 0.12), transparent 34%),
    linear-gradient(145deg, #f3fbfe 0%, #fff 58%, #e5f3f8 100%) !important;
}

.or-vehicle-media img {
  width: min(88%, 285px) !important;
  max-height: 285px !important;
}

.or-vehicle-body {
  padding: 28px !important;
}

.or-car-title {
  font-size: clamp(1.86rem, 2.1vw, 2.14rem) !important;
  line-height: 1.12 !important;
}

.or-car-subtitle {
  max-width: 54ch !important;
  margin: 10px 0 16px !important;
  font-size: 0.94rem !important;
}

.or-trip-snapshot {
  border-radius: 18px !important;
  background: #f8fcfe !important;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
}

.or-spec-grid {
  gap: 9px !important;
}

.or-spec {
  min-height: 72px !important;
  border-radius: 15px !important;
}

.or-spec-text > strong {
  font-size: 0.92rem !important;
}

.or-confidence {
  gap: 12px 16px !important;
}

.or-confidence span {
  font-size: 0.82rem !important;
}

.or-summary-total {
  font-size: clamp(2rem, 2.7vw, 2.42rem) !important;
}

.or-alert {
  align-items: center !important;
  border-radius: 18px !important;
  background: linear-gradient(135deg, #fff7d6 0%, #fff1ad 100%) !important;
}

.or-alert-message {
  max-width: 58ch;
}

@media (max-width: 1280px) {
  .or-grid {
    grid-template-columns: minmax(0, 1fr) minmax(310px, 350px) !important;
  }

  .or-vehicle {
    grid-template-columns: minmax(238px, 34%) minmax(0, 1fr) !important;
  }

  .or-vehicle-media {
    min-height: 390px !important;
  }
}

@media (max-width: 1060px) {
  .or-grid,
  .or-vehicle {
    grid-template-columns: 1fr !important;
  }

  .or-vehicle-media {
    min-height: 270px !important;
  }

  .or-vehicle-media img {
    width: min(74%, 320px) !important;
    max-height: 220px !important;
  }
}

@media (max-width: 760px) {
  .or-body {
    padding-top: 16px !important;
  }

  .or-body-inner {
    gap: 16px !important;
  }

  .or-alert {
    display: grid !important;
    grid-template-columns: 42px minmax(0, 1fr);
    gap: 11px 12px !important;
    align-items: center !important;
    padding: 14px !important;
    border-radius: 20px !important;
  }

  .or-alert-icon {
    width: 42px !important;
    height: 42px !important;
    border-radius: 14px !important;
  }

  .or-alert-icon svg {
    width: 20px !important;
    height: 20px !important;
  }

  .or-alert-eyebrow {
    margin-bottom: 3px !important;
    font-size: 0.7rem !important;
  }

  .or-alert-message {
    font-size: 0.9rem !important;
    line-height: 1.42 !important;
  }

  .or-alert-btn {
    grid-column: 1 / -1;
    min-height: 46px;
    justify-content: center;
    padding: 0 16px !important;
    border-radius: 15px !important;
    font-size: 0.94rem !important;
  }

  .or-vehicle {
    border-radius: 21px !important;
  }

  .or-vehicle-media {
    min-height: 228px !important;
    padding: 14px !important;
  }

  .or-vehicle-media::after {
    inset: 14px !important;
    border-radius: 18px !important;
  }

  .or-vehicle-media img {
    width: min(70%, 250px) !important;
    max-height: 190px !important;
  }

  .or-car-badge {
    top: 12px !important;
    left: 12px !important;
    padding: 8px 14px !important;
    font-size: 0.72rem !important;
  }

  .or-vehicle-body {
    padding: 20px 18px 22px !important;
  }

  .or-supplier-row {
    flex-direction: row !important;
    align-items: center !important;
    gap: 10px !important;
    margin-bottom: 14px !important;
  }

  .or-supplier {
    font-size: 0.92rem !important;
  }

  .or-rating {
    padding: 7px 10px !important;
    font-size: 0.78rem !important;
  }

  .or-car-title {
    font-size: clamp(1.74rem, 8vw, 1.96rem) !important;
    line-height: 1.14 !important;
  }

  .or-car-subtitle {
    margin: 8px 0 14px !important;
    font-size: 0.92rem !important;
    line-height: 1.52 !important;
  }

  .or-trip-snapshot {
    gap: 8px !important;
    padding: 8px !important;
    margin-bottom: 13px !important;
  }

  .or-trip-snapshot-item {
    grid-template-columns: 32px minmax(0, 1fr) !important;
    gap: 8px !important;
    padding: 9px !important;
  }

  .or-trip-snapshot-icon {
    width: 32px !important;
    height: 32px !important;
    border-radius: 11px !important;
  }

  .or-trip-snapshot strong {
    font-size: 0.82rem !important;
  }

  .or-spec-grid {
    grid-template-columns: 1fr !important;
    gap: 8px !important;
  }

  .or-spec {
    grid-template-columns: 38px minmax(0, 1fr) !important;
    min-height: 68px !important;
    padding: 10px 12px !important;
  }

  .or-spec-icon {
    width: 38px !important;
    height: 38px !important;
  }

  .or-spec-text > strong {
    font-size: 0.9rem !important;
  }

  .or-confidence {
    margin-top: 12px !important;
  }

  .or-confidence span {
    font-size: 0.81rem !important;
    line-height: 1.35 !important;
  }

  .or-summary-total {
    font-size: 2rem !important;
  }
}

@media (max-width: 420px) {
  .or-alert {
    grid-template-columns: 38px minmax(0, 1fr);
    padding: 12px !important;
  }

  .or-alert-icon {
    width: 38px !important;
    height: 38px !important;
  }

  .or-car-title {
    font-size: clamp(1.62rem, 7.4vw, 1.86rem) !important;
  }

  .or-vehicle-media {
    min-height: 214px !important;
  }

  .or-vehicle-media img {
    width: min(68%, 230px) !important;
    max-height: 178px !important;
  }
}

/* Clear information architecture pass */
.or-trip-review,
.or-includes-panel {
  overflow: hidden;
}

.or-trip-detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0;
  padding: 0 24px 24px;
}

.or-trip-detail {
  min-width: 0;
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  gap: 13px;
  padding: 20px 18px 20px 0;
  border-top: 1px solid rgba(226, 232, 240, 0.9);
}

.or-trip-detail:nth-child(odd) {
  padding-right: 24px;
  border-right: 1px solid rgba(226, 232, 240, 0.9);
}

.or-trip-detail:nth-child(even) {
  padding-left: 24px;
}

.or-trip-detail-icon,
.or-include-icon {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  border-radius: 14px;
  color: var(--or-brand);
  background: #f0f8fc;
}

.or-trip-detail-icon svg,
.or-include-icon svg {
  width: 20px;
  height: 20px;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.or-trip-detail-copy,
.or-include-head div {
  min-width: 0;
}

.or-trip-detail-copy small,
.or-include-head small {
  display: block;
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  line-height: 1.15;
  text-transform: uppercase;
}

.or-trip-detail-copy strong,
.or-include-head strong {
  display: block;
  margin-top: 5px;
  color: var(--or-brand);
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1rem;
  font-weight: 800;
  line-height: 1.28;
  overflow-wrap: anywhere;
}

.or-trip-detail-copy em {
  display: block;
  margin-top: 5px;
  color: #475569;
  font-style: normal;
  font-size: 0.9rem;
  font-weight: 600;
  line-height: 1.42;
  overflow-wrap: anywhere;
}

.or-include-groups {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0;
  border-top: 1px solid rgba(226, 232, 240, 0.9);
}

.or-include-group {
  min-width: 0;
  padding: 22px 22px 24px;
  border-right: 1px solid rgba(226, 232, 240, 0.9);
}

.or-include-group:last-child {
  border-right: 0;
}

.or-include-head {
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  align-items: center;
  gap: 13px;
}

.or-include-list {
  display: grid;
  gap: 0;
  margin: 18px 0 0;
  padding: 0;
  list-style: none;
}

.or-include-list li {
  display: grid;
  gap: 4px;
  padding: 12px 0;
  border-top: 1px solid rgba(226, 232, 240, 0.78);
}

.or-include-list span {
  color: #0f172a;
  font-size: 0.94rem;
  font-weight: 800;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

.or-include-list em {
  color: #475569;
  font-size: 0.86rem;
  font-style: normal;
  font-weight: 600;
  line-height: 1.42;
  overflow-wrap: anywhere;
}

@media (max-width: 1280px) {
  .or-include-groups {
    grid-template-columns: 1fr;
  }

  .or-include-group {
    border-right: 0;
    border-bottom: 1px solid rgba(226, 232, 240, 0.9);
  }

  .or-include-group:last-child {
    border-bottom: 0;
  }
}

@media (max-width: 1060px) {
  .or-trip-detail-grid {
    grid-template-columns: 1fr;
  }

  .or-trip-detail,
  .or-trip-detail:nth-child(odd),
  .or-trip-detail:nth-child(even) {
    padding: 18px 0;
    border-right: 0;
  }
}

@media (max-width: 760px) {
  .or-trip-detail-grid {
    padding: 0 18px 18px;
  }

  .or-trip-detail {
    grid-template-columns: 38px minmax(0, 1fr);
    gap: 11px;
  }

  .or-trip-detail-icon,
  .or-include-icon {
    width: 38px;
    height: 38px;
    border-radius: 13px;
  }

  .or-trip-detail-icon svg,
  .or-include-icon svg {
    width: 18px;
    height: 18px;
  }

  .or-trip-detail-copy strong,
  .or-include-head strong {
    font-size: 0.94rem;
  }

  .or-trip-review .or-section-head h3,
  .or-includes-panel .or-section-head h3 {
    font-size: 1.42rem !important;
    line-height: 1.22 !important;
  }

  .or-trip-review .or-section-head p,
  .or-includes-panel .or-section-head p {
    font-size: 0.94rem !important;
    line-height: 1.5 !important;
  }

  .or-trip-detail-copy em,
  .or-include-list em {
    font-size: 0.84rem;
  }

  .or-include-group {
    padding: 18px;
  }

  .or-include-head {
    grid-template-columns: 38px minmax(0, 1fr);
    gap: 11px;
  }
}
</style>
