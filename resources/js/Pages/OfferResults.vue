<script setup lang="ts">
import { computed, reactive, ref, toRefs, unref } from 'vue'
import { Head, Link, usePage, useRemember } from '@inertiajs/vue3'
import { AlertCircle, ArrowRight } from 'lucide-vue-next'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'
import Footer from '@/Components/Footer.vue'
import { Toaster } from '@/Components/ui/sonner'
import { toast } from 'vue-sonner'
import BookingExtrasStep from '@/Components/BookingExtras/BookingExtrasStep.vue'
import BookingCheckoutStep from '@/Components/BookingCheckoutStep.vue'
import OfferSteps from '@/features/offer-results/components/OfferSteps.vue'
import OfferMasthead from '@/features/offer-results/components/OfferMasthead.vue'
import OfferItinerary from '@/features/offer-results/components/OfferItinerary.vue'
import OfferInclusions from '@/features/offer-results/components/OfferInclusions.vue'
import OfferAlternatives from '@/features/offer-results/components/OfferAlternatives.vue'
import OfferSummary from '@/features/offer-results/components/OfferSummary.vue'
import OfferActionBar from '@/features/offer-results/components/OfferActionBar.vue'
import OfferTerms from '@/features/offer-results/components/OfferTerms.vue'
import { useOfferTranslations } from '@/features/offer-results/composables/useOfferTranslations'
import {
  ALTERNATIVES_INITIAL_COUNT,
  buildOfferHistoryState,
  buildOfferSpecFacts,
  normalizeOfferPaymentPercentage,
  resolveOfferPartner,
  resolveOfferPaymentSplit,
  revealMoreAlternatives,
} from '@/features/offer-results/utils/offerPresentation'
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
  description?: string | null
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
  dropoff_instructions?: string | null
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
}

const props = defineProps<{
  quote: Quote
  offerResults: OfferResults
  bookingContext: BookingContext | null
  bookingContexts: Record<string, BookingContext>
  quoteStatus?: QuoteStatus
}>()

type BookingStep = 'results' | 'extras' | 'checkout'

// Arms the checkout quote-expiry gate. Without it quoteExpired stayed false
// forever on offer pages and a stale checkout 422'd at Pay.
// Inertia owns browser history and remounts the page on Back/Forward. Remember
// the complete flow per quote so its history entries restore the selected step.
const bookingFlow = useRemember(reactive({
  bookingStep: 'results' as BookingStep,
  offerLoadedAt: Date.now(),
  activeBookingContext: null as BookingContext | null,
  selectedPackage: 'BAS',
  selectedProtectionCode: null as string | null,
  selectedCheckoutData: null as CheckoutData | null,
}), `OfferResults:${props.quote.quote_id}`)
const { bookingStep, offerLoadedAt, activeBookingContext, selectedPackage, selectedProtectionCode, selectedCheckoutData } = toRefs(unref(bookingFlow))
const relatedCardsLimit = ref(ALTERNATIVES_INITIAL_COUNT)
const page = usePage<SharedPageProps>()
const { getCurrencySymbol } = useCurrencyConversion()
const { t: _t } = useOfferTranslations()

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
const bookingStepContext = computed<Partial<BookingContext>>(() => currentBookingContext.value ?? {})
const selectedVehicle = computed<Record<string, unknown> | null>(() => currentBookingContext.value?.vehicle ?? null)
const selectedOptionalExtras = computed(() => currentBookingContext.value?.optional_extras ?? [])
const bookingCurrencyCode = computed(() => {
  const vehiclePricing = selectedVehicle.value?.pricing as Record<string, unknown> | undefined
  return `${vehiclePricing?.currency ?? pricing.value.currency ?? search.value.currency ?? 'EUR'}`
})
const bookingCurrencySymbol = computed(() => registryCurrencySymbol(bookingCurrencyCode.value))
const currentLocale = computed(() => page.props.locale ?? search.value.language ?? 'en')

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
const optionalScalar = (value: unknown): string | number | undefined => {
  return typeof value === 'string' || typeof value === 'number' ? value : undefined
}
const checkoutSearchSessionId = computed(() => stringValue(currentBookingContext.value?.search_session_id ?? selectedVehicle.value?.search_session_id ?? null))
const checkoutGatewaySearchId = computed(() => stringValue(currentBookingContext.value?.gateway_search_id ?? selectedVehicle.value?.gateway_search_id ?? null))
const checkoutProtectionCode = computed<string | undefined>(() => (
  selectedCheckoutData.value?.protection_code ?? undefined
) as string | undefined)

