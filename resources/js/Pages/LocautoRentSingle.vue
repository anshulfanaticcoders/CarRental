<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { route } from 'ziggy-js'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
import { usePage } from '@inertiajs/vue3'

// Define interfaces
interface Vehicle {
  veh_avail: {
    vehicle: {
      veh_make_model: {
        '@code': string
        '@name': string
      }
      picture_url: string
      sipp_code: string
      air_condition_ind: boolean
      transmission_type: string
      fuel_type: string
      passenger_quantity: number
      baggage_quantity: number
      door_count: number
    }
    total_charge: {
      rate_total_amount: {
        '@currency_code': string
        '#text': string
      }
      estimated_total_amount: {
        '@currency_code': string
        '#text': string
      }
    }
    rate_qualifiers: {
      rate_qualifier: Array<{
        rate_category: string
        rate_period: string
      }>
    }
  }
}

interface Props {
  vehicle: Vehicle
  pickupLocation: string
  dropoffLocation: string
  pickupDate: string
  pickupTime: string
  returnDate: string
  returnTime: string
  driverAge: number
  sippDescription: string
}

const props = defineProps<Props>()

const page = usePage()
const baseUrl = page.props.appUrl || window.location.origin

// State
const selectedExtras = ref<Record<string, boolean>>({})
const loading = ref(false)

// Computed properties
const vehicle = computed(() => props.vehicle.veh_avail.vehicle)
const pricing = computed(() => props.vehicle.veh_avail.total_charge)

const sippInfo = computed(() => {
  const sipp = vehicle.value.sipp_code
  return {
    vehicleType: sipp[0],
    doors: sipp[1],
    transmission: sipp[2],
    fuelAircon: sipp[3],
    description: props.sippDescription || decodeSIPP(sipp)
  }
})

const extras = ref([
  { code: '9', name: 'Additional Driver', price: 9.00, unit: 'day', maxDays: null },
  { code: '19', name: 'GPS Navigation', price: 7.00, unit: 'day', maxDays: 18 },
  { code: '78', name: 'Child Seat', price: 11.25, unit: 'day', maxDays: 4 },
  { code: '136', name: 'Don\'t Worry Cover', price: 38.00, unit: 'day', maxDays: null },
  { code: '137', name: 'Additional Driver (Alternative)', price: 9.00, unit: 'day', maxDays: null },
  { code: '139', name: 'Young Driver Fee', price: 15.00, unit: 'day', maxDays: null },
])

const bookingForm = useForm({
  vehicle_code: vehicle.value.sipp_code,
  vehicle_details: vehicle.value,
  pickup_location_code: props.pickupLocation,
  dropoff_location_code: props.dropoffLocation,
  pickup_date: props.pickupDate,
  pickup_time: props.pickupTime,
  return_date: props.returnDate,
  return_time: props.returnTime,
  driver_age: props.driverAge,
  selected_extras: {},
  total_amount: parseFloat(pricing.value.rate_total_amount['#text']),
  currency: pricing.value.rate_total_amount['@currency_code'],
})

const rentalDays = computed(() => {
  const pickup = new Date(props.pickupDate)
  const returnDate = new Date(props.returnDate)
  const diffTime = Math.abs(returnDate.getTime() - pickup.getTime())
  return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
})

const extrasTotal = computed(() => {
  let total = 0
  Object.entries(selectedExtras.value).forEach(([code, selected]) => {
    if (selected) {
      const extra = extras.value.find(e => e.code === code)
      if (extra) {
        const days = extra.maxDays ? Math.min(rentalDays.value, extra.maxDays) : rentalDays.value
        total += extra.price * days
      }
    }
  })
  return total
})

const grandTotal = computed(() => {
  return bookingForm.total_amount + extrasTotal.value
})

