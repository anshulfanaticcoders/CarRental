<script setup>
import { ref, onMounted, nextTick } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3"; // Add router import for redirect
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import infoIcon from "../../../assets/WarningCircle.svg";
import MapPin from "../../../assets/MapPin.svg";
import Footer from "@/Components/Footer.vue";

// Access props from Inertia
const props = usePage().props;
const booking = ref(props.booking);
const payment = ref(props.payment);
const vehicle = ref(props.vehicle);
const customer = ref(props.customer);
const vendorProfile = ref(props.vendorProfile || { currency: 'EUR' });
const plan = ref(props.plan);
const sessionId = ref(props.session_id);
const paymentIntentId = ref(props.payment_intent_id);
const paymentStatus = ref(props.payment_status);
const error = ref(null);
const map = ref(null);
const showLoader = ref(false); // New ref to control loader visibility

// Map initialization function
const initMap = () => {
  if (!vehicle.value?.latitude || !vehicle.value?.longitude) {
    console.warn('No vehicle location coordinates available');
    error.value = 'Vehicle location coordinates are unavailable.';
    return;
  }

  if (map.value) {
    map.value.remove();
  }

  map.value = L.map('booking-map', {
    zoomControl: true,
    maxZoom: 18,
    minZoom: 4,
  }).setView([vehicle.value.latitude, vehicle.value.longitude], 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value);

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

  L.marker([vehicle.value.latitude, vehicle.value.longitude], {
    icon: customIcon
  })
    .bindPopup(`
      <div class="text-center">
        <p class="font-semibold">Vehicle Location</p>
        <p>${vehicle.value.location || 'No location name available'}</p>
      </div>
    `)
    .addTo(map.value);

  setTimeout(() => {
    map.value.invalidateSize();
  }, 100);
};

// Initialize on mount
onMounted(() => {
  // Clear session storage
  if (window.sessionStorage) {
    window.sessionStorage.clear();
    console.log('Session storage cleared on mount');
  }

  // Validate required data
  if (!booking.value || !vehicle.value || !customer.value) {
    error.value = 'Booking, vehicle, or customer details are missing.';
    return;
  }

  // Initialize map
  nextTick(() => {
    initMap();
  });

  // Log props for debugging
  console.log('Inertia props:', usePage().props);

  // Trigger redirect after 5 seconds with loader
  showLoader.value = true;
  setTimeout(() => {
    router.visit('/profile/bookings/pending', {
      preserveState: false,
      preserveScroll: true,
    });
  }, 5000);
});

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: vendorProfile.value.currency || 'EUR'
  }).format(amount);
};

// Format date
const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

// Format time
const formatTime = (timeStr) => {
  if (!timeStr) return 'N/A';
  const [hours, minutes] = timeStr.split(':');
  const period = hours >= 12 ? 'PM' : 'AM';
  const formattedHours = hours % 12 || 12;
  return `${formattedHours}:${minutes} ${period}`;
};
</script>

