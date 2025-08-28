<script setup>
import { ref, onMounted, nextTick } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Footer from "@/Components/Footer.vue";
import currencyData from '../../../../public/currency.json';

// Access props from Inertia
const props = usePage().props;
const greenMotionBooking = ref(props.booking); // This is the GreenMotionBooking model
const vehicle = ref(props.vehicle);
const customer = ref(props.customer); // Customer details are now passed separately
const paymentStatus = ref(props.payment_status);
const error = ref(null);
const map = ref(null);
const showLoader = ref(false);

// Derived amounts for display
const totalAmount = ref(Number(greenMotionBooking.value?.grand_total) || 0);
const amountPaid = ref(paymentStatus.value === 'paid' ? totalAmount.value : 0);
const pendingAmount = ref(paymentStatus.value === 'paid' ? 0 : totalAmount.value);

// Helper function to get ISO currency code from symbol
const getIsoCurrencyCode = (symbol) => {
  const currency = currencyData.find(c => c.symbol === symbol);
  return currency ? currency.code : 'EUR'; // Default to EUR if not found
};

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
        <img src="" alt="Vehicle Location" />
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

  // Tapfiliate Conversion Tracking
  if (window.tap) {
      tap('conversion',
          greenMotionBooking.value.greenmotion_booking_ref, // Unique Conversion Id
          amountPaid.value, // Conversion Amount
          {
              customer_id: customer.value?.id, // Customer Id (if available)
              customer_email: customer.value?.email, // Optional: Customer Email as metadata
              currency: getIsoCurrencyCode(greenMotionBooking.value?.currency) // Use ISO currency code
          }
      );
      console.log('Tapfiliate conversion tracked for booking:', greenMotionBooking.value.greenmotion_booking_ref);
  } else {
      console.warn('Tapfiliate object (tap) not found. Conversion not tracked.');
  }

  // Validate required data
  if (!greenMotionBooking.value || !vehicle.value || !customer.value) {
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
    const locale = usePage().props.locale || 'en';
    router.visit(`/${locale}/profile/bookings/pending`, {
      preserveState: false,
      preserveScroll: true,
    });
  }, 5000);
});

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: greenMotionBooking.value.currency || 'EUR'
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
          <h1 class="text-3xl font-semibold max-[768px]:text-[1.2rem]">GreenMotion Booking Successful</h1>
          <div class="p-4 bg-[#0099001A] border-[#009900] rounded-lg border">
            <p class="text-[#009900] font-medium text-lg max-[768px]:text-[0.875rem]">
              Thank you for your GreenMotion booking! Your reservation is confirmed.
            </p>
          </div>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="mb-8 p-4 bg-red-100 border border-red-400 rounded-lg">
          <h2 class="text-red-700 font-semibold mb-2">Error</h2>
          <p class="text-red-600">{{ error }}</p>
        </div>

        <!-- Booking Details -->
        <div v-if="greenMotionBooking" class="bg-white rounded-lg shadow-sm p-6 mb-8 max-[768px]:p-0">
          <h2 class="text-2xl font-semibold mb-6">Your Trip</h2>

          <!-- Pickup & Return Information -->
          <div class="grid grid-cols-2 gap-8 mb-6">
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Pickup</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ greenMotionBooking.location_id || 'N/A' }}</p>
                <p>{{ formatDate(greenMotionBooking.start_date) }} at {{ formatTime(greenMotionBooking.start_time) }}</p>
              </div>
            </div>
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Return</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ greenMotionBooking.dropoff_location_id || greenMotionBooking.location_id || 'N/A' }}</p>
                <p>{{ formatDate(greenMotionBooking.end_date) }} at {{ formatTime(greenMotionBooking.end_time) }}</p>
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
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ greenMotionBooking.greenmotion_booking_ref || 'N/A' }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Total Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ formatCurrency(totalAmount) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2 text-green-600">{{ formatCurrency(amountPaid) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Pending Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2 text-yellow-600">{{ formatCurrency(pendingAmount) }}</td>
              </tr>
            </table>
          </div>

          <!-- Payment Details (simplified as payment object is not passed directly) -->
          <div class="bg-white rounded-lg shadow-sm">
            <table class="w-full">
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Payment Status</td>
                <td class="text-customPrimaryColor font-medium capitalize text-right py-2">{{ paymentStatus || 'N/A' }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Transaction ID</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ greenMotionBooking.payment_handler_ref || 'N/A' }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ formatCurrency(amountPaid) }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Payment Type</td>
                <td class="text-customPrimaryColor font-medium capitalize text-right py-2">{{ greenMotionBooking.payment_type || 'N/A' }}</td>
              </tr>
            </table>
          </div>
        </div>

        <div class="flex justify-between">
          <Link class="button-primary px-5 py-4 max-[768px]:text-[0.75rem]" :href="`/${usePage().props.locale}/messages`">Chat with owner</Link>
          <Link class="button-secondary px-5 py-4 max-[768px]:text-[0.75rem]" :href="`/${usePage().props.locale}/profile/bookings/pending`">Go to Bookings</Link>
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
                <span class="capitalize">Vehicle Total</span>
                <div>
                  <strong class="text-[1.5rem] font-medium max-[768px]:text-[0.875rem]">{{ formatCurrency(greenMotionBooking.vehicle_total || 0) }}</strong>
                </div>
              </div>
              <!-- Discount amount is not directly available in GreenMotionBooking, so removing for now -->
              <!-- <div v-if="booking.discount_amount" class="flex justify-between items-center text-[1.1rem] mt-[0rem] border-b-[1px] pb-[0.5rem] max-[768px]:mt-0">
                <span class="capitalize">Discount Price</span>
                <strong v-if="booking.discount_amount > 1" class="text-[1.1rem] font-medium text-green-400">- {{ formatCurrency(booking.discount_amount) }}</strong>
                <strong v-else class="text-[1.1rem] font-medium text-red-500">- {{ formatCurrency(booking.discount_amount) }}</strong>
              </div> -->

              <div class="flex justify-between">
                <span>
                  Booking Duration
                </span>
                <strong class="text-[1.1rem] font-medium">{{ greenMotionBooking.start_date }} to {{ greenMotionBooking.end_date }}</strong>
              </div>
              <span class="text-[1.5rem]">Extras</span>
              <div class="">
                <ul class="list-none pl-0">
                  <li v-for="extra in greenMotionBooking.selected_extras" :key="extra.id" class="flex justify-between">
                    <span>{{ extra.name || 'N/A' }} ({{ extra.option_qty }})</span>
                    <span class="text-[1.1rem] font-medium">+ {{ formatCurrency(extra.option_total) }}</span>
                  </li>
                </ul>
              </div>

              <!-- Plan details are not directly available in GreenMotionBooking, so removing for now -->
              <!-- <div>
                <div v-if="plan && plan.price > 0" class="flex justify-between gap-2">
                  <span>{{ plan.plan_type || 'N/A' }} {{ formatCurrency(plan.price) }} <strong class="text-green-400">x {{ booking.total_days }}</strong> </span>
                  <span class="text-[1.1rem] font-medium">+ {{ formatCurrency(plan.price * booking.total_days) }}</span>
                </div>
                <div v-else>
                  <span>{{ plan?.plan_type || 'N/A' }}</span>
                </div>
              </div> -->
            </div>
          </div>
          <div v-if="greenMotionBooking" class="bg-white text-customPrimaryColor p-5 rounded-[12px] border-[1px] border-[#153B4F]">
            <div class="flex justify-between items-center">
              <p class="flex items-center gap-2">
                <span class="text-[1.25rem] font-medium max-[768px]:text-[0.875rem]">Total Payment (incl. VAT)</span>
              </p>
              <span class="relative text-customPrimaryColor text-[1.5rem] font-medium max-[768px]:text-[1.2rem]">{{ formatCurrency(totalAmount) }}
                <span v-if="paymentStatus !== 'paid'" class="absolute left-0 top-[50%] w-full bg-red-600 h-[3px] -rotate-6"></span>
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-[1.25rem] font-medium max-[768px]:text-[0.875rem]">
                Amount Paid
              </span>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium text-green-600">{{ formatCurrency(amountPaid) }}</span>
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