// Methods
function decodeSIPP(sipp: string): string {
  const codes = {
    'M': 'Mini',
    'E': 'Economy',
    'C': 'Compact',
    'I': 'Intermediate',
    'S': 'Standard',
    'F': 'Fullsize',
    'P': 'Premium',
    'L': 'Luxury',
    'X': 'Special',
    'B': '2 doors',
    'C': '2/4 doors',
    'D': '4/5 doors',
    'W': 'Wagon/Estate',
    'V': 'Passenger Van',
    'L': 'Limousine',
    'S': 'Sport',
    'T': 'Convertible',
    'F': 'SUV',
    'X': 'Special',
    'J': 'Open air all terrain',
    'M': 'Manual',
    'N': 'Manual',
    'C': 'Automatic',
    'A': 'Automatic',
    'R': 'Manual stick shift',
    'B': 'Diesel',
    'D': 'Diesel',
    'F': 'Unleaded',
    'H': 'Hybrid',
    'I': 'Hybrid electric',
    'L': 'LPG/Gas',
    'E': 'Electric',
    'C': 'Air conditioning',
    'R': 'No air conditioning'
  }

  let description = ''
  if (codes[sipp[0]]) description += codes[sipp[0]] + ' - '
  if (codes[sipp[1]]) description += codes[sipp[1]] + ', '
  if (codes[sipp[2]]) description += codes[sipp[2]] + ', '
  if (codes[sipp[3]]) description += codes[sipp[3]]

  return description
}

function proceedToBooking() {
  loading.value = true

  // Prepare selected extras
  const selected = Object.entries(selectedExtras.value)
    .filter(([_, selected]) => selected)
    .map(([code, _]) => {
      const extra = extras.value.find(e => e.code === code)
      return {
        code: code,
        name: extra?.name,
        price: extra?.price,
        quantity: extra?.maxDays ? Math.min(rentalDays.value, extra.maxDays) : rentalDays.value,
        total: extra?.price ? extra.price * (extra?.maxDays ? Math.min(rentalDays.value, extra.maxDays) : rentalDays.value) : 0
      }
    })

  bookingForm.selected_extras = selected
  bookingForm.total_amount = grandTotal.value

  bookingForm.post(route('locauto.booking.form'), {
    onSuccess: () => {
      loading.value = false
    },
    onError: () => {
      loading.value = false
    }
  })
}

function formatPrice(amount: number, currency: string): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency
  }).format(amount)
}

function getTransmissionType(type: string): string {
  const types = {
    'Automatic': 'Automatic',
    'Manual': 'Manual'
  }
  return types[type] || type
}

function getFuelType(type: string): string {
  const types = {
    'Petrol': 'Petrol',
    'Diesel': 'Diesel',
    'Electric': 'Electric',
    'Hybrid': 'Hybrid'
  }
  return types[type] || type
}
</script>

