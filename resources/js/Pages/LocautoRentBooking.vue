<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'

// Define interfaces
interface BookingData {
  vehicle_code: string
  vehicle_details: Record<string, any>
  pickup_location_code: string
  dropoff_location_code: string
  pickup_date: string
  pickup_time: string
  return_date: string
  return_time: string
  driver_age: number
  selected_extras: Array<{
    code: string
    name: string
    price: number
    quantity: number
    total: number
  }>
  total_amount: number
  currency: string
}

interface Props {
  bookingData: BookingData
}

const props = defineProps<Props>()

// State
const currentStep = ref(1)
const agreedToTerms = ref(false)
const agreedToPrivacy = ref(false)
const agreedToCancellation = ref(false)

const bookingForm = useForm({
  // Customer Details
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  date_of_birth: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',

  // Driver Details
  driver_license_number: '',
  driver_license_country: '',
  driver_license_expiry: '',
  driver_license_issue_date: '',
  driver_age: props.bookingData.driver_age,

  // Flight Details (optional)
  flight_number: '',
  flight_arrival_time: '',

  // Special Requests
  special_requests: '',

  // Vehicle and rental details
  vehicle_code: props.bookingData.vehicle_code,
  vehicle_details: props.bookingData.vehicle_details,
  pickup_location_code: props.bookingData.pickup_location_code,
  dropoff_location_code: props.bookingData.dropoff_location_code,
  pickup_date: props.bookingData.pickup_date,
  pickup_time: props.bookingData.pickup_time,
  return_date: props.bookingData.return_date,
  return_time: props.bookingData.return_time,
  selected_extras: props.bookingData.selected_extras,
  total_amount: props.bookingData.total_amount,
  currency: props.bookingData.currency,
  payment_type: 'POA', // Pay on Arrival
})

// Computed properties
const totalSteps = 4
const vehicle = computed(() => props.bookingData.vehicle_details)
const rentalDays = computed(() => {
  const pickup = new Date(props.bookingData.pickup_date)
  const returnDate = new Date(props.bookingData.return_date)
  const diffTime = Math.abs(returnDate.getTime() - pickup.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const extrasTotal = computed(() => {
  return props.bookingData.selected_extras.reduce((total, extra) => total + extra.total, 0)
})

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 1:
      return bookingForm.first_name &&
             bookingForm.last_name &&
             bookingForm.email &&
             bookingForm.phone &&
             bookingForm.date_of_birth
    case 2:
      return bookingForm.driver_license_number &&
             bookingForm.driver_license_country &&
             bookingForm.driver_license_expiry
    case 3:
      return true
    case 4:
      return agreedToTerms.value && agreedToPrivacy.value && agreedToCancellation.value
    default:
      return false
  }
})

// Methods
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
  if (!canProceed.value) return

  bookingForm.post(route('locauto.booking.confirm'), {
    onSuccess: () => {
      // Redirect to success page handled by controller
    },
    onError: (errors) => {
      console.error('Booking errors:', errors)
    }
  })
}

function formatPrice(amount: number, currency: string): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

function formatDateTime(date: string, time: string): string {
  return new Date(date + ' ' + time).toLocaleString()
}

function getTransmissionType(type: string): string {
  const types = {
    'Automatic': 'Automatic',
    'Manual': 'Manual'
  }
  return types[type] || type
}
</script>