<template>
  <AuthenticatedHeaderLayout />
  <div class="full-w-container py-customVerticalSpacing max-[768px]:pt-5">
    <!-- Loader Overlay -->
    <div v-if="showLoader" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 flex flex-col items-center gap-4 shadow-lg">
        <div class="loader border-t-4 border-customPrimaryColor rounded-full w-8 h-8 animate-spin"></div>
        <p class="text-customPrimaryColor text-lg font-medium max-[768px]:text-base">
          Redirecting to the bookings...
        </p>
      </div>
    </div>

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
                <p class="font-semibold">{{ booking.pickup_location || 'N/A' }}</p>
                <p>{{ formatDate(booking.pickup_date) }} at {{ formatTime(booking.pickup_time) }}</p>
              </div>
            </div>
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Return</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.return_location || 'N/A' }}</p>
                <p>{{ formatDate(booking.return_date) }} at {{ formatTime(booking.return_time) }}</p>
              </div>
            </div>
          </div>

          <span class="text-[1.5rem] font-medium">Pickup Location</span>
          <div id="booking-map" class="h-64 w-full rounded-lg mt-4 border"></div>

          <!-- Booking Reference and Financials -->
          <div class="border-t pt-6">
            <table class="w-full">
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Booking Reference</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ booking.booking_number || 'N/A' }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Total Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ formatCurrency(booking.total_amount) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2 text-green-600">{{ formatCurrency(booking.amount_paid) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Pending Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2 text-yellow-600">{{ formatCurrency(booking.pending_amount) }}</td>
              </tr>
            </table>
          </div>

          <!-- Payment Details -->
          <div v-if="payment" class="bg-white rounded-lg shadow-sm">
            <table class="w-full">
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Payment Status</td>
                <td class="text-customPrimaryColor font-medium capitalize text-right py-2">{{ payment.payment_status || 'N/A' }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Transaction ID</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ payment.transaction_id || 'N/A' }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ formatCurrency(payment.amount) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Payment Method</td>
                <td class="text-customPrimaryColor font-medium capitalize text-right py-2">{{ payment.payment_method || 'N/A' }}</td>
              </tr>
            </table>
          </div>
          <div v-else class="mt-6 p-4 bg-yellow-100 border border-yellow-400 rounded-lg">
            <p class="text-yellow-700">Payment details are not available.</p>
          </div>
        </div>

        <div class="flex justify-between">
          <Link class="button-primary px-5 py-4 max-[768px]:text-[0.75rem]" href="/messages">Chat with owner</Link>
          <Link class="button-secondary px-5 py-4 max-[768px]:text-[0.75rem]" href="/profile/bookings/pending">Go to Bookings</Link>
        </div>
      </div>

      <!-- Sidebar Column -->
      <div class="w-1/3 max-[768px]:w-full">
        <div v-if="vehicle" class="rounded-[12px] sticky top-[2rem] bg-customPrimaryColor text-customPrimaryColor-foreground">
          <div class="flex flex-col justify-between gap-3 p-5">
            <img v-if="vehicle?.images"
              :src="`${vehicle.images.find((image) => image.image_type === 'primary')?.image_url}`"
              alt="Primary Image"
              class="w-full h-[250px] object-cover rounded-lg"
              v-bind="vehicle.images.length ? {} : { src: '/images/placeholder-vehicle.jpg' }" />
            <div class="flex gap-5 items-center">
              <h4 class="max-[768px]:text-[1.2rem]">{{ vehicle.brand || 'N/A' }}</h4>
              <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] text-customPrimaryColor max-[768px]:text-[0.875rem]">{{ vehicle.category?.name || 'N/A' }}</span>
            </div>
          </div>
          <div class="car_short_info mt-[1rem] flex gap-3 max-[768px]:mt-0">
            <div class="features px-5">
              <span class="text-[1.15rem] capitalize max-[768px]:text-[0.875rem]">{{ vehicle.transmission || 'N/A' }} . {{ vehicle.fuel || 'N/A' }} . {{ vehicle.seating_capacity || 'N/A' }} Seats</span>
            </div>
          </div>

          <div class="pricing py-5 mt-[1rem] px-5">
            <div class="column Titlflex flex-col justify-between gap-3">
              <span class="text-[1.5rem] max-[768px]:text-[1.2rem]">Payment Details</span>
              <div class="flex justify-between items-center text-[1.15rem]">
                <span class="capitalize">{{ booking.preferred_day || 'day' }} Price</span>
                <div>
                  <strong class="text-[1.5rem] font-medium max-[768px]:text-[0.875rem]" v-if="booking.preferred_day === 'day'">{{ formatCurrency(vehicle.price_per_day || 0) }}</strong>
                  <strong class="text-[1.5rem] font-medium max-[768px]:text-[0.875rem]" v-if="booking.preferred_day === 'week'">{{ formatCurrency(vehicle.price_per_week || 0) }}</strong>
                  <strong class="text-[1.5rem] font-medium max-[768px]:text-[0.875rem]" v-if="booking.preferred_day === 'month'">{{ formatCurrency(vehicle.price_per_month || 0) }}</strong>
                </div>
              </div>
              <div v-if="booking.discount_amount" class="flex justify-between items-center text-[1.1rem] mt-[0rem] border-b-[1px] pb-[0.5rem] max-[768px]:mt-0">
                <span class="capitalize">Discount Price</span>
                <strong v-if="booking.discount_amount > 1" class="text-[1.1rem] font-medium text-green-400">- {{ formatCurrency(booking.discount_amount) }}</strong>
                <strong v-else class="text-[1.1rem] font-medium text-red-500">- {{ formatCurrency(booking.discount_amount) }}</strong>
              </div>

              <div class="flex justify-between">
                <span>
                  Vehicle Price for
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
                <strong class="text-[1.1rem] font-medium">{{ formatCurrency(booking.base_price || 0) }}</strong>
              </div>
              <span class="text-[1.5rem]">Extras</span>
              <div class="">
                <ul class="list-none pl-0">
                  <li v-for="extra in booking.extras" :key="extra.id" class="flex justify-between">
                    <span>{{ extra.extra_name }}({{ extra.quantity }}) {{ formatCurrency(extra.price) }} <strong class="text-green-400">x {{ booking.total_days }}</strong></span>
                    <span class="text-[1.1rem] font-medium">+ {{ formatCurrency(extra.price * extra.quantity * booking.total_days) }}</span>
                  </li>
                </ul>
              </div>

              <div>
                <div v-if="plan && plan.price > 0" class="flex justify-between gap-2">
                  <span>{{ plan.plan_type || 'N/A' }} {{ formatCurrency(plan.price) }} <strong class="text-green-400">x {{ booking.total_days }}</strong> </span>
                  <span class="text-[1.1rem] font-medium">+ {{ formatCurrency(plan.price * booking.total_days) }}</span>
                </div>
                <div v-else>
                  <span>{{ plan?.plan_type || 'N/A' }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-if="booking" class="bg-white text-customPrimaryColor p-5 rounded-[12px] border-[1px] border-[#153B4F]">
            <div class="flex justify-between items-center">
              <p class="flex items-center gap-2">
                <span class="text-[1.25rem] font-medium max-[768px]:text-[0.875rem]">Paid Payment (incl. VAT)</span>
                <img :src="infoIcon" alt="" class="w-[25px] h-[25px] max-[768px]:w-[20px] max-[768px]:h-[20px]" />
              </p>
              <span class="relative text-customPrimaryColor text-[1.5rem] font-medium max-[768px]:text-[1.2rem]">{{ formatCurrency(booking.total_amount) }}
                <span class="absolute left-0 top-[50%] w-full bg-red-600 h-[3px] -rotate-6"></span>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[1.25rem] font-medium max-[768px]:text-[0.875rem]">
                After paying 30% amount
              </span>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium text-green-600">{{ formatCurrency(booking.amount_paid) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <Footer />
  <!-- Inject clear session script -->
  <div v-html="props.clearSessionScript"></div>
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

/* Custom styles to match the first code */
.full-w-container {
  max-width: 1200px;
  margin: 0 auto;
}

.py-customVerticalSpacing {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

@media screen and (max-width: 768px) {
  .py-customVerticalSpacing {
    padding-top: 1.25rem;
    padding-bottom: 3rem;
  }
}

.button-primary {
  background-color: #1e40af;
  color: white;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: background-color 0.3s;
}

.button-primary:hover {
  background-color: #1e3a8a;
}

.button-secondary {
  background-color: #e5e7eb;
  color: #1e40af;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: background-color 0.3s;
}

.button-secondary:hover {
  background-color: #d1d5db;
}

/* Custom color variables to match the first code */
.text-customDarkBlackColor {
  color: #1A202C;
}

.text-customPrimaryColor {
  color: #153B4F;
}

.bg-customPrimaryColor {
  background-color: #153B4F;
}

.text-customPrimaryColor-foreground {
  color: #FFFFFF;
}

/* Loader styles */
.loader {
  border: 4px solid #f3f3f3;
  border-top-color: #153B4F; /* Matches text-customPrimaryColor */
  border-radius: 50%;
  width: 32px;
  height: 32px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>