const displayName = computed(() => vehicle.value.display_name ?? [vehicle.value.brand, vehicle.value.model].filter(Boolean).join(' '))
const pageTitle = computed(() => `${displayName.value || _t('selected_offer_title', 'Selected Offer')} | Vrooem`)
const offerAvailabilityText = computed(() => {
  const count = displayedQuotes.value.length
  const key = count === 1 ? 'offer_available' : 'offers_available'
  const fallback = count === 1 ? ':count offer available' : ':count offers available'

  return _t(key, fallback, { count })
})

const luggageFact = computed(() => {
  const luggage = vehicle.value.luggage ?? {}

  return [
    luggage.small != null ? `${luggage.small} ${_t('luggage_small', 'small')}` : null,
    luggage.medium != null ? `${luggage.medium} ${_t('luggage_medium', 'medium')}` : null,
    luggage.large != null ? `${luggage.large} ${_t('luggage_large', 'large')}` : null,
  ].filter(Boolean).join(' / ')
})

const supplierDisplayName = computed(() => (
  stringValue(vehicle.value.supplier_name)
  ?? stringValue(props.quote.supplier?.name)
  ?? stringValue(props.quote.supplier?.code)
  ?? stringValue(vehicle.value.supplier_code)
  ?? 'Vrooem'
))
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

// Skyscanner and Trabber share this page and neither payload names its source,
// so the Inertia page url is the only reliable partner signal.
const offerPartner = computed(() => resolveOfferPartner(page.url))
const partnerLabel = computed(() => _t(offerPartner.value.translationKey, offerPartner.value.label))

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

const describeMileage = (policy?: string | null, limitKm?: number | null) => {
  if (`${policy || ''}`.toLowerCase() === 'unlimited') {
    return _t('unlimited_mileage', 'Unlimited mileage')
  }

  if (limitKm != null) {
    return `${limitKm} km ${_t('per_day_lower', 'per day')}`
  }

  return stringValue(policy) ?? ''
}

const describeTransmission = (value?: string | null) => {
  const transmission = `${value || ''}`.trim()
  const normalized = transmission.toLowerCase()

  if (normalized === 'automatic') {
    return _t('auto', 'Auto')
  }

  if (normalized === 'manual') {
    return _t('manual', 'Manual')
  }

  return transmission
}

const describeAirConditioning = (value?: boolean | null) => {
  if (value == null) {
    return ''
  }

  return value ? _t('included', 'Included') : _t('not_included', 'Not included')
}

const mileageFact = computed(() => describeMileage(policies.value.mileage_policy, policies.value.mileage_limit_km))
const mileageSummary = computed(() => mileageFact.value || _t('not_specified', 'Not specified'))

// Every spec the supplier actually returned is a decision fact; the util drops
// blanks and repeated wording (a category echoing the SIPP class, for example).
const offerSpecFacts = computed(() => buildOfferSpecFacts({
  class: stringValue(vehicle.value.sipp_code) ?? stringValue(vehicle.value.category) ?? '',
  seats: vehicle.value.seats != null ? `${vehicle.value.seats}` : '',
  bags: luggageFact.value,
  transmission: describeTransmission(vehicle.value.transmission),
  fuel: stringValue(vehicle.value.fuel_type) ?? '',
  airConditioning: describeAirConditioning(vehicle.value.air_conditioning),
  mileage: mileageFact.value,
}))

const fuelPolicySummary = computed(() => policies.value.fuel_policy || _t('not_specified', 'Not specified'))

const rentalSummary = computed(() => {
  const days = dayCountText(currentBookingContext.value?.number_of_days || 1)

  return search.value.driver_age
    ? `${days}, ${_t('age_label', 'age')} ${search.value.driver_age}`
    : days
})

// The deposit rate is supplier-driven. When it is missing or out of range the
// summary says so rather than inventing a percentage.
const paymentPercentage = computed(() => normalizeOfferPaymentPercentage(currentBookingContext.value?.payment_percentage))
const paymentSplit = computed(() => resolveOfferPaymentSplit(toFiniteNumber(pricing.value.total_price), paymentPercentage.value))