<template>
  <Head title="Vehicle Details - Locauto Rent" />

  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-6">
          <ol class="flex items-center space-x-2 text-sm text-gray-600">
            <li>
              <Link href="/" class="hover:text-gray-900">Home</Link>
            </li>
            <li>/</li>
            <li>
              <Link href="/locauto/cars" class="hover:text-gray-900">Locauto Rent</Link>
            </li>
            <li>/</li>
            <li class="text-gray-900">Vehicle Details</li>
          </ol>
        </nav>

        <!-- Vehicle Header -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
          <div class="md:flex">
            <div class="md:w-1/3">
              <img
                :src="vehicle.picture_url || '/images/default-car.jpg'"
                :alt="vehicle.veh_make_model['@name']"
                class="w-full h-64 md:h-full object-cover"
              />
            </div>
            <div class="md:w-2/3 p-6">
              <div class="flex items-start justify-between mb-4">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ vehicle.veh_make_model['@name'] }}
                  </h1>
                  <p class="text-lg text-gray-600 mb-1">
                    {{ sippInfo.description }}
                  </p>
                  <p class="text-sm text-gray-500">
                    SIPP Code: {{ vehicle.sipp_code }}
                  </p>
                </div>
                <div class="text-right">
                  <div class="text-3xl font-bold text-blue-600">
                    {{ formatPrice(pricing.rate_total_amount['#text'], pricing.rate_total_amount['@currency_code']) }}
                  </div>
                  <div class="text-sm text-gray-500">per day</div>
                </div>
              </div>

              <!-- Vehicle Features -->
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <span class="text-sm">{{ vehicle.passenger_quantity }} Passengers</span>
                </div>
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                  </svg>
                  <span class="text-sm">{{ vehicle.baggage_quantity }} Bags</span>
                </div>
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                  </svg>
                  <span class="text-sm">{{ getTransmissionType(vehicle.transmission_type) }}</span>
                </div>
                <div class="flex items-center">
                  <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                  </svg>
                  <span class="text-sm">
                    {{ vehicle.air_condition_ind ? 'Air Conditioning' : 'No AC' }}
                  </span>
                </div>
              </div>

              <!-- Pay on Arrival Badge -->
              <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <div>
                    <p class="font-bold">Pay on Arrival</p>
                    <p class="text-sm">No prepayment required. Pay when you pick up the vehicle.</p>
                  </div>
                </div>
              </div>

              <!-- Rental Details -->
              <div class="border-t pt-4">
                <h3 class="font-semibold text-gray-900 mb-2">Rental Details</h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="text-gray-500">Pick-up:</span>
                    <p class="font-medium">{{ pickupLocation }}</p>
                    <p class="text-gray-600">{{ new Date(pickupDate + ' ' + pickupTime).toLocaleString() }}</p>
                  </div>
                  <div>
                    <span class="text-gray-500">Drop-off:</span>
                    <p class="font-medium">{{ dropoffLocation }}</p>
                    <p class="text-gray-600">{{ new Date(returnDate + ' ' + returnTime).toLocaleString() }}</p>
                  </div>
                </div>
                <div class="mt-3 text-sm">
                  <span class="text-gray-500">Rental Duration:</span>
                  <p class="font-medium">{{ rentalDays }} days</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Extras Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Optional Extras</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="extra in extras" :key="extra.code" class="flex items-center justify-between p-4 border rounded-lg hover:bg-gray-50">
              <label class="flex items-center cursor-pointer flex-1">
                <input
                  v-model="selectedExtras[extra.code]"
                  type="checkbox"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <div class="ml-3">
                  <p class="font-medium text-gray-900">{{ extra.name }}</p>
                  <p class="text-sm text-gray-500">
                    {{ formatPrice(extra.price, 'EUR') }} / day
                    <span v-if="extra.maxDays">(max {{ extra.maxDays }} days)</span>
                  </p>
                </div>
              </label>
              <div class="text-right">
                <p class="font-medium">
                  {{ formatPrice(
                    extra.price * (extra.maxDays ? Math.min(rentalDays, extra.maxDays) : rentalDays),
                    'EUR'
                  ) }}
                </p>
                <p class="text-xs text-gray-500">total</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Price Summary -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
          <h2 class="text-xl font-bold text-gray-900 mb-4">Price Summary</h2>
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span>Vehicle Rental ({{ rentalDays }} days)</span>
              <span>{{ formatPrice(bookingForm.total_amount - extrasTotal, bookingForm.currency) }}</span>
            </div>
            <div v-if="extrasTotal > 0" class="flex justify-between text-sm">
              <span>Extras</span>
              <span>{{ formatPrice(extrasTotal, bookingForm.currency) }}</span>
            </div>
            <div class="border-t pt-2 mt-2">
              <div class="flex justify-between">
                <span class="font-semibold text-lg">Total Amount</span>
                <span class="font-bold text-xl text-blue-600">
                  {{ formatPrice(grandTotal, bookingForm.currency) }}
                </span>
              </div>
            </div>
            <div class="bg-green-50 border border-green-200 rounded p-3 text-sm text-green-700">
              <p class="font-medium">Pay on Arrival</p>
              <p>No payment required now. Pay at the rental counter.</p>
            </div>
          </div>
        </div>

        <!-- Booking Button -->
        <div class="text-center">
          <button
            @click="proceedToBooking"
            :disabled="loading"
            class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold text-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            <span v-if="loading">Processing...</span>
            <span v-else>Book Now - Pay on Arrival</span>
          </button>
          <p class="mt-3 text-sm text-gray-500">
            No credit card required. Free cancellation up to 24 hours before pickup.
          </p>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>