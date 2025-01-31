<script setup>
import { ref, onMounted, nextTick } from "vue";
import axios from "axios";
import { Link, usePage } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import infoIcon from "../../../assets/WarningCircle.svg";
import MapPin from "../../../assets/MapPin.svg";

// State management
const booking = ref(null);
const payment = ref(null);
const vehicle = ref(null);
const error = ref(null);
const map = ref(null);
const plan = ref(null);

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
    `,  // Directly set the image source
    iconSize: [30, 30],  // Adjust the size of the marker image
    iconAnchor: [15, 30],  // Adjust the anchor to center the marker
    popupAnchor: [0, -30]  // Adjust the popup position if needed
  });

  // Add vehicle location marker
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
      plan.value = response.data.plan;

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
        <!-- Success Message (unchanged) -->
        <div class="flex flex-col gap-5 mb-8">
          <h1 class="text-3xl font-semibold">Booking Successful</h1>
          <div class="p-4 bg-[#0099001A] border-[#009900] rounded-lg border">
            <p class="text-[#009900] font-medium text-lg">
              Thank you for your booking! Your reservation is confirmed.
            </p>
          </div>
        </div>

        <!-- Error Display (unchanged) -->
        <div v-if="error" class="mb-8 p-4 bg-red-100 border border-red-400 rounded-lg">
          <h2 class="text-red-700 font-semibold mb-2">Error</h2>
          <p class="text-red-600">{{ error }}</p>
        </div>

        <!-- Booking Details -->
        <div v-if="booking" class="bg-white rounded-lg shadow-sm p-6 mb-8">
          <h2 class="text-2xl font-semibold mb-6">Your Trip</h2>

          <!-- Pickup & Return Information (unchanged) -->
          <div class="grid grid-cols-2 gap-8 mb-6">
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Pickup</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.pickup_location }}</p>
                <p>{{ booking.pickup_date }}</p>
              </div>
            </div>
            <div class="space-y-3">
              <h3 class="font-medium text-lg">Return</h3>
              <div class="text-gray-700">
                <p class="font-semibold">{{ booking.return_location }}</p>
                <p>{{ booking.return_date }}</p>
              </div>
            </div>
          </div>

          <span class="text-[1.5rem] font-medium">Pickup Location</span>
          <div id="booking-map" class="h-64 w-full rounded-lg mt-4 border"></div>

          <!-- Booking Reference and Extras -->
          <div class="border-t pt-6">
            <table class="w-full">
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Booking Reference</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ booking.booking_number }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Total Amount</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ formatCurrency(booking.total_amount)
                  }}</td>
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
                <td class="text-customDarkBlackColor py-2">Transaction ID</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ payment.transaction_id }}</td>
              </tr>
              <tr class="border-b">
                <td class="text-customDarkBlackColor py-2">Amount Paid</td>
                <td class="text-customPrimaryColor font-medium text-right py-2">{{ formatCurrency(payment.amount) }}
                </td>
              </tr>
            </table>
          </div>
        </div>

        <div class="flex justify-between"> 
          <Link class="button-primary px-5 py-4" href="/inbox">Chat with owner</Link>
          <Link class="button-secondary px-5 py-4" href="/profile/bookings/pending">Go to Bookings</Link>
        </div>
      </div>

      <!-- Sidebar Column (unchanged) -->
      <div class="w-1/3">
        <div v-if="vehicle"
          class="rounded-[12px] sticky top-[2rem] bg-customPrimaryColor text-customPrimaryColor-foreground">
          <div class="flex flex-col justify-between gap-3 p-5">
            <img v-if="vehicle?.images"
              :src="`/storage/${vehicle.images.find((image) => image.image_type === 'primary')?.image_path}`"
              alt="Primary Image" class="w-full h-[200px] object-cover rounded-lg" />
            <div class="flex gap-5 items-center">
              <h4>{{ vehicle?.brand }}</h4>
              <span
                class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px] text-customPrimaryColor">SUV</span>
            </div>
          </div>
          <div class="car_short_info mt-[1rem] flex gap-3">
            <div class="features px-5">
              <span class="text-[1.15rem] capitalize">{{ vehicle?.transmission }} . {{ vehicle?.fuel }} . {{
                vehicle?.seating_capacity }} Seats</span>
            </div>
          </div>

          <div class="pricing py-5 mt-[1rem] px-5">
            <div class="column flex flex-col justify-between gap-4">
              <span class="text-[1.5rem]">Payment Details</span>
              <div class="flex justify-between items-center text-[1.15rem]">
                <span>Price Per Day</span>
                <div>
                  <strong class="text-[1.5rem] font-medium">€{{ vehicle?.price_per_day }}</strong>
                  <span>/day</span>
                </div>
              </div>
              <div class="">
                <ul class="list-none pl-0">
                  <li v-for="extra in booking.extras" :key="extra.id" class="flex justify-between mb-2">
                    <span>{{ extra.extra_name }} x ({{ extra.quantity }})</span>
                    <span>{{ formatCurrency(extra.price * extra.quantity) }}</span>
                  </li>
                </ul>
              </div>
              <div class="flex justify-between">
                <span>{{plan.plan_type }}</span>
                <span>{{formatCurrency (plan.plan_value )}}</span>
              </div>
            </div>
          </div>
          <div v-if="booking"
            class="bg-white flex justify-between text-customPrimaryColor p-5 rounded-[12px] border-[1px] border-[#153B4F]">
            <div class="flex items-center gap-2">
              <span class="text-[1.25rem] font-medium">Paid Payment (incl. VAT)</span>
              <img :src="infoIcon" alt="" class="w-[25px] h[25px]" />
            </div>
            <span class="text-customPrimaryColor text-[1.875rem] font-medium">{{ formatCurrency(booking.total_amount)
              }}</span>

          </div>
        </div>
        <div>

          <div></div>
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
  min-height: 500px;
  width: 100%;
  z-index: 1;
}
</style>