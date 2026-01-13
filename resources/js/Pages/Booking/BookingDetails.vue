<script setup>
import { ref, onMounted, nextTick, computed } from "vue";
import { Link, usePage, Head } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import MapPin from "../../../assets/MapPin.svg";
import Footer from "@/Components/Footer.vue";
import { getCurrentInstance } from 'vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
  booking: Object,
  vehicle: Object,
  payment: Object,
  vendorProfile: Object,
  plan: Object,
  locale: String
});

const map = ref(null);
const isDownloading = ref(false);

// Status timeline configuration
const statusTimeline = computed(() => {
  const statuses = ['pending', 'confirmed', 'completed'];
  const currentIndex = statuses.indexOf(props.booking?.booking_status);

  return statuses.map((status, index) => ({
    key: status,
    label: status.charAt(0).toUpperCase() + status.slice(1),
    isActive: index <= currentIndex,
    isCurrent: index === currentIndex
  }));
});

// Map initialization
const initMap = () => {
  if (!props.vehicle?.latitude || !props.vehicle?.longitude) {
    return;
  }

  if (map.value) {
    map.value.remove();
  }

  map.value = L.map('booking-map', {
    zoomControl: true,
    maxZoom: 18,
    minZoom: 4,
    scrollWheelZoom: false
  }).setView([props.vehicle.latitude, props.vehicle.longitude], 15);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
  }).addTo(map.value);

  const customIcon = L.divIcon({
    className: 'custom-div-icon',
    html: `<div class="marker-pin"><img src="${MapPin}" alt="Location" /></div>`,
    iconSize: [40, 40],
    iconAnchor: [20, 40],
    popupAnchor: [0, -40]
  });

  L.marker([props.vehicle.latitude, props.vehicle.longitude], { icon: customIcon })
    .bindPopup(`
      <div class="text-center p-2">
        <p class="font-semibold text-[#153B4F]">Vehicle Location</p>
        <p class="text-sm text-gray-600">${props.vehicle.full_vehicle_address || props.booking.pickup_location || 'Location'}</p>
      </div>
    `)
    .addTo(map.value);

  setTimeout(() => map.value?.invalidateSize(), 100);
};

onMounted(() => {
  if (props.vehicle) {
    nextTick(initMap);
  }
});

// Utility functions
const getCurrencySymbol = (currency) => {
  const symbols = {
    'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥',
    'AUD': 'A$', 'CAD': 'C$', 'CHF': 'Fr', 'HKD': 'HK$',
    'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩', 'NOK': 'kr',
    'NZD': 'NZ$', 'INR': '₹', 'MXN': 'Mex$', 'ZAR': 'R',
    'AED': 'AED', 'MAD': 'د.م.', 'TRY': '₺'
  };
  return symbols[currency] || '$';
};

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number || 0);
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatTime = (timeStr) => {
  const [hours, minutes] = timeStr.split(':');
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12;
  return `${formattedHour}:${minutes} ${ampm}`;
};

// Status badge styling
const getStatusBadge = (status) => {
  const config = {
    pending: { bg: 'bg-amber-50', text: 'text-amber-700', border: 'border-amber-200', icon: '○' },
    confirmed: { bg: 'bg-emerald-50', text: 'text-emerald-700', border: 'border-emerald-200', icon: '✓' },
    completed: { bg: 'bg-blue-50', text: 'text-blue-700', border: 'border-blue-200', icon: '●' },
    cancelled: { bg: 'bg-rose-50', text: 'text-rose-700', border: 'border-rose-200', icon: '✕' }
  };
  return config[status] || config.pending;
};

// Download PDF
const downloadPDF = async () => {
  isDownloading.value = true;
  try {
    const url = route('booking.download.pdf', {
      locale: usePage().props.locale,
      id: props.booking?.id
    });

    // Open in new tab to download
    window.open(url, '_blank');
  } catch (error) {
    console.error('Error downloading PDF:', error);
    alert('Failed to download PDF. Please try again.');
  } finally {
    isDownloading.value = false;
  }
};

