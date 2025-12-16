<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue'
import Footer from '@/Components/Footer.vue'
import { useCurrency } from '@/composables/useCurrency'
import LocautoStripeCheckout from '@/Components/LocautoStripeCheckout.vue'
import axios from 'axios'

const paymentPercentage = ref(0.00)

// Icons
import check from "../../assets/Check.svg"
import carIcon from "../../assets/carIcon.svg"
import pickupLocationIcon from "../../assets/pickupLocationIcon.svg"
import returnLocationIcon from "../../assets/returnLocationIcon.svg"

const props = defineProps({
  vehicle: Object,
  location: Object,
  protectionPlans: Array,
  optionalExtras: Array,
  filters: Object,
  error: String
})

const page = usePage()
const locale = page.props.locale || 'en'
const { selectedCurrency } = useCurrency()

// State
const currentStep = ref(1)
const agreedToTerms = ref(false)
const agreedToPrivacy = ref(false)
const agreedToCancellation = ref(false)
const isSubmitting = ref(false)
const selectedProtection = ref(null) // Single protection selection
const selectedExtras = ref([])
const stripeError = ref('') // For Stripe error handling
const showErrorDialog = ref(false)

// Check if vehicle exists
const hasVehicle = computed(() => props.vehicle && Object.keys(props.vehicle).length > 0)

// Vehicle computed properties
const vehicleModel = computed(() => props.vehicle?.model || 'Locauto Vehicle')
const vehicleImage = computed(() => props.vehicle?.image || '/images/default-car.jpg')
const sippCode = computed(() => props.vehicle?.sipp_code || '')
const transmission = computed(() => props.vehicle?.transmission === 'automatic' ? 'Automatic' : 'Manual')
const totalAmount = computed(() => parseFloat(props.vehicle?.total_amount || 0))
const currency = computed(() => props.vehicle?.currency || 'EUR')

// Dates
const pickupDate = computed(() => props.vehicle?.date_from || props.filters?.start_date || '')
const returnDate = computed(() => props.vehicle?.date_to || props.filters?.end_date || '')
const pickupTime = computed(() => props.vehicle?.start_time || props.filters?.start_time || '09:00')
const returnTime = computed(() => props.vehicle?.end_time || props.filters?.end_time || '09:00')

const rentalDays = computed(() => {
    if (!pickupDate.value || !returnDate.value) return 1
    const pickup = new Date(pickupDate.value)
    const returnD = new Date(returnDate.value)
    const diffTime = Math.abs(returnD.getTime() - pickup.getTime())
    return Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)))
})

// Protection total (single selection)
const protectionTotal = computed(() => {
    if (!selectedProtection.value) return 0
    const plan = (props.protectionPlans || []).find(p => p.code === selectedProtection.value)
    return plan ? plan.amount : 0
})

// Extras total (includes both optional extras and safety/assistance items)
const extrasTotal = computed(() => {
    let total = 0
    // Safety and assistance items come from protectionPlans after the first 2
    const safetyAssistanceItems = (props.protectionPlans || []).slice(2)
    const allExtras = [...(props.optionalExtras || []), ...safetyAssistanceItems]
    
    selectedExtras.value.forEach(code => {
        const extra = allExtras.find(e => e.code === code)
        if (extra) total += extra.amount
    })
    return total
})

const grandTotal = computed(() => totalAmount.value + protectionTotal.value + extrasTotal.value)

const calculateAmountPaid = computed(() => {
    const total = grandTotal.value
    const effectivePercentage = paymentPercentage.value === 0 ? 100 : paymentPercentage.value
    return Number((total * (effectivePercentage / 100)).toFixed(2))
})

const calculatePendingAmount = computed(() => {
    const total = grandTotal.value
    const effectivePercentage = paymentPercentage.value === 0 ? 100 : paymentPercentage.value
    return Number((total * (1 - (effectivePercentage / 100))).toFixed(2))
})

// Form
const bookingForm = useForm({
  first_name: '',
  last_name: '',
  email: page.props.auth?.user?.email || '',
  phone: '',
  driver_age: 30, // Default driver age
  date_of_birth: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  driver_license_number: '',
  driver_license_country: '',
  driver_license_expiry: '',
  flight_number: '',
  special_requests: '',
})

