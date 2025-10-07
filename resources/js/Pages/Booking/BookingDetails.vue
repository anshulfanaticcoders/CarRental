<script setup>
import { ref, onMounted, nextTick } from "vue";
import axios from "axios";
import { Link, usePage } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import infoIcon from "../../../assets/WarningCircle.svg";
import MapPin from "../../../assets/MapPin.svg";
import Footer from "@/Components/Footer.vue";

// State management
const booking = ref(null);
const payment = ref(null);
const vehicle = ref(null);
const error = ref(null);
const map = ref(null);
const plan = ref(null);
const vendorProfile = ref(null);

// Map initialization function
const initMap = () => {
  if (!vehicle.value?.latitude || !vehicle.value?.longitude) {
    console.warn('No vehicle location coordinates available');
    return;
  }

  // Cleanup existing map if it exists
  if (map.value) {
    map.value.remove();
  }

  // Initialize map with vehicle's latitude and longitude
  map.value = L.map('booking-map', {
    zoomControl: true,
    maxZoom: 18,
    minZoom: 4,
  }).setView([vehicle.value.latitude, vehicle.value.longitude], 15);

  // Add tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value);

  // Create custom marker icon for vehicle
  const customIcon = L.divIcon({
    className: 'custom-div-icon',
    html: `
      <div class="marker-pin">
        <img src="${MapPin}" alt="Vehicle Location" />
      </div>
    `,
    iconSize: [30, 30],
    iconAnchor: [15, 30],
    popupAnchor: [0, -30]
  });

  // Add vehicle location marker
  L.marker([vehicle.value.latitude, vehicle.value.longitude], {
    icon: customIcon
  })
    .bindPopup(`
      <div class="text-center">
        <p class="font-semibold">Vehicle Location</p>
        <p>${vehicle.value.full_vehicle_address || 'No location name available'}</p>
      </div>
    `)
    .addTo(map.value);

  // Force a map refresh
  setTimeout(() => {
    map.value.invalidateSize();
  }, 100);
};

// Fetch booking details
onMounted(async () => {
  // Clear session storage as soon as component is mounted
  if (window.sessionStorage) {
    window.sessionStorage.clear();
    console.log('Session storage cleared on mount');
  }

  // Get query parameters from the URL
  const paymentIntentId = usePage().props.payment_intent;
  const sessionId = usePage().props.session_id;
  
  // Use either payment_intent or session_id
  const queryParam = paymentIntentId 
    ? `payment_intent=${paymentIntentId}` 
    : sessionId 
      ? `session_id=${sessionId}` 
      : null;

  if (queryParam) {
    try {
      const response = await axios.get(`/api/booking-success/details?${queryParam}`);

      booking.value = response.data.booking;
      payment.value = response.data.payment;
      vehicle.value = response.data.vehicle;
      plan.value = response.data.plan;
      vendorProfile.value = response.data.vendorProfile;

      // Initialize map after data is loaded
      nextTick(() => {
        initMap();
      });
    } catch (err) {
      error.value = "There was an error fetching the booking details. Please try again later.";
      console.error("Error fetching booking details:", err);
    }
  } else {
    error.value = "Payment identification is missing from the URL.";
  }
});

// Get currency symbol
const getCurrencySymbol = (currency) => {
  const symbols = {
    'USD': '$',
    'EUR': '€',
    'GBP': '£',
    'JPY': '¥',
    'AUD': 'A$',
    'CAD': 'C$',
    'CHF': 'Fr',
    'HKD': 'HK$',
    'SGD': 'S$',
    'SEK': 'kr',
    'KRW': '₩',
    'NOK': 'kr',
    'NZD': 'NZ$',
    'INR': '₹',
    'MXN': 'Mex$',
    'ZAR': 'R',
    'AED': 'AED'
  };
  return symbols[currency] || '$';
};

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
};

// Format number with proper decimal places
const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number);
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};
</script>