<template>
  <Head title="Complete Booking - Locauto Rent" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
          <h1 class="text-3xl font-bold text-gray-900">Complete Your Booking</h1>
          <p class="text-gray-600 mt-1">Pay on Arrival - No payment required now</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
          <div class="flex items-center">
            <div
              v-for="step in totalSteps"
              :key="step"
              class="flex items-center"
            >
              <div
                :class="[
                  'w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold',
                  currentStep >= step ? 'bg-blue-600 text-white' : 'bg-gray-300 text-gray-600'
                ]"
              >
                {{ step }}
              </div>
              <div
                v-if="step < totalSteps"
                :class="[
                  'w-full h-1 mx-2',
                  currentStep > step ? 'bg-blue-600' : 'bg-gray-300'
                ]"
              />
            </div>
          </div>
          <div class="mt-4 flex justify-between text-sm">
            <span :class="currentStep >= 1 ? 'text-blue-600 font-semibold' : 'text-gray-500'">
              Customer Details
            </span>
            <span :class="currentStep >= 2 ? 'text-blue-600 font-semibold' : 'text-gray-500'">
              Driver License
            </span>
            <span :class="currentStep >= 3 ? 'text-blue-600 font-semibold' : 'text-gray-500'">
              Review
            </span>
            <span :class="currentStep >= 4 ? 'text-blue-600 font-semibold' : 'text-gray-500'">
              Confirm
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Content -->
          <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-6">
              <!-- Step 1: Customer Details -->
              <div v-if="currentStep === 1">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Customer Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      First Name *
                    </label>
                    <input
                      v-model="bookingForm.first_name"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Last Name *
                    </label>
                    <input
                      v-model="bookingForm.last_name"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Email Address *
                    </label>
                    <input
                      v-model="bookingForm.email"
                      type="email"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Phone Number *
                    </label>
                    <input
                      v-model="bookingForm.phone"
                      type="tel"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Date of Birth *
                    </label>
                    <input
                      v-model="bookingForm.date_of_birth"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Country *
                    </label>
                    <input
                      v-model="bookingForm.country"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Address *
                    </label>
                    <input
                      v-model="bookingForm.address"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      City *
                    </label>
                    <input
                      v-model="bookingForm.city"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Postal Code *
                    </label>
                    <input
                      v-model="bookingForm.postal_code"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                </div>
              </div>

              <!-- Step 2: Driver License -->
              <div v-if="currentStep === 2">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Driver License Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      License Number *
                    </label>
                    <input
                      v-model="bookingForm.driver_license_number"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Issuing Country *
                    </label>
                    <input
                      v-model="bookingForm.driver_license_country"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Issue Date *
                    </label>
                    <input
                      v-model="bookingForm.driver_license_issue_date"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Expiry Date *
                    </label>
                    <input
                      v-model="bookingForm.driver_license_expiry"
                      type="date"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      required
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Flight Number (Optional)
                    </label>
                    <input
                      v-model="bookingForm.flight_number"
                      type="text"
                      placeholder="e.g., AZ2020"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Flight Arrival Time (Optional)
                    </label>
                    <input
                      v-model="bookingForm.flight_arrival_time"
                      type="time"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    />
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                      Special Requests (Optional)
                    </label>
                    <textarea
                      v-model="bookingForm.special_requests"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Any special requirements or requests..."
                    ></textarea>
                  </div>
                </div>
              </div>

              <!-- Step 3: Review -->
              <div v-if="currentStep === 3">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Review Your Booking</h2>
                <div class="space-y-6">
                  <!-- Customer Summary -->
                  <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Customer Details</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <p class="text-sm"><strong>Name:</strong> {{ bookingForm.first_name }} {{ bookingForm.last_name }}</p>
                      <p class="text-sm"><strong>Email:</strong> {{ bookingForm.email }}</p>
                      <p class="text-sm"><strong>Phone:</strong> {{ bookingForm.phone }}</p>
                      <p class="text-sm"><strong>Date of Birth:</strong> {{ bookingForm.date_of_birth }}</p>
                      <p class="text-sm"><strong>Address:</strong> {{ bookingForm.address }}, {{ bookingForm.city }}, {{ bookingForm.postal_code }}, {{ bookingForm.country }}</p>
                    </div>
                  </div>

                  <!-- Driver License Summary -->
                  <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Driver License</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <p class="text-sm"><strong>License Number:</strong> {{ bookingForm.driver_license_number }}</p>
                      <p class="text-sm"><strong>Issuing Country:</strong> {{ bookingForm.driver_license_country }}</p>
                      <p class="text-sm"><strong>Expiry Date:</strong> {{ bookingForm.driver_license_expiry }}</p>
                    </div>
                  </div>

                  <!-- Rental Summary -->
                  <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Rental Details</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <p class="text-sm"><strong>Pick-up:</strong> {{ bookingData.pickup_location_code }} on {{ formatDateTime(bookingData.pickup_date, bookingData.pickup_time) }}</p>
                      <p class="text-sm"><strong>Drop-off:</strong> {{ bookingData.dropoff_location_code }} on {{ formatDateTime(bookingData.return_date, bookingData.return_time) }}</p>
                      <p class="text-sm"><strong>Duration:</strong> {{ rentalDays }} days</p>
                    </div>
                  </div>

                  <!-- Vehicle Summary -->
                  <div>
                    <h3 class="font-semibold text-gray-900 mb-2">Vehicle</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <p class="text-sm"><strong>Model:</strong> {{ vehicle.veh_make_model?.['@name'] || 'N/A' }}</p>
                      <p class="text-sm"><strong>SIPP Code:</strong> {{ bookingData.vehicle_code }}</p>
                      <p class="text-sm"><strong>Passengers:</strong> {{ vehicle.passenger_quantity || 'N/A' }}</p>
                      <p class="text-sm"><strong>Transmission:</strong> {{ getTransmissionType(vehicle.transmission_type || 'N/A') }}</p>
                      <p class="text-sm"><strong>Air Conditioning:</strong> {{ vehicle.air_condition_ind ? 'Yes' : 'No' }}</p>
                    </div>
                  </div>

                  <!-- Extras Summary -->
                  <div v-if="bookingData.selected_extras.length > 0">
                    <h3 class="font-semibold text-gray-900 mb-2">Selected Extras</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                      <div v-for="extra in bookingData.selected_extras" :key="extra.code" class="flex justify-between py-1">
                        <span class="text-sm">{{ extra.name }} ({{ extra.quantity }} days)</span>
                        <span class="text-sm font-medium">{{ formatPrice(extra.total, bookingData.currency) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Step 4: Confirm -->
              <div v-if="currentStep === 4">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Confirm Your Booking</h2>

                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                  <h3 class="font-semibold text-green-800 mb-2">Pay on Arrival</h3>
                  <p class="text-sm text-green-700">
                    No payment is required now. You will pay the total amount when you pick up the vehicle.
                  </p>
                </div>

                <div class="space-y-4">
                  <label class="flex items-start cursor-pointer">
                    <input
                      v-model="agreedToTerms"
                      type="checkbox"
                      class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <span class="ml-3 text-sm text-gray-700">
                      I have read and agree to the <a href="/terms" class="text-blue-600 hover:text-blue-800">Terms and Conditions</a> and the <a href="/locauto/rent-conditions" class="text-blue-600 hover:text-blue-800">Locauto Rent Conditions</a>.
                    </span>
                  </label>

                  <label class="flex items-start cursor-pointer">
                    <input
                      v-model="agreedToPrivacy"
                      type="checkbox"
                      class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <span class="ml-3 text-sm text-gray-700">
                      I have read and agree to the <a href="/privacy" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>.
                    </span>
                  </label>

                  <label class="flex items-start cursor-pointer">
                    <input
                      v-model="agreedToCancellation"
                      type="checkbox"
                      class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                    <span class="ml-3 text-sm text-gray-700">
                      I understand that cancellations are free up to 24 hours before pickup.
                    </span>
                  </label>
                </div>
              </div>

              <!-- Navigation Buttons -->
              <div class="mt-8 flex justify-between">
                <button
                  v-if="currentStep > 1"
                  @click="previousStep"
                  class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
                >
                  Previous
                </button>
                <div v-else></div>

                <button
                  v-if="currentStep < totalSteps"
                  @click="nextStep"
                  :disabled="!canProceed"
                  class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed"
                >
                  Next
                </button>

                <button
                  v-if="currentStep === totalSteps"
                  @click="submitBooking"
                  :disabled="!canProceed || bookingForm.processing"
                  class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold"
                >
                  <span v-if="bookingForm.processing">Processing...</span>
                  <span v-else>Confirm Booking - Pay on Arrival</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="lg:col-span-1">
            <!-- Booking Summary -->
            <div class="bg-white rounded-lg shadow-lg p-6 sticky top-6">
              <h2 class="text-xl font-bold text-gray-900 mb-4">Booking Summary</h2>

              <!-- Vehicle Info -->
              <div class="mb-4">
                <div class="flex items-center mb-2">
                  <img
                    :src="vehicle.picture_url || '/images/default-car.jpg'"
                    :alt="vehicle.veh_make_model?.['@name'] || 'Vehicle'"
                    class="w-20 h-20 object-cover rounded mr-3"
                  />
                  <div>
                    <p class="font-semibold">{{ vehicle.veh_make_model?.['@name'] || 'N/A' }}</p>
                    <p class="text-sm text-gray-600">{{ vehicle.sipp_code }}</p>
                  </div>
                </div>
              </div>

              <!-- Rental Details -->
              <div class="border-t pt-4 mb-4">
                <h3 class="font-semibold text-gray-900 mb-2">Rental Details</h3>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span>Pick-up:</span>
                    <span class="font-medium">{{ bookingData.pickup_location_code }}</span>
                  </div>
                  <div class="text-xs text-gray-500">{{ formatDateTime(bookingData.pickup_date, bookingData.pickup_time) }}</div>
                  <div class="flex justify-between pt-2">
                    <span>Drop-off:</span>
                    <span class="font-medium">{{ bookingData.dropoff_location_code }}</span>
                  </div>
                  <div class="text-xs text-gray-500">{{ formatDateTime(bookingData.return_date, bookingData.return_time) }}</div>
                  <div class="flex justify-between pt-2">
                    <span>Duration:</span>
                    <span class="font-medium">{{ rentalDays }} days</span>
                  </div>
                </div>
              </div>

              <!-- Price Breakdown -->
              <div class="border-t pt-4">
                <h3 class="font-semibold text-gray-900 mb-2">Price Breakdown</h3>
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span>Vehicle Rental</span>
                    <span>{{ formatPrice(bookingData.total_amount - extrasTotal, bookingData.currency) }}</span>
                  </div>
                  <div v-if="extrasTotal > 0" class="flex justify-between">
                    <span>Extras</span>
                    <span>{{ formatPrice(extrasTotal, bookingData.currency) }}</span>
                  </div>
                  <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between">
                      <span class="font-semibold">Total Amount</span>
                      <span class="font-bold text-lg text-blue-600">
                        {{ formatPrice(bookingData.total_amount, bookingData.currency) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Pay on Arrival Notice -->
              <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-800 font-medium">Pay on Arrival</p>
                <p class="text-xs text-green-700">No payment required now</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>