// Error handling
const handleStripeError = (message) => {
    stripeError.value = message || 'Something went wrong, please try again later.'
    showErrorDialog.value = true
    isSubmitting.value = false
}

// Computed property for Stripe booking data
const bookingDataForStripe = computed(() => ({
    customer: {
        first_name: bookingForm.first_name,
        last_name: bookingForm.last_name,
        email: bookingForm.email,
        phone: bookingForm.phone,
        driver_age: bookingForm.driver_age,
        driver_license_number: bookingForm.driver_license_number,
        flight_number: bookingForm.flight_number,
    },
    pickup_location_code: props.location?.code || props.filters?.location_id || '',
    dropoff_location_code: props.filters?.dropoff_location_id || props.location?.code || '',
    pickup_date: pickupDate.value,
    pickup_time: pickupTime.value,
    return_date: returnDate.value,
    return_time: returnTime.value,
    vehicle_code: sippCode.value || props.vehicle?.id || '',
    vehicle_model: vehicleModel.value,
    sipp_code: sippCode.value,
    selected_protection: selectedProtection.value,
    selected_extras: selectedExtras.value,
    grand_total: grandTotal.value,
    currency: currency.value,
}))

// Price formatting
const currencySymbols = ref({})
const exchangeRates = ref(null)

onMounted(async () => {
    try {
        const [currencyRes, ratesRes] = await Promise.all([
            fetch('/currency.json'),
            fetch(`${import.meta.env.VITE_EXCHANGERATE_API_BASE_URL}/v6/${import.meta.env.VITE_EXCHANGERATE_API_KEY}/latest/USD`)
        ])
        
        const currencyData = await currencyRes.json()
        currencySymbols.value = currencyData.reduce((acc, curr) => {
            acc[curr.code] = curr.symbol
            return acc
        }, {})
        
        const ratesData = await ratesRes.json()
        if (ratesData.result === 'success') {
            exchangeRates.value = ratesData.conversion_rates
        }
    } catch (error) {
        console.error("Error loading currency data:", error)
    }

    // Fetch payment percentage
    try {
        const response = await axios.get('/api/payment-percentage')
        if (response.data && response.data.payment_percentage !== undefined) {
            paymentPercentage.value = Number(response.data.payment_percentage)
        }
    } catch (error) {
        console.error('Error fetching payment percentage:', error)
    }
})

const formatPrice = (amount, fromCurrency = 'EUR') => {
    const numericAmount = parseFloat(amount)
    if (isNaN(numericAmount)) return '€0.00'
    
    if (!exchangeRates.value || !selectedCurrency.value) {
        return `€${numericAmount.toFixed(2)}`
    }
    
    const rateFrom = exchangeRates.value[fromCurrency] || 1
    const rateTo = exchangeRates.value[selectedCurrency.value] || 1
    const converted = (numericAmount / rateFrom) * rateTo
    const symbol = currencySymbols.value[selectedCurrency.value] || '€'
    
    return `${symbol}${converted.toFixed(2)}`
}

const formatDateTime = (date, time) => {
    if (!date) return ''
    try {
        const dateObj = new Date(date + 'T' + (time || '09:00'))
        return dateObj.toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        }) + ', ' + (time || '09:00')
    } catch (e) {
        return date + ' ' + time
    }
}

// Toggle functions
const selectProtection = (code) => {
    selectedProtection.value = selectedProtection.value === code ? null : code
}

const toggleExtra = (code) => {
    const index = selectedExtras.value.indexOf(code)
    if (index === -1) {
        selectedExtras.value.push(code)
    } else {
        selectedExtras.value.splice(index, 1)
    }
}

// Navigation - 4 steps: Protection -> Extras -> Your Details -> Confirm
const totalSteps = 4

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1: return true // Protection is optional
    case 2: return true // Extras are optional  
    case 3:
      return bookingForm.first_name && bookingForm.last_name && bookingForm.email && bookingForm.phone && bookingForm.driver_license_number
    case 4:
      return agreedToTerms.value && agreedToPrivacy.value && agreedToCancellation.value
    default:
      return false
  }
})

