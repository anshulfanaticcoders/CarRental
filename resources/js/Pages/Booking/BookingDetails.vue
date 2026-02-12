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
const showHoursModal = ref(false);
const hoursTab = ref('pickup');

const providerMetadata = computed(() => props.booking?.provider_metadata || {});
const pickupDetails = computed(() => providerMetadata.value?.pickup_location_details || providerMetadata.value?.location || null);
const dropoffDetails = computed(() => providerMetadata.value?.dropoff_location_details || null);
const pickupInstructions = computed(() => providerMetadata.value?.location_instructions || pickupDetails.value?.collection_details || null);
const dropoffInstructions = computed(() => dropoffDetails.value?.collection_details || null);
const isGreenMotionBooking = computed(() => {
  const source = `${props.booking?.provider_source || ''}`.toLowerCase();
  return source === 'greenmotion' || source === 'usave';
});
const isRenteonBooking = computed(() => {
  const source = `${props.booking?.provider_source || ''}`.toLowerCase();
  return source === 'renteon';
});
const amountPaidLabel = computed(() => {
  if (isGreenMotionBooking.value) return 'Deposit paid';
  if (isRenteonBooking.value) return 'Commission paid';
  return _t('customerprofile', 'amount_paid');
});
const pendingAmountLabel = computed(() => {
  if (isGreenMotionBooking.value) return 'Pay at pickup';
  if (isRenteonBooking.value) return 'Pay at desk';
  return _t('customerprofile', 'pending_amount');
});

const formatLocationLines = (details) => {
  if (!details) return [];
  return [
    details.address_1,
    details.address_2,
    details.address_3,
    details.address_city,
    details.address_county,
    details.address_postcode,
  ].filter(Boolean);
};

const pickupLines = computed(() => formatLocationLines(pickupDetails.value));
const dropoffLines = computed(() => formatLocationLines(dropoffDetails.value));

const formatHourWindow = (window) => {
  if (!window) return '';
  const start = window.open || window.start || '';
  const end = window.close || window.end || '';
  const start2 = window.start2 || '';
  const end2 = window.end2 || '';
  const first = start && end ? `${start} - ${end}` : '';
  const second = start2 && end2 ? `${start2} - ${end2}` : '';
  return [first, second].filter(Boolean).join(' / ');
};

const hasHours = (details) => {
  if (!details) return false;
  return (
    (details.opening_hours && details.opening_hours.length)
    || (details.office_opening_hours && details.office_opening_hours.length)
    || (details.out_of_hours && details.out_of_hours.length)
    || (details.out_of_hours_dropoff && details.out_of_hours_dropoff.length)
    || (details.daytime_closures_hours && details.daytime_closures_hours.length)
    || details.out_of_hours_charge
  );
};

const hasPickupHours = computed(() => hasHours(pickupDetails.value));
const hasDropoffHours = computed(() => hasHours(dropoffDetails.value));

// Status timeline configuration
const statusTimeline = computed(() => {
  const statuses = ['pending', 'confirmed', 'completed'];
  const currentIndex = statuses.indexOf(props.booking?.booking_status);

  return statuses.map((status, index) => ({
    key: status,
    label: _t('customerprofile', status),
    isActive: index <= currentIndex,
    isCurrent: index === currentIndex
  }));
});