const formatDisplayAmount = (amount?: number | null, sourceCurrency?: string | null) => {
  if (amount == null) {
    return _t('not_available', 'Not available')
  }

  const currency = `${sourceCurrency || pricing.value.currency || search.value.currency || 'EUR'}`

  try {
    return new Intl.NumberFormat(currentLocale.value, {
      style: 'currency',
      currency,
      maximumFractionDigits: 2,
    }).format(amount)
  } catch {
    return `${getCurrencySymbol(currency)}${amount.toFixed(2)}`
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

const quoteDailyPrice = (quote: Quote) => formatDisplayAmount(quote.pricing?.price_per_day, quote.pricing?.currency || props.offerResults.search?.currency)
const dayCountText = (days?: number | null) => {
  const count = days || 1

  return _t(count === 1 ? 'day_count' : 'days_count', count === 1 ? ':count day' : ':count days', { count })
}
const forDayCountText = (days?: number | null) => {
  const count = days || 1

  return _t(count === 1 ? 'for_day_count' : 'for_days_count', count === 1 ? 'For :count day' : 'For :count days', { count })
}

const depositSummary = computed(() => formatDisplayAmount(
  pricing.value.deposit_amount ?? featuredProduct.value?.deposit,
  pricing.value.deposit_amount != null
    ? pricing.value.deposit_currency || pricing.value.currency || search.value.currency
    : featuredProduct.value?.deposit_currency || featuredProduct.value?.currency || pricing.value.currency,
))
const totalPriceText = computed(() => formatDisplayAmount(pricing.value.total_price, pricing.value.currency || search.value.currency))
const payNowText = computed(() => paymentSplit.value === null ? null : formatDisplayAmount(paymentSplit.value.payNow, pricing.value.currency || search.value.currency))
const payLaterText = computed(() => paymentSplit.value === null ? null : formatDisplayAmount(paymentSplit.value.payLater, pricing.value.currency || search.value.currency))
const packageLabel = computed(() => featuredProduct.value?.name || _t('standard_offer', 'Standard offer'))
const rentalDurationText = computed(() => forDayCountText(currentBookingContext.value?.number_of_days || 1))

const pickupContact = computed(() => {
  const parts = [pickupLocation.value.phone, formatCoordinate(pickupLocation.value)].filter(Boolean)
  return parts.length > 0 ? parts.join(' / ') : null
})
const itinerary = computed(() => ({
  pickupWhen: formatDateTime(search.value.pickup_date, search.value.pickup_time),
  pickupName: pickupLocation.value.name || _t('not_specified', 'Not specified'),
  pickupAddress: pickupLocation.value.address || formatLocationMeta(pickupLocation.value),
  // Instructions are shown alongside the address, never instead of it.
  pickupInstructions: stringValue(pickupLocation.value.pickup_instructions ?? currentBookingContext.value?.location_instructions),
  returnWhen: formatDateTime(search.value.dropoff_date, search.value.dropoff_time),
  returnName: dropoffLocation.value.name || pickupLocation.value.name || _t('same_as_pickup', 'Same as pickup'),
  returnAddress: dropoffLocation.value.address || pickupLocation.value.address || _t('return_same_office', 'Return the car to the selected office.'),
  returnInstructions: stringValue(dropoffLocation.value.dropoff_instructions ?? currentBookingContext.value?.dropoff_instructions),
}))

const cancellationSummary = computed(() => {
  const cancellation = policies.value.cancellation
  const description = stringValue(cancellation?.description)

  if (cancellation?.available === true) {
    return cancellation.days_before_pickup != null
      ? _t('free_cancellation_until_days', 'Free cancellation up to :days days before pickup', { days: cancellation.days_before_pickup })
      : (description ?? _t('cancellation_available', 'Cancellation available'))
  }

  if (cancellation?.available === false) {
    return description ?? _t('cancellation_not_available', 'This rate cannot be cancelled')
  }

  return description ?? _t('not_specified', 'Not specified')
})

const termEntries = computed(() => {
  const entries = asRecordArray(currentBookingContext.value?.terms).flatMap((term, index) => {
    const body = stringValue(term.description ?? term.content ?? term.text ?? term.value)

    return body === null ? [] : [{
      key: `term-${stringValue(term.code) ?? index}`,
      title: stringValue(term.title ?? term.name ?? term.label) ?? _t('rental_condition', 'Rental condition'),
      body,
    }]
  })

  const driverRequirements = Object.values(asRecord(currentBookingContext.value?.driver_requirements))
    .map(stringValue)
    .filter((value): value is string => value !== null)
    .join(' · ')

  if (driverRequirements !== '') {
    entries.push({ key: 'driver-requirements', title: _t('driver_requirements', 'Driver requirements'), body: driverRequirements })
  }

  return entries
})

const inclusionGroups = computed(() => {
  const protectionFacts = offerProtectionFacts.value.length > 0
    ? [...offerProtectionFacts.value]
    : [{ key: 'protection-default', label: _t('supplier_cover', 'Supplier cover'), detail: _t('not_specified', 'Not specified') }]
  protectionFacts.push({ key: 'protection-deposit', label: _t('security_deposit', 'Security deposit'), detail: depositSummary.value })

  const extraFacts = [...offerExtraFacts.value]
  if (hasFreeEsim.value) {
    extraFacts.push({ key: 'extra-esim', label: _t('free_esim', 'Free eSIM'), detail: _t('included', 'Included') })
  }
  if (extraFacts.length === 0) {
    extraFacts.push({ key: 'extra-none', label: _t('extras', 'Extras'), detail: _t('not_specified', 'Not specified') })
  }

  return [
    {
      key: 'package',
      title: _t('rental_package', 'Rental package'),
      facts: offerPackageFacts.value.length > 0
        ? offerPackageFacts.value
        : [{ key: 'package-default', label: packageLabel.value, detail: featuredProduct.value?.subtitle || _t('not_specified', 'Not specified') }],
    },
    { key: 'protection', title: _t('protection', 'Protection'), facts: protectionFacts },
    { key: 'extras', title: _t('extras', 'Extras'), facts: extraFacts },
  ]
})

const alternativeSpecs = (offer: Quote) => {
  const offerVehicle = offer.vehicle ?? {}

  return buildOfferSpecFacts({
    class: stringValue(offerVehicle.sipp_code) ?? stringValue(offerVehicle.category) ?? '',
    seats: offerVehicle.seats != null ? _t('seats_count', ':count seats', { count: offerVehicle.seats }) : '',
    transmission: describeTransmission(offerVehicle.transmission),
    fuel: stringValue(offerVehicle.fuel_type) ?? '',
    mileage: describeMileage(offer.policies?.mileage_policy, offer.policies?.mileage_limit_km),
  }).map((fact) => fact.value)
}

const alternativeCards = computed(() => visibleAlternativeQuotes.value.map((offer) => {
  const context = props.bookingContexts?.[offer.quote_id]
  const offerVehicle = offer.vehicle ?? {}

  return {
    quoteId: offer.quote_id,
    title: offerVehicle.display_name || [offerVehicle.brand, offerVehicle.model].filter(Boolean).join(' ') || _t('vehicle_offer', 'Vehicle offer'),
    imageUrl: offerVehicle.image_url ?? null,
    specs: alternativeSpecs(offer),
    total: formatDisplayAmount(offer.pricing?.total_price, offer.pricing?.currency || props.offerResults.search?.currency),
    perDay: quoteDailyPrice(offer),
    // startBooking falls back to the context package, but sending it keeps the
    // selection payload identical to the one the previous card emitted.
    selection: {
      package: context?.initial_package ?? 'BAS',
      protection_code: context?.initial_protection_code ?? null,
    },
    disabled: !canBookQuote(offer.quote_id),
  }
}))

const loadMoreAlternativeQuotes = () => {
  relatedCardsLimit.value = revealMoreAlternatives(relatedCardsLimit.value, alternativeQuotes.value.length)
}

const startBooking = (quoteId: string, selection: Record<string, unknown> | null = null) => {
  if (!canBookQuote(quoteId)) {
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
  pushStepHistory('extras')
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Back button steps back through the booking flow instead of leaving the page
// and dropping the selected extras/package (parity with SearchResults).
const pushStepHistory = (step: BookingStep) => {
  history.pushState(buildOfferHistoryState(history.state, step), '', window.location.href)
}
const handleBackToResults = () => {
  bookingStep.value = 'results'
  activeBookingContext.value = null
  selectedCheckoutData.value = null
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleProceedToCheckout = (data: CheckoutData) => {
  // Offer adapters can legitimately return a context without pickup fields.
  // The checkout screen has no inputs for them, so submitting would 422 into
  // a dead end — block the transition here with a recoverable message instead.
  const ctx = currentBookingContext.value || {}
  const missingTripFields = ['pickup_date', 'pickup_time', 'dropoff_date', 'dropoff_time', 'pickup_location']
    .filter((field) => !String((ctx as Record<string, unknown>)[field] ?? '').trim())
  if (missingTripFields.length) {
    toast.error(_t('offer_context_incomplete', 'This offer is missing trip details. Please search again to get a fresh quote.'))
    return
  }

  selectedCheckoutData.value = data
  bookingStep.value = 'checkout'
  pushStepHistory('checkout')
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const handleBackToExtras = () => {
  bookingStep.value = 'extras'
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const canBookQuote = (quoteId: string) => {
  const context = props.bookingContexts?.[quoteId]
  const percentage = normalizeOfferPaymentPercentage(context?.payment_percentage)
  return !isExpired.value
    && Boolean(context)
    && percentage !== null && percentage > 0
}
const searchAgainUrl = computed(() => {
  if (quoteStatus.value.search_again_url) return quoteStatus.value.search_again_url
  const context = props.bookingContext
  const params = new URLSearchParams()
  const values = {
    where: pickupLocation.value.name || context?.pickup_location,
    dropoff_where: dropoffLocation.value.name || context?.dropoff_location,
    date_from: search.value.pickup_date, date_to: search.value.dropoff_date,
    start_time: search.value.pickup_time, end_time: search.value.dropoff_time,
    age: search.value.driver_age ?? context?.driver_age, currency: search.value.currency || pricing.value.currency,
    latitude: pickupLocation.value.latitude, longitude: pickupLocation.value.longitude,
    dropoff_latitude: dropoffLocation.value.latitude, dropoff_longitude: dropoffLocation.value.longitude,
    unified_location_id: context?.unified_location_id,
    dropoff_unified_location_id: context?.dropoff_unified_location_id,
    provider_pickup_id: context?.provider_pickup_id,
  }
  Object.entries(values).forEach(([key, value]) => {
    if (value != null && value !== '') params.set(key, String(value))
  })
  return `/${encodeURIComponent(currentLocale.value)}/s?${params}`
})
</script>

<template>
  <Head :title="pageTitle" />
  <AuthenticatedHeaderLayout />
  <Toaster class="pointer-events-auto" />

  <OfferSteps
    v-if="bookingStep !== 'results'"
    :step="bookingStep"
    @back-to-offer="handleBackToResults"
    @back-to-extras="handleBackToExtras"
  />

  <div class="or-page" :class="{ 'or-page-offset': bookingStep === 'results' }">
    <template v-if="bookingStep === 'results'">
      <div class="full-w-container or-shell">
        <div v-if="isExpired || !canBookQuote(quote.quote_id)" class="or-alert" role="status">
          <AlertCircle class="or-alert-icon" :size="20" :stroke-width="2" />
          <div class="or-alert-copy">
            <strong>{{ isExpired ? _t('offer_expired', 'Offer expired') : _t('booking_unavailable', 'Booking unavailable') }}</strong>
            <p>{{ isExpired ? (quoteStatus.message || _t('offer_expired_message', 'This offer has expired. Run the search again to see current prices and availability.')) : _t('payment_terms_unavailable', 'Payment terms could not be confirmed. Search again for a bookable offer.') }}</p>
          </div>
          <Link :href="searchAgainUrl" class="or-alert-action">
            {{ _t('search_again', 'Search again') }}
            <ArrowRight :size="16" :stroke-width="2.2" />
          </Link>
        </div>

        <div class="or-layout">
          <div class="or-main">
            <OfferMasthead
              :partner-label="partnerLabel"
              :title="displayName || _t('vehicle_offer', 'Vehicle offer')"
              :image-url="vehicle.image_url"
              :supplier-name="supplierDisplayName"
              :availability-text="offerAvailabilityText"
              :specs="offerSpecFacts"
            />

            <section class="or-card">
              <OfferItinerary
                :duration-text="rentalSummary"
                :pickup-when="itinerary.pickupWhen"
                :pickup-name="itinerary.pickupName"
                :pickup-address="itinerary.pickupAddress"
                :pickup-instructions="itinerary.pickupInstructions"
                :pickup-contact="pickupContact"
                :return-when="itinerary.returnWhen"
                :return-name="itinerary.returnName"
                :return-address="itinerary.returnAddress"
                :return-instructions="itinerary.returnInstructions"
                :fuel-policy="fuelPolicySummary"
                :mileage="mileageSummary"
                :deposit="depositSummary"
              />
            </section>

            <section class="or-card">
              <OfferInclusions :groups="inclusionGroups" />
            </section>

            <section class="or-card">
              <OfferTerms :cancellation="cancellationSummary" :entries="termEntries" />
            </section>

            <OfferAlternatives
              v-if="alternativeQuotes.length > 0"
              class="or-card"
              :offers="alternativeCards"
              :total-count="alternativeQuotes.length"
              :can-reveal="canLoadMoreAlternativeQuotes"
              @select="startBooking"
              @reveal="loadMoreAlternativeQuotes"
            />
          </div>

          <div class="or-rail">
            <OfferSummary
              :total="totalPriceText"
              :pay-now="payNowText"
              :pay-later="payLaterText"
              :payment-percentage="paymentPercentage"
              :duration-text="rentalDurationText"
              :package-label="packageLabel"
              :deposit="depositSummary"
              :disabled="!canBookQuote(quote.quote_id)"
              :expired="isExpired"
              @book="startBooking(quote.quote_id)"
            />
          </div>
        </div>
      </div>

      <OfferActionBar
        :total="totalPriceText"
        :pay-now="payNowText"
        :payment-percentage="paymentPercentage"
        :disabled="!canBookQuote(quote.quote_id)"
        :expired="isExpired"
        @book="startBooking(quote.quote_id)"
      />
    </template>

    <BookingExtrasStep
      v-else-if="bookingStep === 'extras' && selectedVehicle"
      class="full-w-container"
      :vehicle="selectedVehicle"
      :initial-package="selectedPackage"
      :initial-protection-code="selectedProtectionCode ?? undefined"
      :optional-extras="selectedOptionalExtras"
      :currency-symbol="bookingCurrencySymbol"
      :location-name="bookingStepContext.location_name || bookingStepContext.pickup_location || ''"
      :pickup-location="bookingStepContext.pickup_location || ''"
      :dropoff-location="bookingStepContext.dropoff_location || bookingStepContext.pickup_location || ''"
      :dropoff-latitude="bookingStepContext.dropoff_latitude ?? undefined"
      :dropoff-longitude="bookingStepContext.dropoff_longitude ?? undefined"
      :pickup-date="bookingStepContext.pickup_date || ''"
      :pickup-time="bookingStepContext.pickup_time || ''"
      :dropoff-date="bookingStepContext.dropoff_date || ''"
      :dropoff-time="bookingStepContext.dropoff_time || ''"
      :number-of-days="bookingStepContext.number_of_days || 1"
      :location-instructions="bookingStepContext.location_instructions || undefined"
      :location-details="bookingStepContext.location_details || undefined"
      :driver-requirements="bookingStepContext.driver_requirements || undefined"
      :terms="bookingStepContext.terms || undefined"
      :payment-percentage="paymentPercentage ?? 0"
      :search-session-id="checkoutSearchSessionId ?? undefined"
      @back="handleBackToResults"
      @proceed-to-checkout="handleProceedToCheckout"
    />

    <BookingCheckoutStep
      v-else-if="bookingStep === 'checkout' && selectedVehicle && selectedCheckoutData"
      class="full-w-container"
      :vehicle="selectedVehicle"
      :package="selectedCheckoutData.package"
      :protection-code="checkoutProtectionCode"
      :protection-amount="selectedCheckoutData.protection_amount ?? 0"
      :extras="selectedCheckoutData.extras"
      :detailed-extras="selectedCheckoutData.detailedExtras"
      :optional-extras="selectedOptionalExtras"
      :pickup-date="bookingStepContext.pickup_date || ''"
      :pickup-time="bookingStepContext.pickup_time || ''"
      :dropoff-date="bookingStepContext.dropoff_date || ''"
      :dropoff-time="bookingStepContext.dropoff_time || ''"
      :pickup-location="bookingStepContext.pickup_location || ''"
      :dropoff-location="bookingStepContext.dropoff_location || bookingStepContext.pickup_location || ''"
      :number-of-days="bookingStepContext.number_of_days || 1"
      :currency-symbol="bookingCurrencySymbol"
      :selected-currency-code="bookingCurrencyCode"
      :payment-percentage="paymentPercentage ?? 0"
      :totals="selectedCheckoutData.totals"
      :totals-currency="selectedCheckoutData.totals_currency || bookingCurrencyCode"
      :vehicle-total="selectedCheckoutData.vehicle_total ?? 0"
      :vehicle-total-currency="selectedCheckoutData.vehicle_total_currency || bookingCurrencyCode"
      :location-details="bookingStepContext.location_details || undefined"
      :location-instructions="bookingStepContext.location_instructions || undefined"
      :dropoff-location-details="bookingStepContext.dropoff_location_details || undefined"
      :dropoff-instructions="bookingStepContext.dropoff_instructions || undefined"
      :driver-requirements="bookingStepContext.driver_requirements || undefined"
      :terms="bookingStepContext.terms || undefined"
      :search-loaded-at="offerLoadedAt"
      :search-session-id="checkoutSearchSessionId ?? undefined"
      :gateway-search-id="checkoutGatewaySearchId ?? undefined"
      :provider-pickup-id="optionalScalar(bookingStepContext.provider_pickup_id ?? selectedVehicle.provider_pickup_id)"
      :unified-location-id="optionalScalar(bookingStepContext.unified_location_id ?? selectedVehicle.unified_location_id ?? bookingStepContext.location_details?.unified_location_id)"
      :dropoff-unified-location-id="optionalScalar(bookingStepContext.dropoff_unified_location_id ?? selectedVehicle.dropoff_unified_location_id ?? bookingStepContext.dropoff_location_details?.unified_location_id)"
      :driver-age="optionalScalar(bookingStepContext.driver_age ?? selectedVehicle.driver_age ?? search.driver_age)"
      :selected-deposit-type="selectedCheckoutData.selected_deposit_type || undefined"
      @back="handleBackToExtras"
    />
  </div>

  <Footer />
</template>

<style scoped>
.or-page {
  --or-ease: cubic-bezier(0.22, 1, 0.36, 1);
  font-family: 'IBM Plex Sans', sans-serif;
  background: linear-gradient(180deg, #f8fafc 0%, #ffffff 45%, #f1f5f9 100%);
  min-height: 60vh;
}
/* Keeps the mobile action bar from covering the last section. The bar stacks
   its figures above the CTA below 480px, so the reserved space is taller there. */
.or-page-offset { padding-bottom: calc(126px + env(safe-area-inset-bottom)); }

.or-shell { padding-block: clamp(1.5rem, 4vw, 3rem); }

.or-alert {
  display: grid; grid-template-columns: auto minmax(0, 1fr); gap: 10px 12px; align-items: start;
  margin-bottom: 20px; padding: 16px 18px; border-radius: 14px;
  background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412;
}
.or-alert-icon { color: #d97706; margin-top: 1px; }
.or-alert-copy strong { display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9rem; font-weight: 700; }
.or-alert-copy p { margin: 4px 0 0; font-size: 0.83rem; line-height: 1.55; }
.or-alert-action {
  grid-column: 2; justify-self: start; display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 16px; border-radius: 999px; background: #153b4f; color: #fff;
  font-size: 0.8rem; font-weight: 600; text-decoration: none;
  transition: background 0.3s var(--or-ease), transform 0.3s var(--or-ease);
}
.or-alert-action:hover { background: #1c4d66; transform: translateY(-1px); }
.or-alert-action:focus-visible { outline: 2px solid #22d3ee; outline-offset: 3px; }

.or-layout { display: grid; gap: 20px; align-items: start; }
.or-main { display: grid; gap: 20px; min-width: 0; }

.or-card {
  background: #fff; border: 1px solid #e2e8f0; border-radius: 18px;
  padding: clamp(1.25rem, 3.5vw, 1.75rem);
  box-shadow: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
}

@media (min-width: 480px) { .or-page-offset { padding-bottom: calc(82px + env(safe-area-inset-bottom)); } }
@media (min-width: 1024px) {
  .or-page-offset { padding-bottom: 0; }
  .or-layout { grid-template-columns: minmax(0, 1fr) 356px; gap: 24px; }
  .or-rail { position: sticky; top: 20px; }
}
@media (prefers-reduced-motion: reduce) {
  .or-alert-action { transition: none; }
  .or-alert-action:hover { transform: none; }
}
</style>