function nextStep() {
  if (canProceed.value && currentStep.value < totalSteps) {
    currentStep.value++
  }
}

function previousStep() {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

function submitBooking() {
  if (!canProceed.value || isSubmitting.value) return
  isSubmitting.value = true

  const bookingData = {
    ...bookingForm.data(),
    vehicle_id: props.vehicle?.id,
    vehicle_model: vehicleModel.value,
    sipp_code: sippCode.value,
    pickup_location_id: props.filters?.location_id,
    dropoff_location_id: props.filters?.dropoff_location_id || props.filters?.location_id,
    pickup_date: pickupDate.value,
    pickup_time: pickupTime.value,
    return_date: returnDate.value,
    return_time: returnTime.value,
    selected_protection: selectedProtection.value,
    selected_extras: selectedExtras.value,
    total_amount: grandTotal.value,
    amount_paid: calculateAmountPaid.value,
    pending_amount: calculatePendingAmount.value,
    currency: currency.value,
  }

  router.post(`/${locale}/locauto-rent-booking`, bookingData, {
    onSuccess: () => {
      isSubmitting.value = false
    },
    onError: (errors) => {
      console.error('Booking errors:', errors)
      isSubmitting.value = false
    }
  })
}

const backToVehicle = computed(() => {
    return `/${locale}/locauto-rent-car/${props.vehicle?.id}?location_id=${props.filters?.location_id}&start_date=${pickupDate.value}&start_time=${pickupTime.value}&end_date=${returnDate.value}&end_time=${returnTime.value}`
})
</script>

<template>
  <Head :title="`Book ${vehicleModel} - Locauto Rent`" />

  <AuthenticatedHeaderLayout />

  <!-- Hero -->
  <section class="bg-gradient-to-r from-customPrimaryColor to-blue-700 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Rental Summary Bar -->
      <div class="bg-white/10 backdrop-blur-sm rounded-xl p-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-6 flex-wrap">
          <div class="text-white">
            <p class="text-xs text-blue-200">PICK-UP & RETURN</p>
            <p class="font-semibold">{{ location?.name || 'Location' }}</p>
            <p class="text-sm text-blue-200">{{ formatDateTime(pickupDate, pickupTime) }} - {{ formatDateTime(returnDate, returnTime) }}</p>
          </div>
          <div class="text-white">
            <p class="text-xs text-blue-200">VEHICLE</p>
            <p class="font-semibold">{{ vehicleModel }}</p>
            <p class="text-sm text-blue-200">{{ sippCode }} • {{ transmission }}</p>
          </div>
          <div v-if="selectedProtection" class="text-white">
            <p class="text-xs text-blue-200">PROTECTION</p>
            <p class="font-semibold">{{ (protectionPlans || []).find(p => p.code === selectedProtection)?.description || 'Basic' }}</p>
          </div>
        </div>
        <div class="text-right text-white">
          <p class="text-xs text-blue-200">TOTAL</p>
          <p class="text-2xl font-bold">{{ formatPrice(grandTotal, currency) }}</p>
        </div>
      </div>
    </div>
  </section>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Error State -->
    <div v-if="error || !hasVehicle" class="text-center py-16">
      <div class="bg-red-50 border border-red-200 rounded-2xl p-8 max-w-md mx-auto">
        <p class="text-red-600 text-xl font-semibold mb-4">{{ error || 'Booking data not found' }}</p>
        <Link :href="`/${locale}/s`" class="inline-flex items-center gap-2 bg-customPrimaryColor text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
          Back to Search
        </Link>
      </div>
    </div>

    <div v-else>
      <!-- Progress Steps -->
      <div class="mb-8">
        <div class="flex items-center justify-center">
          <div v-for="(step, index) in ['Protection', 'Extras', 'Your Details', 'Confirm']" :key="index" class="flex items-center">
            <div
              :class="[
                'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold',
                currentStep > index + 1 ? 'bg-green-500 text-white' : 
                currentStep === index + 1 ? 'bg-customPrimaryColor text-white' : 'bg-gray-300 text-gray-600'
              ]"
            >
              <svg v-if="currentStep > index + 1" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/>
              </svg>
              <span v-else>{{ index + 1 }}</span>
            </div>
            <div v-if="index < 3" :class="['w-16 md:w-24 h-1 mx-2', currentStep > index + 1 ? 'bg-green-500' : 'bg-gray-300']" />
          </div>
        </div>
        <div class="mt-3 flex justify-center gap-4 md:gap-12 text-xs md:text-sm">
          <span v-for="(step, index) in ['Protection', 'Extras', 'Your Details', 'Confirm']" :key="index"
                :class="currentStep >= index + 1 ? 'text-customPrimaryColor font-semibold' : 'text-gray-500'">
            {{ step }}
          </span>
        </div>
      </div>

      <!-- Main Content -->
      <div class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
        
        <!-- Step 1: Protection Plans -->
        <div v-if="currentStep === 1">
          <h2 class="text-xl font-bold text-gray-900 mb-2">Choose the ideal protection for your rental</h2>
          <p class="text-gray-600 mb-6">Select a protection level that suits your needs</p>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Basic Option (always available) -->
            <div @click="selectProtection(null)"
                 :class="[
                   'p-5 rounded-xl border-2 cursor-pointer transition-all',
                   !selectedProtection ? 'border-customPrimaryColor bg-blue-50' : 'border-gray-200 hover:border-blue-300'
                 ]">
              <div class="flex justify-between items-start mb-3">
                <h3 class="font-bold text-gray-900">Basic</h3>
                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center',
                             !selectedProtection ? 'bg-customPrimaryColor border-customPrimaryColor' : 'border-gray-300']">
                  <div v-if="!selectedProtection" class="w-2 h-2 bg-white rounded-full"></div>
                </div>
              </div>
              <p class="text-sm text-gray-600 mb-4">Included with rental - limited liability coverage</p>
              <ul class="text-xs text-gray-600 space-y-1 mb-4">
                <li class="flex items-center gap-2"><span class="text-orange-500">●</span> Damages liability up to €1500</li>
                <li class="flex items-center gap-2"><span class="text-orange-500">●</span> Theft liability up to €2000</li>
              </ul>
              <div class="border-t pt-3">
                <p class="text-lg font-bold text-green-600">0 € <span class="text-sm font-normal text-gray-500">/ per day</span></p>
              </div>
            </div>

            <!-- Protection Plans from API -->
            <div v-for="plan in (protectionPlans || []).slice(0, 2)" :key="plan.code"
                 @click="selectProtection(plan.code)"
                 :class="[
                   'p-5 rounded-xl border-2 cursor-pointer transition-all',
                   selectedProtection === plan.code ? 'border-customPrimaryColor bg-blue-50' : 'border-gray-200 hover:border-blue-300'
                 ]">
              <div class="flex justify-between items-start mb-3">
                <h3 class="font-bold text-gray-900">{{ plan.description.split('/')[0].trim() }}</h3>
                <div :class="['w-5 h-5 rounded-full border-2 flex items-center justify-center',
                             selectedProtection === plan.code ? 'bg-customPrimaryColor border-customPrimaryColor' : 'border-gray-300']">
                  <div v-if="selectedProtection === plan.code" class="w-2 h-2 bg-white rounded-full"></div>
                </div>
              </div>
              <p class="text-sm text-gray-600 mb-4">{{ plan.description }}</p>
              <ul class="text-xs text-gray-600 space-y-1 mb-4">
                <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Reduced damage excess</li>
                <li class="flex items-center gap-2"><span class="text-green-500">✓</span> Theft protection</li>
              </ul>
              <div class="border-t pt-3">
                <p class="text-lg font-bold text-customPrimaryColor">{{ formatPrice(plan.amount, plan.currency) }} <span class="text-sm font-normal text-gray-500">/ rental</span></p>
              </div>
            </div>
          </div>
        </div>

        <!-- Step 2: Extra Options -->
        <div v-if="currentStep === 2">
          <h2 class="text-xl font-bold text-gray-900 mb-2">Make your rental more complete</h2>
          <p class="text-gray-600 mb-6">Add optional extras to enhance your experience</p>
          
          <!-- Safety and Assistance -->
          <div v-if="protectionPlans && protectionPlans.length > 2" class="mb-8">
            <h3 class="font-semibold text-gray-800 mb-4">Safety and assistance</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-for="plan in (protectionPlans || []).slice(2)" :key="plan.code"
                   @click="toggleExtra(plan.code)"
                   :class="[
                     'p-4 rounded-xl border-2 cursor-pointer transition-all',
                     selectedExtras.includes(plan.code) ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300'
                   ]">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-semibold text-gray-900 text-sm">{{ plan.description.split('/')[0].trim() }}</h4>
                  <button :class="[
                    'px-3 py-1 text-xs font-semibold rounded',
                    selectedExtras.includes(plan.code) ? 'bg-green-500 text-white' : 'bg-customPrimaryColor text-white'
                  ]">
                    {{ selectedExtras.includes(plan.code) ? 'SELECTED' : 'SELECT' }}
                  </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">{{ plan.description }}</p>
                <p class="text-sm font-bold text-customPrimaryColor">{{ formatPrice(plan.amount, plan.currency) }}</p>
              </div>
            </div>
          </div>

          <!-- Extra Optionals -->
          <div v-if="optionalExtras && optionalExtras.length > 0">
            <h3 class="font-semibold text-gray-800 mb-4">Extra optionals</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div v-for="extra in optionalExtras" :key="extra.code"
                   @click="toggleExtra(extra.code)"
                   :class="[
                     'p-4 rounded-xl border-2 cursor-pointer transition-all',
                     selectedExtras.includes(extra.code) ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-green-300'
                   ]">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-semibold text-gray-900 text-sm">{{ extra.description.split('/')[0].trim() }}</h4>
                  <button :class="[
                    'px-3 py-1 text-xs font-semibold rounded',
                    selectedExtras.includes(extra.code) ? 'bg-green-500 text-white' : 'bg-customPrimaryColor text-white'
                  ]">
                    {{ selectedExtras.includes(extra.code) ? 'SELECTED' : 'SELECT' }}
                  </button>
                </div>
                <p class="text-xs text-gray-600 mb-2">{{ extra.description }}</p>
                <p class="text-sm font-bold text-customPrimaryColor">{{ formatPrice(extra.amount, extra.currency) }}</p>
              </div>
            </div>
          </div>

          <div v-if="(!protectionPlans || protectionPlans.length <= 2) && (!optionalExtras || optionalExtras.length === 0)" 
               class="text-center py-8 text-gray-500">
            <p>No additional extras available for this vehicle.</p>
          </div>
        </div>

        <!-- Step 3: Your Details -->
        <div v-if="currentStep === 3">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Your Details</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
              <input v-model="bookingForm.first_name" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
              <input v-model="bookingForm.last_name" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input v-model="bookingForm.email" type="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
              <input v-model="bookingForm.phone" type="tel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
          </div>

          <h3 class="text-lg font-semibold text-gray-900 mb-4">Driver License</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">License Number *</label>
              <input v-model="bookingForm.driver_license_number" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Driver Age *</label>
              <input v-model="bookingForm.driver_age" type="number" min="18" max="99" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Issuing Country *</label>
              <input v-model="bookingForm.driver_license_country" type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
              <input v-model="bookingForm.driver_license_expiry" type="date" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Flight Number (Optional)</label>
              <input v-model="bookingForm.flight_number" type="text" placeholder="e.g., AZ2020" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Special Requests (Optional)</label>
            <textarea v-model="bookingForm.special_requests" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-customPrimaryColor focus:border-transparent" placeholder="Any special requirements..."></textarea>
          </div>
        </div>

        <!-- Step 4: Confirm -->
        <div v-if="currentStep === 4">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Confirm Your Booking</h2>

          <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
            <h3 class="font-semibold text-green-800 mb-2">✓ Secure Online Payment</h3>
            <p class="text-sm text-green-700">You will be redirected to a secure payment page to complete your booking of {{ formatPrice(paymentPercentage > 0 ? calculateAmountPaid : grandTotal, currency) }}.</p>
          </div>

          <!-- Booking Summary -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-xl">
              <h4 class="font-semibold text-gray-900 mb-2">Customer Details</h4>
              <p class="text-sm text-gray-600">{{ bookingForm.first_name }} {{ bookingForm.last_name }}</p>
              <p class="text-sm text-gray-600">{{ bookingForm.email }}</p>
              <p class="text-sm text-gray-600">{{ bookingForm.phone }}</p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
              <h4 class="font-semibold text-gray-900 mb-2">Price Breakdown</h4>
              <div class="text-sm space-y-1">
                <div class="flex justify-between"><span>Vehicle</span><span>{{ formatPrice(totalAmount, currency) }}</span></div>
                <div v-if="protectionTotal > 0" class="flex justify-between"><span>Protection</span><span>{{ formatPrice(protectionTotal, currency) }}</span></div>
                <div v-if="extrasTotal > 0" class="flex justify-between"><span>Extras</span><span>{{ formatPrice(extrasTotal, currency) }}</span></div>
                <div class="flex justify-between font-bold pt-2 border-t"><span>Total</span><span class="text-customPrimaryColor">{{ formatPrice(grandTotal, currency) }}</span></div>
                <div v-if="paymentPercentage > 0" class="pt-2 mt-2 border-t border-dashed">
                    <div class="flex justify-between font-medium text-green-600">
                        <span>Pay {{ paymentPercentage }}% now</span>
                        <span>{{ formatPrice(calculateAmountPaid, currency) }}</span>
                    </div>
                    <div class="flex justify-between font-medium text-customPrimaryColor">
                        <span>Rest pay on arrival</span>
                        <span>{{ formatPrice(calculatePendingAmount, currency) }}</span>
                    </div>
                </div>
              </div>
            </div>
          </div>

          <div class="space-y-4">
            <label class="flex items-start cursor-pointer">
              <input v-model="agreedToTerms" type="checkbox" class="mt-1 h-4 w-4 text-customPrimaryColor focus:ring-customPrimaryColor border-gray-300 rounded" />
              <span class="ml-3 text-sm text-gray-700">I agree to the <a href="/terms" class="text-customPrimaryColor hover:underline">Terms and Conditions</a></span>
            </label>
            <label class="flex items-start cursor-pointer">
              <input v-model="agreedToPrivacy" type="checkbox" class="mt-1 h-4 w-4 text-customPrimaryColor focus:ring-customPrimaryColor border-gray-300 rounded" />
              <span class="ml-3 text-sm text-gray-700">I agree to the <a href="/privacy" class="text-customPrimaryColor hover:underline">Privacy Policy</a></span>
            </label>
            <label class="flex items-start cursor-pointer">
              <input v-model="agreedToCancellation" type="checkbox" class="mt-1 h-4 w-4 text-customPrimaryColor focus:ring-customPrimaryColor border-gray-300 rounded" />
              <span class="ml-3 text-sm text-gray-700">I understand that cancellations are free up to 24 hours before pickup</span>
            </label>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex justify-between">
          <button v-if="currentStep > 1" @click="previousStep" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
            Previous
          </button>
          <Link v-else :href="backToVehicle" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
            Back to Vehicle
          </Link>

          <button v-if="currentStep < totalSteps" @click="nextStep"
                  class="px-8 py-3 bg-customPrimaryColor text-white rounded-lg hover:bg-blue-700 font-semibold flex items-center gap-2">
            Continue
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <div v-if="currentStep === totalSteps && canProceed" class="w-full">
            <LocautoStripeCheckout 
              :booking-data="bookingDataForStripe" 
              @error="handleStripeError" 
            />
          </div>
          <button v-else-if="currentStep === totalSteps" disabled
                  class="px-8 py-3 bg-gray-400 text-white rounded-lg cursor-not-allowed font-semibold">
            Please complete all required fields
          </button>
        </div>
      </div>
    </div>
  </div>

  <Footer />

  <!-- Error Dialog -->
  <div v-if="showErrorDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md mx-4">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Payment Error</h3>
      </div>
      <p class="text-gray-600 mb-6">{{ stripeError }}</p>
      <button @click="showErrorDialog = false" class="w-full px-4 py-2 bg-customPrimaryColor text-white rounded-lg hover:bg-blue-700">
        Close
      </button>
    </div>
  </div>
</template>