// Calculate rental period display
const rentalPeriodDisplay = computed(() => {
  const days = props.booking?.total_days || 0;
  const period = props.booking?.preferred_day || 'day';

  if (period === 'week' && days % 7 === 0) {
    return `${days / 7} ${days / 7 === 1 ? 'week' : 'weeks'}`;
  } else if (period === 'month' && days % 30 === 0) {
    return `${days / 30} ${days / 30 === 1 ? 'month' : 'months'}`;
  }
  return `${days} days`;
});
</script>

<template>
  <Head>
    <title>Booking Details - {{ booking?.booking_number }}</title>
    <meta name="robots" content="noindex, nofollow">
  </Head>

  <AuthenticatedHeaderLayout />

  <div class="min-h-screen bg-gray-50 pb-16">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-[#153B4F] to-[#245f7d] text-white py-8 px-4">
      <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold bg-white/20 border border-white/30"
              >
                <span>{{ getStatusBadge(booking?.booking_status).icon }}</span>
                <span class="capitalize">{{ booking?.booking_status }}</span>
              </span>
              <span class="text-white/70">|</span>
              <span class="text-white/90">{{ booking?.booking_number }}</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold">Booking Details</h1>
          </div>

          <!-- Action Buttons -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <button
              @click="downloadPDF"
              :disabled="isDownloading"
              class="inline-flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 sm:py-2 bg-white text-[#153B4F] rounded-lg font-medium hover:bg-gray-100 transition-colors disabled:opacity-50 text-sm sm:text-base w-full sm:w-auto"
            >
              <svg v-if="!isDownloading" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <svg v-else class="w-5 h-5 animate-spin flex-shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="hidden sm:inline">{{ isDownloading ? 'Downloading...' : 'Download PDF' }}</span>
              <span class="sm:hidden">Download</span>
            </button>
            <Link
              :href="route('profile.bookings.all', { locale })"
              class="inline-flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 sm:py-2 bg-white/10 text-white border border-white/30 rounded-lg font-medium hover:bg-white/20 transition-colors text-sm sm:text-base w-full sm:w-auto"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              <span class="hidden sm:inline">Back to Bookings</span>
              <span class="sm:hidden">Back</span>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 pt-6">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (2/3) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Status Timeline -->
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-[#153B4F] mb-8">Booking Progress</h2>
            <div class="flex items-center justify-between relative">
              <!-- Connection Line Background -->
              <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 -z-10 mx-12"></div>

              <div
                v-for="(step, index) in statusTimeline"
                :key="step.key"
                class="flex flex-col items-center relative z-10 flex-1"
              >
                <!-- Step Circle with Ripple Effect -->
                <div class="relative">
                  <!-- Ripple Effect for Active/Current Steps -->
                  <div
                    v-if="step.isActive"
                    class="absolute inset-0 rounded-full animate-ping opacity-20"
                    :class="step.isCurrent ? 'bg-[#009900]' : 'bg-[#153B4F]'"
                  ></div>
                  <div
                    v-if="step.isCurrent"
                    class="absolute inset-0 rounded-full animate-pulse opacity-40"
                    :class="step.isActive ? 'bg-[#009900]' : 'bg-[#153B4F]'"
                  ></div>

                  <!-- Step Circle -->
                  <div
                    class="w-10 h-10 rounded-full flex items-center justify-center text-base font-bold transition-all duration-300 shadow-md relative z-10"
                    :class="step.isCurrent ? 'bg-[#009900] text-white scale-110' : step.isActive ? 'bg-[#153B4F] text-white' : 'bg-white border-2 border-gray-300 text-gray-400'"
                  >
                    <span v-if="step.isCurrent">✓</span>
                    <span v-else>{{ index + 1 }}</span>
                  </div>
                </div>

                <!-- Step Label -->
                <p class="mt-4 text-sm font-semibold text-center capitalize" :class="step.isActive ? 'text-[#153B4F]' : 'text-gray-400'">
                  {{ step.label }}
                </p>
              </div>
            </div>
          </div>

          <!-- Trip Details -->
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-[#153B4F] mb-6">Trip Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Pickup -->
              <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-[#0099001A] to-transparent rounded-xl">
                <div class="w-12 h-12 rounded-full bg-[#0099001A] flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-[#009900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Pickup</p>
                  <p class="font-bold text-[#153B4F] text-lg">{{ booking?.pickup_location }}</p>
                  <p class="text-gray-600 mt-1">
                    {{ formatDate(booking?.pickup_date) }} at {{ formatTime(booking?.pickup_time) }}
                  </p>
                </div>
              </div>

              <!-- Return -->
              <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-[#EE1D521A] to-transparent rounded-xl">
                <div class="w-12 h-12 rounded-full bg-[#EE1D521A] flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-[#EE1D52]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Return</p>
                  <p class="font-bold text-[#153B4F] text-lg">{{ booking?.return_location }}</p>
                  <p class="text-gray-600 mt-1">
                    {{ formatDate(booking?.return_date) }} at {{ formatTime(booking?.return_time) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Map -->
            <div
              v-if="vehicle && vehicle.latitude && vehicle.longitude"
              class="mt-6"
            >
              <p class="text-sm font-semibold text-[#153B4F] mb-3">Pickup Location</p>
              <div id="booking-map" class="h-64 rounded-xl border border-gray-200"></div>
            </div>
          </div>

          <!-- Booking Reference -->
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-[#153B4F] mb-6">Booking Information</h2>

            <div class="space-y-4">
              <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">Booking Number</span>
                <span class="font-semibold text-[#153B4F]">{{ booking?.booking_number }}</span>
              </div>
              <div v-if="booking?.booking_reference" class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">Booking Reference</span>
                <span class="font-semibold text-[#153B4F]">{{ booking.booking_reference }}</span>
              </div>
              <div v-if="booking?.provider_source" class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">Provider</span>
                <span class="font-semibold text-[#153B4F] capitalize">{{ booking.provider_source.replace('_', ' ') }}</span>
              </div>
              <div v-if="payment" class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">Payment Method</span>
                <span class="font-semibold text-[#153B4F] capitalize">{{ payment.payment_method }}</span>
              </div>
              <div v-if="payment?.transaction_id" class="flex justify-between items-center py-3">
                <span class="text-gray-600">Transaction ID</span>
                <span class="font-mono text-sm text-gray-500">{{ payment.transaction_id }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar (1/3) -->
        <div class="space-y-6">
          <!-- Vehicle Card -->
          <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="relative h-48">
              <img
                v-if="vehicle?.images?.length"
                :src="vehicle.images.find(img => img.image_type === 'primary')?.image_url || vehicle.images[0]?.image_url"
                :alt="vehicle?.brand || booking?.vehicle_name"
                class="w-full h-full object-cover"
              />
              <div
                v-else-if="booking?.vehicle_image"
                class="w-full h-full bg-gray-50 flex items-center justify-center p-6"
              >
                <img :src="booking.vehicle_image" :alt="booking.vehicle_name" class="max-h-full object-contain" />
              </div>
              <div v-else class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </div>

              <!-- Category Badge -->
              <div class="absolute bottom-4 left-4">
                <span class="px-3 py-1.5 bg-white/95 backdrop-blur rounded-full text-sm font-semibold text-[#153B4F] shadow-sm">
                  {{ vehicle?.category?.name || 'Standard' }}
                </span>
              </div>
            </div>

            <div class="p-5">
              <h3 class="text-xl font-bold text-[#153B4F] mb-1">
                {{ vehicle?.brand || booking?.vehicle_name?.split(' ')[0] || 'Vehicle' }}
                <span v-if="vehicle?.model" class="font-normal">{{ vehicle.model }}</span>
              </h3>

              <!-- Specs -->
              <div v-if="vehicle" class="flex flex-wrap gap-3 mt-3 text-sm text-gray-600">
                <span class="capitalize">{{ vehicle.transmission }}</span>
                <span class="text-gray-300">•</span>
                <span class="capitalize">{{ vehicle.fuel }}</span>
                <span class="text-gray-300">•</span>
                <span>{{ vehicle.seating_capacity }} seats</span>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="bg-gradient-to-br from-[#153B4F] to-[#245f7d] rounded-2xl shadow-lg p-6 text-white">
            <h2 class="text-lg font-bold mb-6">Payment Summary</h2>

            <div class="space-y-3">
              <div class="flex justify-between items-center text-white/80">
                <span>Base Price ({{ booking?.preferred_day || 'day' }})</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.base_price) }}</span>
              </div>

              <div class="flex justify-between items-center text-white/80">
                <span>Vehicle Subtotal ({{ rentalPeriodDisplay }})</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber((booking?.base_price || 0) * (booking?.total_days || 0)) }}</span>
              </div>

              <div v-if="booking?.plan_price > 0" class="flex justify-between items-center text-white/80">
                <span>{{ booking?.plan }} Plan</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.plan_price * booking.total_days) }}</span>
              </div>

              <!-- Extras -->
              <div v-if="booking?.extras?.length" class="pt-3 border-t border-white/20">
                <p class="text-sm font-semibold text-white/90 mb-2">Extras</p>
                <div v-for="extra in booking.extras" :key="extra.id" class="flex justify-between items-center text-sm text-white/80 mb-1">
                  <span>{{ extra.extra_name }} × {{ extra.quantity }}</span>
                  <span>+{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(extra.price * booking.total_days) }}</span>
                </div>
              </div>

              <div v-if="booking?.discount_amount" class="flex justify-between items-center text-green-300">
                <span>Discount</span>
                <span>-{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.discount_amount) }}</span>
              </div>

              <div class="flex justify-between items-center text-white/80">
                <span>Extra Charges</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.extra_charges) }}</span>
              </div>

              <div class="flex justify-between items-center text-white/80">
                <span>Tax</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.tax_amount) }}</span>
              </div>
            </div>

            <!-- Total -->
            <div class="mt-6 pt-4 border-t border-white/20">
              <div class="flex justify-between items-center mb-3">
                <span class="text-white/80">Total Amount</span>
                <span class="text-2xl font-bold">{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.total_amount) }}</span>
              </div>

              <div v-if="booking?.amount_paid > 0" class="flex justify-between items-center">
                <span class="text-green-300">Amount Paid</span>
                <span class="text-xl font-semibold text-green-300">{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.amount_paid) }}</span>
              </div>

              <div v-if="booking?.pending_amount > 0" class="flex justify-between items-center mt-2">
                <span class="text-amber-300">Pending Amount</span>
                <span class="text-lg font-semibold text-amber-300">{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.pending_amount) }}</span>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col gap-3">
            <Link
              v-if="vendorProfile"
              :href="route('messages.index', { locale, vendor_id: vendorProfile.user_id })"
              class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#153B4F] text-white rounded-xl font-semibold hover:bg-[#0f2a38] transition-colors shadow-md"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
              </svg>
              Chat with Owner
            </Link>

            <Link
              v-if="booking?.payment_status === 'pending' && booking?.booking_status === 'pending'"
              href="#"
              class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-amber-500 text-white rounded-xl font-semibold hover:bg-amber-600 transition-colors shadow-md"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              Complete Payment
            </Link>
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
  height: 40px;
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.custom-div-icon {
  background: none;
  border: none;
}

#booking-map {
  min-height: 250px;
  width: 100%;
  z-index: 1;
}
</style>
