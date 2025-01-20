<script setup>
import { ref, onMounted, nextTick } from "vue";
import axios from "axios";
import { usePage } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// State management
const booking = ref(null);
const payment = ref(null);
const vehicle = ref(null);
const error = ref(null);
const map = ref(null);

// Map initialization function
const initMap = () => {
  if (!booking.value?.pickup_latitude || !booking.value?.pickup_longitude) {
    console.warn('No pickup location coordinates available');
    return;
  }

  // Cleanup existing map if it exists
  if (map.value) {
    map.value.remove();
  }

  // Initialize map
  map.value = L.map('booking-map', {
    zoomControl: true,
    maxZoom: 18,
    minZoom: 4,
  }).setView([booking.value.pickup_latitude, booking.value.pickup_longitude], 15);

  // Add tile layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value);

  // Create custom marker icon
  const customIcon = L.divIcon({
    className: 'custom-div-icon',
    html: `
      <div class="marker-pin">
        <span>Pickup Point</span>
      </div>
    `,
    iconSize: [100, 30],
    iconAnchor: [50, 15],
    popupAnchor: [0, -15]
  });

  // Add marker
  L.marker([booking.value.pickup_latitude, booking.value.pickup_longitude], {
    icon: customIcon
  })
    .bindPopup(`
      <div class="text-center">
        <p class="font-semibold">Pickup Location</p>
        <p>${booking.value.pickup_location}</p>
        <p class="text-sm text-gray-600">${booking.value.pickup_date}</p>
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
  const paymentIntentId = usePage().props.payment_intent;
  
  if (paymentIntentId) {
    try {
      const response = await axios.get(
        `/api/booking-success/details?payment_intent=${paymentIntentId}`
      );
      
      booking.value = response.data.booking;
      payment.value = response.data.payment;
      vehicle.value = response.data.vehicle;

      // Initialize map after data is loaded
      nextTick(() => {
        initMap();
      });
    } catch (err) {
      error.value = "There was an error fetching the booking details. Please try again later.";
      console.error("Error fetching booking details:", err);
    }
  } else {
    error.value = "Payment Intent ID is missing from the URL.";
  }
});

// Format currency
const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
};
</script>

<template>
  <AuthenticatedHeaderLayout />
  <div class="full-w-container py-customVerticalSpacing">
    <div class="flex gap-8">
      <!-- Main Content Column -->
      <div class="w-2/3">
        <!-- Success Message -->
        <div class="flex flex-col gap-5 mb-8">
          <h1 class="text-3xl font-semibold">Booking Successful</h1>
          <div class="p-4 bg-[#0099001A] border-[#009900] rounded-lg border">
            <p class="text-[#009900] font-medium text-lg">
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
        <div v-if="booking" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 class="text-2xl font-semibold mb-6">Trip Details</h2>
          
          <!-- Pickup & Return Information -->
          <div class="grid grid-cols-2 gap-8 mb-6">
            <!-- Pickup Details -->
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Pickup</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.pickup_location }}</p>
                <p>{{ booking.pickup_date }}</p>
              </div>
              <!-- Map Container -->
              <div id="booking-map" class="h-64 w-full rounded-lg mt-4 border"></div>
            </div>

            <!-- Return Details -->
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Return</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.return_location }}</p>
                <p>{{ booking.return_date }}</p>
              </div>
            </div>
          </div>

          <!-- Booking Reference -->
          <div class="border-t pt-6">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-gray-600">Booking Reference</p>
                <p class="font-semibold text-lg">{{ booking.booking_number }}</p>
              </div>
              <div class="text-right">
                <p class="text-gray-600">Total Amount</p>
                <p class="font-semibold text-lg">{{ formatCurrency(booking.total_amount) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Details -->
        <div v-if="payment" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 class="text-2xl font-semibold mb-4">Payment Details</h2>
          <div class="space-y-3">
            <div class="flex justify-between">
              <span class="text-gray-600">Payment Status</span>
              <span class="font-medium capitalize">{{ payment.payment_status }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Transaction ID</span>
              <span class="font-medium">{{ payment.transaction_id }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Amount Paid</span>
              <span class="font-medium">{{ formatCurrency(payment.amount) }}</span>
            </div>
          </div>
        </div>

        <!-- Navigation Links -->
        <div class="flex gap-4">
          <Link href="/" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-customPrimaryColor hover:bg-opacity-90">
            Return to Home
          </Link>
          <Link href="/bookings" class="inline-flex items-center justify-center px-6 py-3 border border-customPrimaryColor rounded-md shadow-sm text-base font-medium text-customPrimaryColor hover:bg-gray-50">
            View My Bookings
          </Link>
        </div>
      </div>

      <!-- Sidebar Column -->
      <div class="w-1/3">
        <!-- Vehicle Summary -->
        <div v-if="vehicle" class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
          <h3 class="text-xl font-semibold mb-4">Vehicle Details</h3>
          <div class="space-y-4">
            <div class="flex items-center gap-4">
              <img :src="`/storage/${vehicle.primary_image}`" alt="Vehicle" class="w-24 h-24 object-cover rounded-lg" />
              <div>
                <h4 class="font-medium">{{ vehicle.brand }}</h4>
                <p class="text-gray-600">{{ vehicle.category?.name }}</p>
              </div>
            </div>
            <!-- Add more vehicle details as needed -->
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import 'leaflet/dist/leaflet.css';

.marker-pin {
  width: auto;
  min-width: 100px;
  height: 30px;
  background: white;
  border: 2px solid #666;
  border-radius: 15px;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
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
  min-height: 300px;
  width: 100%;
  z-index: 1;
}
</style>