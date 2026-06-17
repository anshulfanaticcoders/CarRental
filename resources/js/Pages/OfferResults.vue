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

const noticeMetaText = computed(() => hasFreeEsim.value
  ? _t('free_esim_included_booking', 'Free eSIM included with this booking')
  : _t('package_extras_next_step', 'Package and extras continue in the next step'))

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

const fuelPolicySummary = computed(() => policies.value.fuel_policy || _t('not_specified', 'Not specified'))

const rentalSummary = computed(() => {
  const days = dayCountText(currentBookingContext.value?.number_of_days || 1)

  return search.value.driver_age
    ? `${days}, ${_t('age_label', 'age')} ${search.value.driver_age}`
    : days
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
const offerParityFacts = computed<OfferFact[]>(() => [
  ...offerPackageFacts.value,
  ...offerInsuranceFacts.value,
  ...offerCoverageFacts.value,
  ...offerExtraFacts.value,
])

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

  <section v-if="bookingStep === 'results'" class="or-partner-band">
    <div class="or-offer-container or-partner-inner">
      <div class="or-partner-copy">
        <nav class="or-crumbs">
          <Link href="/">{{ _t('home', 'Home') }}</Link>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
          <span>{{ _t('partner_offer', 'Partner offer') }}</span>
        </nav>
        <span class="or-source-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1.005-.449.95-.998a10 10 0 00-8.952-8.952C12.45 1.995 12 2.448 12 3v1a8 8 0 018 8h1z"/><path d="M3 12c-.552 0-1.005.449-.95.998a10 10 0 008.952 8.952c.55.055.998-.398.998-.95v-1a8 8 0 01-8-8H3z"/></svg>
          {{ partnerSourceLabel }}
        </span>
        <h1 class="or-hero-title">
          {{ displayName || _t('selected_car_rental', 'Selected car rental') }}
          <em v-if="pickupLocation.name">{{ _t('at_location', 'at :location', { location: pickupLocation.name }) }}</em>
        </h1>
        <p class="or-hero-sub">
          {{ _t('offer_page_intro', 'Your selected car is ready to review. Pricing, office details, and booking options stay together before checkout.') }}
        </p>
      </div>

      <div class="or-trip-strip" aria-label="Trip summary">
        <div class="or-trip-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0116 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <div>
            <span>{{ _t('pickup', 'Pickup') }}</span>
            <strong>{{ [pickupLocation.iata, formatDateTime(search.pickup_date, search.pickup_time)].filter(Boolean).join(', ') }}</strong>
          </div>
        </div>
        <div class="or-trip-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 10l5 5-5 5"/><path d="M4 4v7a4 4 0 004 4h12"/></svg>
          <div>
            <span>{{ _t('dropoff', 'Drop-off') }}</span>
            <strong>{{ [dropoffLocation.iata, formatDateTime(search.dropoff_date, search.dropoff_time)].filter(Boolean).join(', ') }}</strong>
          </div>
        </div>
        <div class="or-trip-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
          <div>
            <span>{{ _t('rental', 'Rental') }}</span>
            <strong>{{ rentalSummary }}</strong>
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

        <div class="or-notice-row">
          <div class="or-notice-copy">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8z"/><path d="M9 12l2 2 4-4"/></svg>
            <span>{{ _t('selected_offer_preserved', 'Selected offer preserved from partner') }}</span>
          </div>
          <span class="or-notice-meta">{{ noticeMetaText }}</span>
        </div>

        <div class="or-grid">
          <div class="or-col-main">
            <article class="or-vehicle">
              <div class="or-vehicle-media">
                <img v-if="vehicle.image_url" :src="vehicle.image_url" :alt="displayName || _t('vehicle_image', 'Vehicle image')" />
                <div v-else class="or-vehicle-empty">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 16H9m10 0h3v-3.15a1 1 0 00-.84-.99L16 11l-2.7-3.6a1 1 0 00-.8-.4H5.24a2 2 0 00-1.8 1.1l-.8 1.63A6 6 0 002 12.42V16h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                  <span>{{ _t('no_image_available', 'No image available') }}</span>
                </div>
              </div>

              <div class="or-vehicle-body">
                <div class="or-badge-row">
                  <span class="or-badge or-badge-good">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 13l4 4L19 7"/></svg>
                    {{ _t('selected_offer', 'Selected offer') }}
                  </span>
                  <span v-if="hasFreeEsim" class="or-badge or-badge-cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2M10 6h4M10 10h4M10 14h2"/></svg>
                    {{ _t('free_esim', 'Free eSIM') }}
                  </span>
                  <span class="or-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4M9 9h1M9 13h1M9 17h1"/></svg>
                    {{ locationTypeLabel(pickupLocation) }}
                  </span>
                  <span v-if="vehicle.category" class="or-badge">{{ vehicle.category }}</span>
                </div>

                <div class="or-vehicle-head">
                  <div class="or-vehicle-title">
                    <h2>{{ displayName || _t('vehicle_offer', 'Vehicle offer') }}</h2>
                    <p>
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 21V7l8-4 8 4v14M9 21v-7h6v7M9 9h.01M15 9h.01"/></svg>
                      {{ supplierDisplayName }}
                    </p>
                  </div>
                  <div class="or-price-block">
                    <span>{{ _t('total_from', 'Total from') }}</span>
                    <strong>{{ formatDisplayAmount(pricing.total_price, pricing.currency || search.currency) }}</strong>
                    <small>{{ formatDisplayAmount(pricing.price_per_day, pricing.currency || search.currency) }} {{ _t('per_day_lower', 'per day') }}</small>
                  </div>
                </div>

                <div class="or-spec-grid">
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-seat"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 014-4h4a4 4 0 014 4v2M17 11a4 4 0 100-8"/></svg></div>
                    <div class="or-spec-text"><span>{{ _t('seats', 'Seats') }}</span><strong>{{ vehicle.seats != null ? _t('seats_count', ':count seats', { count: vehicle.seats }) : _t('not_applicable', 'N/A') }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-lug"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="7" width="14" height="13" rx="2"/><path d="M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M9 12v5M15 12v5"/></svg></div>
                    <div class="or-spec-text"><span>{{ _t('bags', 'Bags') }}</span><strong>{{ luggageSummary }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-trans"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><path d="M6 8v8M6 8h12a2 2 0 012 2v6"/></svg></div>
                    <div class="or-spec-text"><span>{{ _t('gearbox', 'Gearbox') }}</span><strong>{{ vehicle.transmission || '-' }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-fuel"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 22V4a2 2 0 012-2h8a2 2 0 012 2v18M3 22h12M6 7h6M18 8l3-3v11a2 2 0 01-4 0V8a2 2 0 012-2"/></svg></div>
                    <div class="or-spec-text"><span>{{ _t('fuel', 'Fuel') }}</span><strong>{{ fuelPolicySummary }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-ac"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M2 12h20M4.93 4.93l14.14 14.14M19.07 4.93L4.93 19.07"/></svg></div>
                    <div class="or-spec-text"><span>{{ _t('comfort', 'Comfort') }}</span><strong>{{ vehicle.air_conditioning ? _t('air_conditioning', 'Air conditioning') : _t('not_specified', 'Not specified') }}</strong></div>
                  </div>
                  <div class="or-spec">
                    <div class="or-spec-icon or-icon-mileage"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/><path d="M7 8l-4 4 4 4"/><path d="M17 8l4 4-4 4"/></svg></div>
                    <div class="or-spec-text"><span>{{ _t('mileage', 'Mileage') }}</span><strong>{{ mileageSummary }}</strong></div>
                  </div>
                </div>
              </div>
            </article>
          </div>

          <div class="or-post-grid">
            <section class="or-panel or-panel-pad">
              <div class="or-section-head">
                <div>
                  <h3>{{ _t('pickup_return_offices', 'Pickup and return offices') }}</h3>
                  <p>{{ _t('office_details_visible', 'Office details stay visible without making the page feel like a form.') }}</p>
                </div>
              </div>

              <div class="or-office-grid">
                <article class="or-office-card">
                  <div class="or-office-top">
                    <div>
                      <h4>{{ pickupLocation.name || _t('not_specified', 'Not specified') }}</h4>
                      <p>{{ pickupLocation.address || _t('address_not_specified', 'Address not specified') }}</p>
                    </div>
                    <span class="or-office-type">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 16l20-5-20-5v4l12 1-12 1v4z"/></svg>
                      {{ locationTypeLabel(pickupLocation) }}
                    </span>
                  </div>
                  <div class="or-mini-data">
                    <span v-if="formatCoordinate(pickupLocation)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-12M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6z"/></svg>{{ formatCoordinate(pickupLocation) }}</span>
                    <span v-if="pickupLocation.phone"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>{{ pickupLocation.phone }}</span>
                    <span v-if="pickupLocation.pickup_instructions"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 01-4 4H7l-4 4V7a4 4 0 014-4h10a4 4 0 014 4v8z"/></svg>{{ pickupLocation.pickup_instructions }}</span>
                    <span v-if="!formatCoordinate(pickupLocation) && !pickupLocation.phone && !pickupLocation.pickup_instructions">{{ formatLocationMeta(pickupLocation) }}</span>
                  </div>
                </article>

                <article class="or-office-card">
                  <div class="or-office-top">
                    <div>
                      <h4>{{ dropoffLocation.name || pickupLocation.name || _t('not_specified', 'Not specified') }}</h4>
                      <p>{{ dropoffLocation.address || pickupLocation.address || _t('return_same_office', 'Return the car to the selected office.') }}</p>
                    </div>
                    <span class="or-office-type">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 00-15-6.7L3 13"/></svg>
                      {{ locationTypeLabel(dropoffLocation) }}
                    </span>
                  </div>
                  <div class="or-mini-data">
                    <span v-if="formatCoordinate(dropoffLocation)"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-12M3 6l6-3 6 3 6-3v15l-6 3-6-3-6 3V6z"/></svg>{{ formatCoordinate(dropoffLocation) }}</span>
                    <span v-if="dropoffLocation.phone"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>{{ dropoffLocation.phone }}</span>
                    <span v-if="dropoffLocation.dropoff_instructions"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 01-4 4H7l-4 4V7a4 4 0 014-4h10a4 4 0 014 4v8z"/></svg>{{ dropoffLocation.dropoff_instructions }}</span>
                    <span v-if="!formatCoordinate(dropoffLocation) && !dropoffLocation.phone && !dropoffLocation.dropoff_instructions">{{ formatLocationMeta(dropoffLocation) }}</span>
                  </div>
                </article>
              </div>
            </section>

            <section class="or-panel or-panel-pad">
              <div class="or-section-head">
                <div>
                  <h3>{{ _t('what_offer_includes', 'What this offer includes') }}</h3>
                  <p>{{ _t('offer_includes_grouped', 'The essentials are grouped as quick scan facts instead of scattered panels.') }}</p>
                </div>
              </div>
              <div class="or-included-grid">
                <div v-if="hasFreeEsim" class="or-included">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2M10 6h4M10 10h4M10 14h2"/></svg>
                  <div><strong>{{ _t('free_esim_included', 'Free eSIM included') }}</strong><span>{{ _t('free_esim_auto', 'Added automatically for every eligible booking.') }}</span></div>
                </div>
                <div v-for="fact in offerParityFacts" :key="fact.key" class="or-included">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8z"/><path d="M9 12l2 2 4-4"/></svg>
                  <div><strong>{{ fact.label }}</strong><span>{{ fact.detail }}</span></div>
                </div>
                <div class="or-included">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V5l8-3 8 3v8z"/></svg>
                  <div><strong>{{ _t('security_deposit', 'Security deposit') }}</strong><span>{{ formatDisplayAmount(pricing.deposit_amount, pricing.deposit_currency || pricing.currency || search.currency) }}</span></div>
                </div>
                <div class="or-included">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                  <div><strong>{{ _t('verified_partner_handoff', 'Verified partner handoff') }}</strong><span>{{ _t('partner_tracking_preserved', 'Offer opened with partner tracking preserved.') }}</span></div>
                </div>
                <div class="or-included">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                  <div><strong>{{ _t('secure_checkout', 'Secure checkout') }}</strong><span>{{ _t('checkout_options_continue', 'Package, protection, and optional extras continue next.') }}</span></div>
                </div>
              </div>
            </section>

            <section class="or-panel or-panel-pad or-related-panel">
              <div class="or-section-head">
                <div>
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

              <p v-else class="or-empty">{{ _t('no_alternative_offers', 'No alternative offers were returned for this search.') }}</p>
              <div v-if="canLoadMoreAlternativeQuotes" class="or-load-more-wrap">
                <button type="button" class="or-load-more" @click="loadMoreAlternativeQuotes">
                  {{ _t('load_more', 'Load more') }}
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                </button>
              </div>
            </section>
          </div>

          <aside class="or-col-side">
            <div class="or-summary">
              <div class="or-summary-top">
                <span class="or-summary-eyebrow">{{ _t('total_rental_price', 'Total rental price') }}</span>
                <strong class="or-summary-total">{{ formatDisplayAmount(pricing.total_price, pricing.currency || search.currency) }}</strong>
                <p class="or-summary-days">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  {{ forDayCountText(currentBookingContext?.number_of_days || 1) }}, {{ _t('includes_vrooem_service_price', 'includes Vrooem service price') }}
                </p>
              </div>

              <div class="or-summary-body">
                <div class="or-summary-row">
                  <span>{{ _t('per_day', 'Per day') }}</span>
                  <strong>{{ formatDisplayAmount(pricing.price_per_day, pricing.currency || search.currency) }}</strong>
                </div>
                <div class="or-summary-row">
                  <span>{{ _t('deposit', 'Deposit') }}</span>
                  <strong>{{ formatDisplayAmount(pricing.deposit_amount, pricing.deposit_currency || pricing.currency || search.currency) }}</strong>
                </div>
                <div class="or-summary-row">
                  <span>{{ _t('mileage', 'Mileage') }}</span>
                  <strong>{{ mileageSummary }}</strong>
                </div>
                <div class="or-summary-row">
                  <span>{{ _t('fuel_policy', 'Fuel policy') }}</span>
                  <strong>{{ fuelPolicySummary }}</strong>
                </div>

                <div v-if="featuredProduct" class="or-product">
                  <span class="or-product-eyebrow">{{ _t('selected_product', 'Selected product') }}</span>
                  <p class="or-product-name">{{ featuredProduct.name || _t('standard_offer', 'Standard offer') }}</p>
                  <p v-if="featuredProduct.subtitle" class="or-product-sub">{{ featuredProduct.subtitle }}</p>
                </div>

                <button type="button" class="or-btn-primary or-btn-block" :disabled="!canBookQuote(quote.quote_id)" @click="startBooking(quote.quote_id)">
                  <span>{{ isExpired ? _t('offer_expired', 'Offer expired') : _t('continue_to_booking', 'Continue to booking') }}</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </button>

                <p class="or-summary-note">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 018 0v4"/></svg>
                  {{ _t('secure_checkout_package_note', 'Secure checkout. Package, protection, and optional extras continue in the next step.') }}
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
</style>