// Map initialization
const initMap = () => {
  const rawLat = props.vehicle?.latitude || pickupDetails.value?.latitude;
  const rawLng = props.vehicle?.longitude || pickupDetails.value?.longitude;
  const lat = rawLat !== null && rawLat !== undefined ? parseFloat(rawLat) : null;
  const lng = rawLng !== null && rawLng !== undefined ? parseFloat(rawLng) : null;
  if (!lat || !lng) return;

  if (map.value) {
    map.value.remove();
  }

  map.value = L.map('booking-map', {
    zoomControl: true,
    maxZoom: 18,
    minZoom: 4,
    scrollWheelZoom: false
  }).setView([lat, lng], 15);

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

  const popupAddress = pickupDetails.value
    ? formatLocationLines(pickupDetails.value).join(', ')
    : (props.vehicle?.full_vehicle_address || props.booking.pickup_location || 'Location');

  L.marker([lat, lng], { icon: customIcon })
    .bindPopup(`
      <div class="text-center p-2">
        <p class="font-semibold text-[#153B4F]">Vehicle Location</p>
        <p class="text-sm text-gray-600">${popupAddress}</p>
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

const displayExtras = computed(() => {
  const extras = props.booking?.extras || [];
  if (extras.length) {
    return extras.map(extra => ({
      name: extra.extra_name,
      qty: extra.quantity,
      total: (extra.price || 0) * (props.booking?.total_days || 1),
      isFree: false,
    }));
  }

  const providerExtras = providerMetadata.value?.extras_selected;
  if (Array.isArray(providerExtras)) {
    return providerExtras.map(extra => ({
      name: extra.name || extra.Name || 'Extra',
      qty: extra.qty || extra.quantity || 1,
      total: extra.total ?? 0,
      isFree: extra.isFree || false,
    }));
  }

  return [];
});
</script>

<template>
  <Head>
    <title>{{ _t('customerprofile', 'booking_details') }} - {{ booking?.booking_number }}</title>
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
            <h1 class="text-2xl md:text-3xl font-bold">{{ _t('customerprofile', 'booking_details') }}</h1>
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
              <span class="hidden sm:inline">{{ isDownloading ? _t('customerprofile', 'downloading_pdf') : _t('customerprofile', 'download_pdf') }}</span>
              <span class="sm:hidden">{{ _t('customerprofile', 'download') }}</span>
            </button>
            <Link
              :href="route('profile.bookings.all', { locale })"
              class="inline-flex items-center justify-center gap-2 px-3 py-2.5 sm:px-4 sm:py-2 bg-white/10 text-white border border-white/30 rounded-lg font-medium hover:bg-white/20 transition-colors text-sm sm:text-base w-full sm:w-auto"
            >
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
              <span class="hidden sm:inline">{{ _t('customerprofile', 'back_to_bookings') }}</span>
              <span class="sm:hidden">{{ _t('customerprofile', 'back') }}</span>
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
            <h2 class="text-lg font-bold text-[#153B4F] mb-8">{{ _t('customerprofile', 'booking_progress') }}</h2>
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
            <h2 class="text-lg font-bold text-[#153B4F] mb-6">{{ _t('customerprofile', 'trip_details') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Pickup -->
              <div class="flex items-start gap-4 p-4 bg-gradient-to-br from-[#0099001A] to-transparent rounded-xl">
                <div class="w-12 h-12 rounded-full bg-[#0099001A] flex items-center justify-center flex-shrink-0">
                  <svg class="w-6 h-6 text-[#009900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ _t('customerprofile', 'pickup') }}</p>
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
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ _t('customerprofile', 'return') }}</p>
                  <p class="font-bold text-[#153B4F] text-lg">{{ booking?.return_location }}</p>
                  <p class="text-gray-600 mt-1">
                    {{ formatDate(booking?.return_date) }} at {{ formatTime(booking?.return_time) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Map -->
            <div
              v-if="(vehicle && vehicle.latitude && vehicle.longitude) || pickupDetails?.latitude"
              class="mt-6"
            >
              <p class="text-sm font-semibold text-[#153B4F] mb-3">{{ _t('customerprofile', 'pickup_location') }}</p>
              <div id="booking-map" class="h-64 rounded-xl border border-gray-200"></div>
            </div>
          </div>

          <!-- Location Details (Providers) -->
          <div v-if="pickupDetails || dropoffDetails" class="bg-white rounded-2xl shadow-sm p-6">
            <div class="flex items-center justify-between gap-4 mb-6">
              <h2 class="text-lg font-bold text-[#153B4F]">Location Details</h2>
              <button
                v-if="hasPickupHours || hasDropoffHours"
                @click="showHoursModal = true"
                class="text-sm font-semibold text-[#153B4F] hover:text-[#0f2a38]"
              >
                View hours & policies
              </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div v-if="pickupDetails" class="rounded-xl border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-3">
                  <p class="text-sm font-semibold text-gray-600">Pickup Location</p>
                  <button
                    v-if="hasPickupHours"
                    @click="() => { hoursTab = 'pickup'; showHoursModal = true; }"
                    class="text-xs font-semibold text-[#153B4F] hover:text-[#0f2a38]"
                  >
                    Hours
                  </button>
                </div>
                <p v-if="pickupDetails.name" class="font-semibold text-[#153B4F]">{{ pickupDetails.name }}</p>
                <div v-if="pickupLines.length" class="text-sm text-gray-600 mt-2 space-y-1">
                  <p v-for="(line, index) in pickupLines" :key="`pickup-line-${index}`">{{ line }}</p>
                </div>
                <div class="text-sm text-gray-600 mt-3 space-y-1">
                  <p v-if="pickupDetails.telephone"><span class="font-semibold text-gray-700">Phone:</span> {{ pickupDetails.telephone }}</p>
                  <p v-if="pickupDetails.email"><span class="font-semibold text-gray-700">Email:</span> {{ pickupDetails.email }}</p>
                  <p v-if="pickupDetails.whatsapp"><span class="font-semibold text-gray-700">WhatsApp:</span> {{ pickupDetails.whatsapp }}</p>
                  <p v-if="pickupDetails.iata"><span class="font-semibold text-gray-700">Airport:</span> {{ pickupDetails.iata }}</p>
                </div>
                <p v-if="pickupInstructions" class="text-sm text-gray-600 mt-3">
                  <span class="font-semibold text-gray-700">Instructions:</span> {{ pickupInstructions }}
                </p>
              </div>

              <div v-if="dropoffDetails" class="rounded-xl border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-3">
                  <p class="text-sm font-semibold text-gray-600">Dropoff Location</p>
                  <button
                    v-if="hasDropoffHours"
                    @click="() => { hoursTab = 'dropoff'; showHoursModal = true; }"
                    class="text-xs font-semibold text-[#153B4F] hover:text-[#0f2a38]"
                  >
                    Hours
                  </button>
                </div>
                <p v-if="dropoffDetails.name" class="font-semibold text-[#153B4F]">{{ dropoffDetails.name }}</p>
                <div v-if="dropoffLines.length" class="text-sm text-gray-600 mt-2 space-y-1">
                  <p v-for="(line, index) in dropoffLines" :key="`dropoff-line-${index}`">{{ line }}</p>
                </div>
                <div class="text-sm text-gray-600 mt-3 space-y-1">
                  <p v-if="dropoffDetails.telephone"><span class="font-semibold text-gray-700">Phone:</span> {{ dropoffDetails.telephone }}</p>
                  <p v-if="dropoffDetails.email"><span class="font-semibold text-gray-700">Email:</span> {{ dropoffDetails.email }}</p>
                  <p v-if="dropoffDetails.whatsapp"><span class="font-semibold text-gray-700">WhatsApp:</span> {{ dropoffDetails.whatsapp }}</p>
                  <p v-if="dropoffDetails.iata"><span class="font-semibold text-gray-700">Airport:</span> {{ dropoffDetails.iata }}</p>
                </div>
                <p v-if="dropoffInstructions" class="text-sm text-gray-600 mt-3">
                  <span class="font-semibold text-gray-700">Instructions:</span> {{ dropoffInstructions }}
                </p>
              </div>
            </div>
          </div>

          <!-- Booking Reference -->
          <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-bold text-[#153B4F] mb-6">{{ _t('customerprofile', 'booking_information') }}</h2>

            <div class="space-y-4">
              <div class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">{{ _t('customerprofile', 'booking_number') }}</span>
                <span class="font-semibold text-[#153B4F]">{{ booking?.booking_number }}</span>
              </div>
              <div v-if="booking?.booking_reference" class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">{{ _t('customerprofile', 'booking_reference') }}</span>
                <span class="font-semibold text-[#153B4F]">{{ booking.booking_reference }}</span>
              </div>
              <div v-if="booking?.provider_source" class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">{{ _t('customerprofile', 'provider') }}</span>
                <span class="font-semibold text-[#153B4F] capitalize">{{ booking.provider_source.replace('_', ' ') }}</span>
              </div>
              <div v-if="payment" class="flex justify-between items-center py-3 border-b border-gray-100">
                <span class="text-gray-600">{{ _t('customerprofile', 'payment_method') }}</span>
                <span class="font-semibold text-[#153B4F] capitalize">{{ payment.payment_method }}</span>
              </div>
              <div v-if="payment?.transaction_id" class="flex justify-between items-center py-3">
                <span class="text-gray-600">{{ _t('customerprofile', 'transaction_id') }}</span>
                <span class="font-mono text-sm text-gray-500">{{ payment.transaction_id }}</span>
              </div>
            </div>
          </div>

          <!-- Vendor Contact Info (Internal Vehicles Only) -->
          <div
            v-if="vendorProfile"
            class="space-y-6"
          >
            <!-- Vendor Contact Person -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
              <h2 class="text-lg font-bold text-[#153B4F] mb-6">{{ _t('customerprofile', 'vendor_contact_info') }}</h2>

              <div class="space-y-3">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">{{ _t('customerprofile', 'contact_person') }}</h3>

                <!-- Vendor Name -->
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-bold text-[#153B4F] text-lg">
                      {{ vendorProfile.user?.first_name }} {{ vendorProfile.user?.last_name }}
                    </p>
                    <p class="text-sm text-gray-500">{{ _t('customerprofile', 'vendor') }}</p>
                  </div>
                </div>

                <!-- Vendor Email -->
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-8 h-8 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">{{ _t('customerprofile', 'email') }}</p>
                    <a v-if="vendorProfile.user?.email" :href="`mailto:${vendorProfile.user.email}`" class="text-sm font-semibold text-[#153B4F] hover:text-[#245f7d] break-all">
                      {{ vendorProfile.user.email }}
                    </a>
                    <span v-else class="text-sm text-gray-400">Not provided</span>
                  </div>
                </div>

                <!-- Vendor Phone -->
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-8 h-8 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">{{ _t('customerprofile', 'phone') }}</p>
                    <a v-if="vendorProfile.user?.phone" :href="`tel:${vendorProfile.user.phone}`" class="text-sm font-semibold text-[#153B4F] hover:text-[#245f7d]">
                      {{ vendorProfile.user.phone }}
                    </a>
                    <span v-else class="text-sm text-gray-400">Not provided</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Company Contact Info -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
              <h2 class="text-lg font-bold text-[#153B4F] mb-6">{{ _t('customerprofile', 'company_name') }}</h2>

              <div class="space-y-3">
                <!-- Company Name -->
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-12 h-12 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                  </div>
                  <div>
                    <p v-if="vendorProfile.company_name" class="font-bold text-[#153B4F]">{{ vendorProfile.company_name }}</p>
                    <p v-else class="font-medium text-gray-400">Not provided</p>
                  </div>
                </div>

                <!-- Company Email -->
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-8 h-8 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">{{ _t('customerprofile', 'email') }}</p>
                    <a v-if="vendorProfile.company_email" :href="`mailto:${vendorProfile.company_email}`" class="text-sm font-semibold text-[#153B4F] hover:text-[#245f7d] break-all">
                      {{ vendorProfile.company_email }}
                    </a>
                    <span v-else class="text-sm text-gray-400">Not provided</span>
                  </div>
                </div>

                <!-- Company Phone -->
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-8 h-8 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">{{ _t('customerprofile', 'phone') }}</p>
                    <a v-if="vendorProfile.company_phone_number" :href="`tel:${vendorProfile.company_phone_number}`" class="text-sm font-semibold text-[#153B4F] hover:text-[#245f7d]">
                      {{ vendorProfile.company_phone_number }}
                    </a>
                    <span v-else class="text-sm text-gray-400">Not provided</span>
                  </div>
                </div>

                <!-- Company Address -->
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                  <div class="w-8 h-8 rounded-full bg-[#153B4F1A] flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4 text-[#153B4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs text-gray-500">{{ _t('customerprofile', 'address') }}</p>
                    <p v-if="vendorProfile.company_address" class="text-sm font-medium text-[#153B4F]">{{ vendorProfile.company_address }}</p>
                    <p v-else class="text-sm text-gray-400">Not provided</p>
                  </div>
                </div>
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
                  {{ vehicle?.category?.name || _t('customerprofile', 'standard_category') }}
                </span>
              </div>
            </div>

            <div class="p-5">
              <h3 class="text-xl font-bold text-[#153B4F] mb-1">
                {{ vehicle?.brand || booking?.vehicle_name?.split(' ')[0] || _t('customerprofile', 'vehicle') }}
                <span v-if="vehicle?.model" class="font-normal">{{ vehicle.model }}</span>
              </h3>

              <!-- Specs -->
              <div v-if="vehicle" class="flex flex-wrap gap-3 mt-3 text-sm text-gray-600">
                <span class="capitalize">{{ vehicle.transmission }}</span>
                <span class="text-gray-300">•</span>
                <span class="capitalize">{{ vehicle.fuel }}</span>
                <span class="text-gray-300">•</span>
                <span>{{ vehicle.seating_capacity }} {{ _t('customerprofile', 'seats') }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="bg-gradient-to-br from-[#153B4F] to-[#245f7d] rounded-2xl shadow-lg p-6 text-white">
            <h2 class="text-lg font-bold mb-6">{{ _t('customerprofile', 'payment_summary') }}</h2>

            <div class="space-y-3">
              <div class="flex justify-between items-center text-white/80">
                <span>{{ _t('customerprofile', 'base_price') }} ({{ booking?.preferred_day || 'day' }})</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.base_price) }}</span>
              </div>

              <div class="flex justify-between items-center text-white/80">
                <span>{{ _t('customerprofile', 'vehicle_subtotal') }} ({{ rentalPeriodDisplay }})</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber((booking?.base_price || 0) * (booking?.total_days || 0)) }}</span>
              </div>

              <div v-if="booking?.plan_price > 0" class="flex justify-between items-center text-white/80">
                <span>{{ booking?.plan }} Plan</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.plan_price * booking.total_days) }}</span>
              </div>

              <!-- Extras -->
              <div v-if="displayExtras.length" class="pt-3 border-t border-white/20">
                <p class="text-sm font-semibold text-white/90 mb-2">{{ _t('customerprofile', 'extras') }}</p>
                <div v-for="(extra, index) in displayExtras" :key="`extra-${index}`" class="flex justify-between items-center text-sm text-white/80 mb-1">
                  <span>{{ extra.name }} × {{ extra.qty }}</span>
                  <span>+{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(extra.isFree ? 0 : extra.total) }}</span>
                </div>
              </div>

              <div v-if="booking?.discount_amount" class="flex justify-between items-center text-green-300">
                <span>{{ _t('customerprofile', 'discount') }}</span>
                <span>-{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.discount_amount) }}</span>
              </div>

              <div class="flex justify-between items-center text-white/80">
                <span>{{ _t('customerprofile', 'extra_charges') }}</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.extra_charges) }}</span>
              </div>

              <div class="flex justify-between items-center text-white/80">
                <span>{{ _t('customerprofile', 'tax') }}</span>
                <span>{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.tax_amount) }}</span>
              </div>
            </div>

            <!-- Total -->
            <div class="mt-6 pt-4 border-t border-white/20">
              <div class="flex justify-between items-center mb-3">
                <span class="text-white/80">{{ _t('customerprofile', 'total_amount') }}</span>
                <span class="text-2xl font-bold">{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking?.total_amount) }}</span>
              </div>

              <div v-if="booking?.amount_paid > 0" class="flex justify-between items-center">
                <span class="text-green-300">{{ amountPaidLabel }}</span>
                <span class="text-xl font-semibold text-green-300">{{ getCurrencySymbol(booking?.booking_currency) }}{{ formatNumber(booking.amount_paid) }}</span>
              </div>

              <div v-if="booking?.pending_amount > 0" class="flex justify-between items-center mt-2">
                <span class="text-amber-300">{{ pendingAmountLabel }}</span>
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
              {{ _t('customerprofile', 'chat_with_owner') }}
            </Link>

            <Link
              v-if="booking?.payment_status === 'pending' && booking?.booking_status === 'pending'"
              href="#"
              class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-amber-500 text-white rounded-xl font-semibold hover:bg-amber-600 transition-colors shadow-md"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              {{ _t('customerprofile', 'complete_payment') }}
            </Link>
          </div>
        </div>
      </div>
    </div>
  </div>

  <Footer />

  <div v-if="showHoursModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full">
      <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <div class="flex items-center gap-3">
          <h3 class="text-lg font-bold text-[#153B4F]">Hours & Policies</h3>
          <div v-if="pickupDetails && dropoffDetails" class="flex gap-2">
            <button
              @click="hoursTab = 'pickup'"
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="hoursTab === 'pickup' ? 'bg-[#153B4F] text-white' : 'bg-gray-100 text-gray-600'"
            >
              Pickup
            </button>
            <button
              @click="hoursTab = 'dropoff'"
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="hoursTab === 'dropoff' ? 'bg-[#153B4F] text-white' : 'bg-gray-100 text-gray-600'"
            >
              Dropoff
            </button>
          </div>
        </div>
        <button @click="showHoursModal = false" class="text-gray-500 hover:text-gray-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
        <div v-if="hoursTab === 'pickup' && pickupDetails">
          <p class="text-sm font-semibold text-gray-600 mb-3">Pickup Location</p>

          <div v-if="pickupDetails.opening_hours?.length" class="mb-4">
            <p class="text-sm font-semibold text-gray-800 mb-2">Opening Hours</p>
            <div class="space-y-1 text-sm text-gray-600">
              <p v-for="(day, index) in pickupDetails.opening_hours" :key="`pickup-open-${index}`">
                <span class="font-medium text-gray-700">{{ day.name }}:</span>
                <span class="ml-1">{{ formatHourWindow(day) || 'Closed' }}</span>
              </p>
            </div>
          </div>

          <div v-if="pickupDetails.office_opening_hours?.length" class="mb-4">
            <p class="text-sm font-semibold text-gray-800 mb-2">Office Hours</p>
            <div class="space-y-1 text-sm text-gray-600">
              <p v-for="(day, index) in pickupDetails.office_opening_hours" :key="`pickup-office-${index}`">
                <span class="font-medium text-gray-700">{{ day.name }}:</span>
                <span class="ml-1">{{ formatHourWindow(day) || 'Closed' }}</span>
              </p>
            </div>
          </div>

          <div v-if="pickupDetails.out_of_hours?.length" class="mb-4">
            <p class="text-sm font-semibold text-gray-800 mb-2">Out of Hours</p>
            <div class="space-y-1 text-sm text-gray-600">
              <p v-for="(day, index) in pickupDetails.out_of_hours" :key="`pickup-out-${index}`">
                <span class="font-medium text-gray-700">{{ day.name }}:</span>
                <span class="ml-1">{{ formatHourWindow(day) || 'Unavailable' }}</span>
              </p>
            </div>
          </div>

          <div v-if="pickupDetails.out_of_hours_charge" class="text-sm text-gray-600">
            <span class="font-semibold text-gray-800">Out of Hours Charge:</span> {{ pickupDetails.out_of_hours_charge }}
          </div>
        </div>

        <div v-if="hoursTab === 'dropoff' && dropoffDetails">
          <p class="text-sm font-semibold text-gray-600 mb-3">Dropoff Location</p>

          <div v-if="dropoffDetails.out_of_hours_dropoff?.length" class="mb-4">
            <p class="text-sm font-semibold text-gray-800 mb-2">Out of Hours Dropoff</p>
            <div class="space-y-1 text-sm text-gray-600">
              <p v-for="(day, index) in dropoffDetails.out_of_hours_dropoff" :key="`dropoff-out-${index}`">
                <span class="font-medium text-gray-700">{{ day.name }}:</span>
                <span class="ml-1">{{ formatHourWindow(day) || 'Unavailable' }}</span>
              </p>
            </div>
          </div>

          <div v-if="dropoffDetails.daytime_closures_hours?.length" class="mb-4">
            <p class="text-sm font-semibold text-gray-800 mb-2">Daytime Closures</p>
            <div class="space-y-1 text-sm text-gray-600">
              <p v-for="(day, index) in dropoffDetails.daytime_closures_hours" :key="`dropoff-closure-${index}`">
                <span class="font-medium text-gray-700">{{ day.name }}:</span>
                <span class="ml-1">{{ formatHourWindow(day) || 'None' }}</span>
              </p>
            </div>
          </div>

          <div v-if="dropoffDetails.out_of_hours_charge" class="text-sm text-gray-600">
            <span class="font-semibold text-gray-800">Out of Hours Charge:</span> {{ dropoffDetails.out_of_hours_charge }}
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