<template>
  <Head>
    <title>Booking Successful</title>
    <!-- Event snippet for Book appointment conversion page -->
  </Head>
  <AuthenticatedHeaderLayout />
  <div class="full-w-container py-customVerticalSpacing max-[768px]:pt-5">
    <div class="flex gap-8 max-[768px]:flex-col">
      <!-- Main Content Column -->
      <div class="w-2/3 max-[768px]:w-full">
        <!-- Success Message -->
        <div class="flex flex-col gap-5 mb-8">
          <h1 class="text-3xl font-semibold max-[768px]:text-[1.2rem]">Booking Successful</h1>
          <div class="p-4 bg-[#0099001A] border-[#009900] rounded-lg border">
            <p class="text-[#009900] font-medium text-lg max-[768px]:text-[0.875rem]">
              Thank you for your booking! Your reservation is confirmed.
            </p>
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="mb-8 p-4 bg-red-100 border border-red-400 rounded-lg">
          <h2 class="text-red-700 font-semibold mb-2">Error</h2>
          <p class="text-red-600">{{ error }}</p>
        </div>

        <!-- Booking Details -->
        <div v-if="booking" class="bg-white rounded-lg shadow-sm p-6 mb-8 max-[768px]:p-0">
          <h2 class="text-2xl font-semibold mb-6">Your Trip</h2>

          <!-- Pickup & Return Information -->
          <div class="grid grid-cols-2 gap-8 mb-6">
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Pickup</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.pickup_location }}</p>
                <p>{{ formatDate(booking.pickup_date) }}</p>
              </div>
            </div>
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Return</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.return_location }}</p>
                <p>{{ formatDate(booking.return_date) }}</p>
              </div>
            </div>
          </div>

          <span class="text-[1.5rem] font-medium">Pickup Location</span>
          <div id="booking-map" class="h-64 w-full rounded-lg mt-4 border"></div>

          <!-- Booking Reference and Extras -->
          <div class="border-t pt-6">
            <table class="w-full">
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Booking Number</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ booking.booking_number }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Booking Reference</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ booking.booking_reference }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Total Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.total_amount) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2 text-green-600">{{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.amount_paid) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Pending Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2 text-yellow-600">{{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.pending_amount) }}</td>
              </tr>
            </table>
          </div>

          <div v-if="payment" class="bg-white rounded-lg shadow-sm">
            <table class="w-full">
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Payment Status</td>
                <td class="text-customPrimaryColor font-medium capitalize text-right py-2">{{ payment.payment_status }}
                </td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Payment Method</td>
                <td class="text-customPrimaryColor font-medium capitalize text-right py-2">{{ payment.payment_method }}
                </td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Transaction ID</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ payment.transaction_id }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(payment.amount) }}</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="flex justify-between">
          <Link 
            class="button-primary px-5 py-4 max-[768px]:text-[0.75rem]" 
            :href="vendorProfile ? route('messages.index', { locale: usePage().props.locale, vendor_id: vendorProfile.user_id }) : route('messages.index', { locale: usePage().props.locale })"
          >
            Chat with owner
          </Link>
          <Link class="button-secondary px-5 py-4 max-[768px]:text-[0.75rem]" :href="route('profile.bookings.pending', { locale: usePage().props.locale })">Go to
          Bookings</Link>
        </div>
      </div>

      <!-- Sidebar Column -->
      <div class="w-1/3 max-[768px]:w-full">
        <div v-if="vehicle"
          class="rounded-[12px] sticky top-[2rem] bg-customPrimaryColor text-customPrimaryColor-foreground">
          <div class="flex flex-col justify-between gap-3 p-5">
            <img v-if="vehicle?.images"
              :src="`${vehicle.images.find((image) => image.image_type === 'primary')?.image_url}`" alt="Primary Image"
              class="w-full h-[250px] object-cover rounded-lg" />
            <div class="flex gap-5 items-center">
              <h4 class="max-[768px]:text-[1.2rem]">{{ vehicle?.brand }}</h4>
              <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] text-customPrimaryColor 
                max-[768px]:text-[0.875rem]">{{ vehicle?.category.name }}</span>
            </div>
          </div>
          <div class="car_short_info mt-[1rem] flex gap-3 max-[768px]:mt-0">
            <div class="features px-5">
              <span class="text-[1.15rem] capitalize max-[768px]:text-[0.875rem]">{{ vehicle?.transmission }} . {{
                vehicle?.fuel }} . {{
                  vehicle?.seating_capacity }} Seats</span>
            </div>
          </div>

          <div class="pricing py-5 mt-[1rem] px-5">
            <div class="column flex flex-col justify-between gap-3">
              <span class="text-[1.5rem] max-[768px]:text-[1.2rem]">Payment Details</span>
              <div class="flex justify-between items-center text-[1.15rem]">
                <span class="capitalize">Base Price (per {{ booking.preferred_day }})</span>
                <div>
                  <strong class="text-[1.5rem] font-medium max-[768px]:text-[0.875rem]">
                    {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.base_price) }}
                  </strong>
                </div>
              </div>

              <div class="flex justify-between items-center text-[1.15rem]">
                <span class="capitalize">Vehicle Subtotal ({{ booking.base_price }} × {{ booking.total_days }} {{ booking.preferred_day }}(s))</span>
                <div>
                  <strong class="text-[1.5rem] font-medium max-[768px]:text-[0.875rem]">
                    {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.base_price * booking.total_days) }}
                  </strong>
                </div>
              </div>
              <div v-if="booking.discount_amount"
                class="flex justify-between items-center text-[1.1rem] mt-[0rem] border-b-[1px] pb-[0.5rem] max-[768px]:mt-0">
                <span class="capitalize">Discount Price</span>
                <strong v-if="booking.discount_amount > 1" class="text-[1.1rem] font-medium text-green-400">- {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.discount_amount) }}</strong>
                <strong v-else class="text-[1.1rem] font-medium text-red-500">- {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.discount_amount) }}</strong>
              </div>

              <div class="flex justify-between">
                <span>
                  Rental Period
                  <template v-if="booking.preferred_day === 'week' && booking.total_days % 7 === 0">
                    {{ booking.total_days / 7 }} {{ booking.total_days / 7 === 1 ? 'week' : 'weeks' }}
                  </template>
                  <template v-else-if="booking.preferred_day === 'month' && booking.total_days % 30 === 0">
                    {{ booking.total_days / 30 }} {{ booking.total_days / 30 === 1 ? 'month' : 'months' }}
                  </template>
                  <template v-else>
                    {{ booking.total_days }} days
                  </template>
                </span>
                <strong class="text-[1.1rem] font-medium">{{ booking.total_days }} {{ booking.preferred_day }}(s)</strong>
              </div>
              <span class="text-[1.5rem]">Extras</span>
              <div class="">
                <ul class="list-none pl-0">
                  <li v-for="extra in booking.extras" :key="extra.id" class="flex justify-between">
                    <span>{{ extra.extra_name }}({{ extra.quantity }}) {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(extra.price) }}
                      <strong class="text-green-400">x {{ booking.total_days }}</strong></span>
                    <span class="text-[1.1rem] font-medium">+ {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(extra.price * booking.total_days) }}</span>
                  </li>
                </ul>
              </div>

              <div>
                <div v-if="booking.plan_price > 0" class="flex justify-between gap-2">
                  <span>{{ booking.plan }} ({{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.plan_price) }} per {{ booking.preferred_day }}) <strong
                      class="text-green-400">x {{
                      booking.total_days }}</strong> </span>
                  <span class="text-[1.1rem] font-medium">
                    + {{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.plan_price * booking.total_days) }}
                  </span>
                </div>
                <div v-else>
                  <span>{{ booking.plan }}</span>
                </div>
              </div>

            </div>
          </div>
          <div v-if="booking" class="bg-white text-customPrimaryColor p-5 rounded-[12px] border-[1px] border-[#153B4F]">
            <div class="flex justify-between items-center">
              <p class="flex items-center gap-2">
                <span class="text-[1.25rem] font-medium max-[768px]:text-[0.875rem]">Total Amount (incl. VAT)</span>
                <img :src="infoIcon" alt="" class="w-[25px] h[25px] max-[768px]:w-[20px] max-[768px]:h-[20px]" />
              </p>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium max-[768px]:text-[1.2rem]">{{
                getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.total_amount) }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[1.25rem] font-medium max-[768px]:text-[0.875rem]">
                Amount Paid
              </span>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium text-green-600">{{ getCurrencySymbol(booking.booking_currency) }}{{ formatNumber(booking.amount_paid) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <Footer />
</template>

<style scoped>
@import 'leaflet/dist/leaflet.css';

.marker-pin {
  width: auto;
  min-width: 100px;
  height: 30px;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.marker-pin span {
  color: black;
  font-weight: bold;
  font-size: 12px;
  padding: 0 8px;
}

.custom-div-icon {
  background: none;
  border: none;
}

/* Ensure proper map container dimensions */
#booking-map {
  min-height: 500px;
  width: 100%;
  z-index: 1;
}
tr{
  scale: 1;
  transition: all 0.1s ease;
  cursor: pointer;
}
tr:hover{
background-color: rgba(182, 182, 182, 0.256);
scale: 1.01;
}

@media screen and (max-width:768px) {
  #booking-map {
    min-height: 200px;
  }

  table tr>td:first-child {
    font-size: 0.875rem;
  }

  table tr>td:last-child {
    font-size: 0.875rem;
  }
}
</style>
