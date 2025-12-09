<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  booking: Object
})

// Computed properties
const vehicle = computed(() => props.booking.vehicle_details)
const rentalDays = computed(() => {
  const pickup = new Date(props.booking.pickup_date)
  const returnDate = new Date(props.booking.return_date)
  const diffTime = Math.abs(returnDate.getTime() - pickup.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const extrasTotal = computed(() => {
  if (!props.booking.selected_extras) return 0
  return props.booking.selected_extras.reduce((total, extra) => total + extra.total, 0)
})

// Methods
function formatPrice(amount, currency) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

function formatDateTime(date, time) {
  return new Date(date + ' ' + time).toLocaleString()
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

function getTransmissionType(type) {
  const types = {
    'Automatic': 'Automatic',
    'Manual': 'Manual'
  }
  return types[type] || type
}

function printBooking() {
  window.print()
}

function addToCalendar() {
  // Create calendar event
  const event = {
    title: `Car Rental - ${vehicle.value.veh_make_model?.['@name'] || 'Locauto Rent'}`,
    start: `${props.booking.pickup_date}T${props.booking.pickup_time}`,
    end: `${props.booking.return_date}T${props.booking.return_time}`,
    description: `Booking Confirmation: ${props.booking.confirmation_number}\nPick-up: ${props.booking.pickup_location_code}\nDrop-off: ${props.booking.dropoff_location_code}`
  }

  // Create Google Calendar URL
  const googleCalendarUrl = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(event.title)}&dates=${event.start.replace(/[-:]/g, '')}/${event.end.replace(/[-:]/g, '')}&details=${encodeURIComponent(event.description)}`

  window.open(googleCalendarUrl, '_blank')
}
</script>

<template>
  <Head :title="`Booking Confirmed - ${booking.confirmation_number}`" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Success Message -->
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-lg font-medium text-green-800">Booking Confirmed!</h3>
              <p class="mt-1 text-sm text-green-700">
                Your car rental has been successfully booked. Confirmation details have been sent to your email.
              </p>
            </div>
          </div>
        </div>

        <!-- Confirmation Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
          <!-- Header -->
          <div class="bg-blue-600 text-white px-6 py-4">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-2xl font-bold">Booking Confirmation</h1>
                <p class="text-blue-100">Confirmation #: {{ booking.confirmation_number }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm text-blue-100">Status</p>
                <p class="font-semibold">{{ booking.status }}</p>
              </div>
            </div>
          </div>

          <!-- Booking Details -->
          <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <!-- Vehicle Info -->
              <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Vehicle Details</h2>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex items-center mb-3">
                    <img
                      :src="vehicle.picture_url || '/images/default-car.jpg'"
                      :alt="vehicle.veh_make_model?.['@name'] || 'Vehicle'"
                      class="w-24 h-24 object-cover rounded-lg mr-4"
                    />
                    <div>
                      <p class="font-semibold text-lg">{{ vehicle.veh_make_model?.['@name'] || 'N/A' }}</p>
                      <p class="text-sm text-gray-600">SIPP Code: {{ vehicle.sipp_code }}</p>
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="flex items-center">
                      <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      <span>{{ vehicle.passenger_quantity || 'N/A' }} Passengers</span>
                    </div>
                    <div class="flex items-center">
                      <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                      </svg>
                      <span>{{ getTransmissionType(vehicle.transmission_type || 'N/A') }}</span>
                    </div>
                    <div class="flex items-center">
                      <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                      </svg>
                      <span>{{ vehicle.air_condition_ind ? 'Air Conditioning' : 'No AC' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Rental Dates & Locations -->
              <div>
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Rental Details</h2>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="mb-4">
                    <p class="text-sm font-medium text-gray-600 mb-1">Pick-up</p>
                    <p class="font-semibold">{{ booking.pickup_location_code }}</p>
                    <p class="text-sm text-gray-600">{{ formatDateTime(booking.pickup_date, booking.pickup_time) }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ formatDate(booking.pickup_date) }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Drop-off</p>
                    <p class="font-semibold">{{ booking.dropoff_location_code }}</p>
                    <p class="text-sm text-gray-600">{{ formatDateTime(booking.return_date, booking.return_time) }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ formatDate(booking.return_date) }}</p>
                  </div>
                  <div class="mt-3 pt-3 border-t">
                    <p class="text-sm font-medium text-gray-600">Rental Duration</p>
                    <p class="font-semibold">{{ rentalDays }} days</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Customer Information -->
            <div class="mb-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-3">Customer Information</h2>
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <p class="text-sm text-gray-600">Name</p>
                    <p class="font-medium">{{ booking.customer_details.first_name }} {{ booking.customer_details.last_name }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Email</p>
                    <p class="font-medium">{{ booking.customer_details.email }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Phone</p>
                    <p class="font-medium">{{ booking.customer_details.phone }}</p>
                  </div>
                  <div>
                    <p class="text-sm text-gray-600">Payment Type</p>
                    <p class="font-medium">{{ booking.payment_type === 'POA' ? 'Pay on Arrival' : booking.payment_type }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Price Summary -->
            <div class="mb-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-3">Price Summary</h2>
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span>Vehicle Rental ({{ rentalDays }} days)</span>
                    <span>{{ formatPrice(booking.total_amount - extrasTotal, booking.currency) }}</span>
                  </div>
                  <div v-if="extrasTotal > 0" class="flex justify-between text-sm">
                    <span>Extras</span>
                    <span>{{ formatPrice(extrasTotal, booking.currency) }}</span>
                  </div>
                  <div class="border-t pt-2 mt-2">
                    <div class="flex justify-between">
                      <span class="font-semibold text-lg">Total Amount</span>
                      <span class="font-bold text-xl text-blue-600">
                        {{ formatPrice(booking.total_amount, booking.currency) }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Selected Extras -->
            <div v-if="booking.selected_extras && booking.selected_extras.length > 0" class="mb-6">
              <h2 class="text-lg font-semibold text-gray-900 mb-3">Selected Extras</h2>
              <div class="bg-gray-50 rounded-lg p-4">
                <div v-for="extra in booking.selected_extras" :key="extra.name" class="flex justify-between py-1">
                  <span class="text-sm">{{ extra.name }} ({{ extra.quantity }} days)</span>
                  <span class="text-sm font-medium">{{ formatPrice(extra.total, booking.currency) }}</span>
                </div>
              </div>
            </div>

            <!-- Important Information -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
              <h3 class="font-semibold text-yellow-800 mb-2">Important Information</h3>
              <ul class="text-sm text-yellow-700 space-y-1">
                <li>• Please arrive at the rental counter 15 minutes before your scheduled pick-up time</li>
                <li>• Present your valid driver's license, credit card, and booking confirmation</li>
                <li>• A security deposit will be held on your credit card at pickup</li>
                <li>• Free cancellation up to 24 hours before pickup time</li>
                <li>• Payment will be collected at the rental counter (Pay on Arrival)</li>
              </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
              <button
                @click="printBooking"
                class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Booking
              </button>

              <button
                @click="addToCalendar"
                class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Add to Calendar
              </button>

              <Link
                href="/"
                class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Back to Home
              </Link>
            </div>
          </div>
        </div>

        <!-- Contact Support -->
        <div class="bg-white rounded-lg shadow-lg p-6">
          <h2 class="text-lg font-semibold text-gray-900 mb-3">Need Help?</h2>
          <p class="text-gray-600 mb-4">
            If you have any questions or need to modify your booking, please contact our support team.
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <p class="text-sm font-medium text-gray-700">Email Support</p>
              <p class="text-sm text-gray-600">support@vrooem.com</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-700">Phone Support</p>
              <p class="text-sm text-gray-600">+1 (555) 123-4567</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style media="print">
@media print {
  .no-print {
    display: none !important;
  }

  body {
    -webkit-print-color-adjust: exact;
  }
}
</style>