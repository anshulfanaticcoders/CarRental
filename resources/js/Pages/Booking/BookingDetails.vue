<script setup>
import { ref, reactive, onMounted, nextTick, computed } from "vue";
import { Link, usePage, Head } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import MapPin from "../../../assets/MapPin.svg";
import Footer from "@/Components/Footer.vue";
import { getCurrentInstance } from 'vue';
import { useBookingData } from '@/Composables/useBookingData';

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

const bookingData = reactive(useBookingData(props.booking, props.vehicle, props.payment));

const map = ref(null);
const isDownloading = ref(false);
const showHoursModal = ref(false);
const showPoliciesModal = ref(false);
const hoursTab = ref('pickup');

const providerMetadata = computed(() => props.booking?.provider_metadata || {});

// Parse plan features (may arrive as JSON string from backend)
const planFeatures = computed(() => {
  const features = props.plan?.features;
  if (!features) return [];
  if (Array.isArray(features)) return features;
  if (typeof features === 'string') {
    try { return JSON.parse(features); } catch { return []; }
  }
  return [];
});
const pickupDetails = computed(() => bookingData.pickupLocation || {});
const dropoffDetails = computed(() => bookingData.dropoffLocation || {});
const pickupInstructions = computed(() => bookingData.pickupLocation?.pickupInstructions);
const dropoffInstructions = computed(() => bookingData.dropoffLocation?.dropoffInstructions);

const formatLocationLines = (details) => {
  if (!details) return [];
  const addressLine1 = details.address_1 || details.address || null;
  const addressCity = details.address_city || details.town || null;
  const addressPostcode = details.address_postcode || details.postal_code || null;
  return [
    addressLine1,
    details.address_2,
    details.address_3,
    addressCity,
    details.address_county,
    addressPostcode,
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
    attribution: '&copy; OpenStreetMap contributors'
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

const getCurrencySymbol = (currency) => {
  const symbols = {
    'USD': '$', 'EUR': '€', 'GBP': '£', 'JPY': '¥',
    'AUD': 'A$', 'CAD': 'C$', 'CHF': 'Fr', 'HKD': 'HK$',
    'SGD': 'S$', 'SEK': 'kr', 'KRW': '₩', 'NOK': 'kr',
    'NZD': 'NZ$', 'INR': '₹', 'MXN': 'Mex$', 'ZAR': 'R',
    'AED': 'AED', 'MAD': 'د.م.', 'TRY': '₺'
  };
  return symbols[currency] || currency || '$';
};

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number || 0);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatDateShort = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const formatTime = (timeStr) => {
  if (!timeStr) return '';
  const [hours, minutes] = timeStr.split(':');
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12;
  return `${formattedHour}:${minutes} ${ampm}`;
};

const getStatusBadge = (status) => {
  const config = {
    pending: { bg: 'bg-amber-400/20', text: 'text-amber-200', border: 'border-amber-400/30', dot: 'bg-amber-400' },
    confirmed: { bg: 'bg-emerald-400/20', text: 'text-emerald-200', border: 'border-emerald-400/30', dot: 'bg-emerald-400' },
    completed: { bg: 'bg-blue-400/20', text: 'text-blue-200', border: 'border-blue-400/30', dot: 'bg-blue-400' },
    cancelled: { bg: 'bg-rose-400/20', text: 'text-rose-200', border: 'border-rose-400/30', dot: 'bg-rose-400' }
  };
  return config[status] || config.pending;
};

const downloadPDF = async () => {
  isDownloading.value = true;
  try {
    const url = route('booking.download.pdf', {
      locale: usePage().props.locale,
      id: props.booking?.id
    });
    window.open(url, '_blank');
  } catch (error) {
    console.error('Error downloading PDF:', error);
  } finally {
    isDownloading.value = false;
  }
};

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

const displayExtras = computed(() => bookingData.normalizedExtras || []);

// Format deposit payment method (JSON array or string from vendor settings)
const formatDepositMethod = (method) => {
  if (!method) return '';
  let methods = [];
  try {
    if (typeof method === 'string' && (method.startsWith('[') || method.startsWith('{'))) {
      const parsed = JSON.parse(method);
      methods = Array.isArray(parsed) ? parsed : [method];
    } else if (Array.isArray(method)) {
      methods = method;
    } else {
      methods = [method];
    }
  } catch {
    methods = [method];
  }
  return methods.map(m => m.toString().replace(/[_-]/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1).toLowerCase()).join(' ')).join(', ');
};

const pricingSummary = computed(() => {
  const pricing = bookingData.pricingBreakdown || {};
  const currency = pricing.currency || 'EUR';
  const payment = pricing.payment || {};
  const bookingPricing = pricing.booking || {};

  return {
    currency,
    vehicleTotal: bookingPricing.vehicleTotal || 0,
    extrasTotal: bookingPricing.extrasTotal || 0,
    taxTotal: bookingPricing.taxTotal || 0,
    discountTotal: bookingPricing.discountTotal || 0,
    grandTotal: bookingPricing.grandTotal || 0,
    paidNow: payment.paidNow || 0,
    dueOnArrival: payment.dueOnArrival || 0,
    paidPercentage: payment.paidPercentage || 0,
    duePercentage: payment.duePercentage || 100,
    isPOA: payment.isPOA || false,
    deposit: pricing.deposit || null,
    excess: pricing.excess || null,
    excessTheft: pricing.excessTheft || null,
    securityDeposit: bookingData.policies?.securityDeposit || null,
    depositPaymentMethod: bookingData.policies?.depositPaymentMethod || null,
    selectedDepositType: bookingData.policies?.selectedDepositType || null,
  };
});

const customerSnapshot = computed(() => providerMetadata.value?.customer_snapshot || {});

const vehicleImage = computed(() => {
  if (props.vehicle?.images?.length) {
    return props.vehicle.images.find(img => img.image_type === 'primary')?.image_url || props.vehicle.images[0]?.image_url;
  }
  return props.booking?.vehicle_image || providerMetadata.value?.image || '';
});

const vehicleName = computed(() => {
  if (props.vehicle?.brand) {
    return `${props.vehicle.brand} ${props.vehicle.model || ''}`.trim();
  }
  return props.booking?.vehicle_name || 'Vehicle';
});

const vendorInitials = computed(() => {
  const vp = props.vendorProfile;
  if (!vp?.user) return 'V';
  const f = vp.user.first_name?.[0] || '';
  const l = vp.user.last_name?.[0] || '';
  return (f + l).toUpperCase() || 'V';
});
</script>

<template>
  <Head>
    <title>{{ _t('customerprofile', 'booking_details') }} - {{ booking?.booking_number }}</title>
    <meta name="robots" content="noindex, nofollow">
  </Head>

  <AuthenticatedHeaderLayout />

  <div class="min-h-screen bg-[#f5f7fa] pb-16">

    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-200">
      <div class="full-w-container py-3 flex items-center gap-2 text-sm">
        <Link :href="route('welcome', { locale })" class="text-gray-400 hover:text-[var(--gray-800)] transition flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
          {{ _t('customerprofile', 'home') || 'Home' }}
        </Link>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <Link :href="route('profile.bookings.all', { locale })" class="text-gray-400 hover:text-[var(--gray-800)] transition">
          {{ _t('customerprofile', 'my_bookings') || 'My Bookings' }}
        </Link>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-[var(--gray-800)] font-semibold">{{ booking?.booking_number }}</span>
      </div>
    </div>

    <!-- Hero Highlight Card -->
    <div class="full-w-container mt-5">
      <div class="hero-card px-6 py-6 md:px-8 md:py-7">
        <div class="relative z-10">
          <!-- Top row -->
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3 flex-wrap">
              <div class="flex items-center gap-2">
                <span class="text-white/50 text-sm">{{ _t('customerprofile', 'booking') || 'Booking' }}</span>
                <span class="font-mono text-lg font-bold tracking-wide text-white">{{ booking?.booking_number }}</span>
              </div>
              <span
                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border status-pulse"
                :class="[getStatusBadge(booking?.booking_status).bg, getStatusBadge(booking?.booking_status).text, getStatusBadge(booking?.booking_status).border]"
              >
                <span class="w-2 h-2 rounded-full" :class="getStatusBadge(booking?.booking_status).dot"></span>
                <span class="capitalize">{{ booking?.booking_status }}</span>
              </span>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <button
                @click="downloadPDF"
                :disabled="isDownloading"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-[var(--gray-800)] rounded-xl text-sm font-bold hover:bg-gray-100 transition shadow-sm disabled:opacity-50"
              >
                <svg v-if="!isDownloading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <svg v-else class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isDownloading ? _t('customerprofile', 'downloading_pdf') : _t('customerprofile', 'download_pdf') || 'Download Receipt' }}
              </button>
              <Link
                :href="route('profile.bookings.all', { locale })"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-white/10 border border-white/20 text-white rounded-xl text-sm font-semibold hover:bg-white/20 transition"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ _t('customerprofile', 'back_to_bookings') || 'All Bookings' }}
              </Link>
            </div>
          </div>

          <!-- Quick info pills -->
          <div class="flex gap-3 quick-pills-row">
            <div class="quick-pill">
              <span class="pill-label">{{ _t('customerprofile', 'pickup') || 'Pickup' }}</span>
              <span class="pill-value">{{ formatDateShort(booking?.pickup_date) }}</span>
              <span class="pill-sub">{{ formatTime(booking?.pickup_time) }} &middot; {{ booking?.pickup_location }}</span>
            </div>
            <div class="quick-pill">
              <span class="pill-label">{{ _t('customerprofile', 'return') || 'Return' }}</span>
              <span class="pill-value">{{ formatDateShort(booking?.return_date) }}</span>
              <span class="pill-sub">{{ formatTime(booking?.return_time) }} &middot; {{ booking?.return_location || booking?.pickup_location }}</span>
            </div>
            <div class="quick-pill">
              <span class="pill-label">{{ _t('customerprofile', 'duration') || 'Duration' }}</span>
              <span class="pill-value">{{ rentalPeriodDisplay }}</span>
              <span class="pill-sub">{{ vehicleName }}</span>
            </div>
            <div class="quick-pill quick-pill--accent">
              <span class="pill-label" style="color: #5cd3d9;">{{ _t('customerprofile', 'total_amount') || 'Total Amount' }}</span>
              <span class="pill-value text-white" style="font-size: 20px;">{{ bookingData.formatCurrency(pricingSummary.grandTotal) }}</span>
              <span class="pill-sub" style="color: #5cd3d9;">{{ pricingSummary.currency }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Alert Bar -->
    <div class="full-w-container mt-4">
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Paid -->
        <div v-if="pricingSummary.paidNow > 0" class="flex-1 flex items-center gap-4 bg-emerald-50 border border-emerald-200 rounded-2xl px-5 py-4">
          <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wide">
              {{ pricingSummary.isPOA ? `${_t('customerprofile', 'paid_online') || 'Paid Online'} (${pricingSummary.paidPercentage}%)` : _t('customerprofile', 'paid_in_full') || 'Paid in Full' }}
            </p>
            <p class="amount-xl text-emerald-700 mt-0.5">{{ bookingData.formatCurrency(pricingSummary.paidNow) }}</p>
            <p class="text-xs text-emerald-600/70 mt-1">{{ _t('customerprofile', 'deposit_via_stripe') || 'Deposit via Stripe' }}</p>
          </div>
          <svg class="w-5 h-5 text-emerald-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        </div>
        <!-- Due at pickup -->
        <div v-if="pricingSummary.dueOnArrival > 0" class="flex-1 flex items-center gap-4 bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4">
          <div class="w-11 h-11 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-amber-700 uppercase tracking-wide">{{ _t('customerprofile', 'due_at_pickup') || 'Due at Pickup' }} ({{ pricingSummary.duePercentage }}%)</p>
            <p class="amount-xl text-amber-800 mt-0.5">{{ bookingData.formatCurrency(pricingSummary.dueOnArrival) }}</p>
            <p class="text-xs text-amber-600/70 mt-1">{{ _t('customerprofile', 'pay_to_vendor') || 'Pay to vendor on arrival' }}</p>
          </div>
        </div>
      </div>
      <!-- Progress bar -->
      <div v-if="pricingSummary.isPOA" class="mt-3 px-1">
        <div class="flex justify-between text-[11px] text-gray-400 font-medium mb-1.5">
          <span>{{ _t('customerprofile', 'payment_progress') || 'Payment Progress' }}</span>
          <span>{{ pricingSummary.paidPercentage }}% {{ _t('customerprofile', 'paid') || 'paid' }}</span>
        </div>
        <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden">
          <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-500 transition-all" :style="{ width: `${pricingSummary.paidPercentage}%` }"></div>
        </div>
      </div>
    </div>

    <!-- Main 2-column grid -->
    <div class="full-w-container py-6">
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

        <!-- LEFT (3/5) -->
        <div class="lg:col-span-3 space-y-5">

          <!-- Vehicle & Trip -->
          <div class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-[var(--primary-50,#f0f8fc)]">
                <svg class="w-4 h-4 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'vehicle_and_trip') || 'Vehicle & Trip' }}</h3>
            </div>
            <div class="bd-card-body">
              <!-- Vehicle info -->
              <div class="flex items-start gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100 mb-5">
                <div class="w-28 h-20 bg-white rounded-xl border border-gray-200 flex items-center justify-center overflow-hidden flex-shrink-0">
                  <img v-if="vehicleImage" :src="vehicleImage" :alt="vehicleName" class="w-full h-full object-contain" />
                  <svg v-else class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="text-lg font-bold text-[var(--gray-800,#153b4f)] tracking-tight">{{ vehicleName }}</p>
                  <div class="flex flex-wrap items-center gap-2 mt-1.5">
                    <span v-if="vehicle?.transmission || bookingData.vehicleSpecs.transmission" class="bd-spec-tag">
                      {{ vehicle?.transmission || bookingData.vehicleSpecs.transmission }}
                    </span>
                    <span v-if="vehicle?.fuel || bookingData.vehicleSpecs.fuel" class="bd-spec-tag">
                      {{ vehicle?.fuel || bookingData.vehicleSpecs.fuel }}
                    </span>
                    <span v-if="vehicle?.seating_capacity || bookingData.vehicleSpecs.seats" class="bd-spec-tag">
                      {{ vehicle?.seating_capacity || bookingData.vehicleSpecs.seats }} {{ _t('customerprofile', 'seats') || 'Seats' }}
                    </span>
                    <span v-if="booking?.plan" class="inline-flex items-center px-2 py-0.5 rounded-md bg-[var(--accent-50,#edf9fa)] border border-[var(--accent-100,#d5f2f4)] text-[11px] font-bold text-[var(--accent-600,#2ea7ad)]">
                      {{ booking.plan }} Plan
                    </span>
                  </div>
                </div>
              </div>

              <!-- Pickup / Return Timeline -->
              <div class="bd-loc-timeline space-y-6">
                <!-- Pickup -->
                <div class="relative pl-7">
                  <div class="bd-loc-dot bd-loc-dot--pickup"></div>
                  <div>
                    <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">{{ _t('customerprofile', 'pickup') || 'Pickup' }}</span>
                    <p class="text-base font-bold text-gray-900 mt-0.5">
                      {{ formatDateShort(booking?.pickup_date) }}
                      <span class="font-normal text-gray-500">&middot;</span>
                      <span class="text-[var(--primary-600,#245f7d)]">{{ formatTime(booking?.pickup_time) }}</span>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                      <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      {{ booking?.pickup_location }}
                    </p>
                  </div>
                </div>
                <!-- Return -->
                <div class="relative pl-7">
                  <div class="bd-loc-dot bd-loc-dot--return"></div>
                  <div>
                    <span class="text-[11px] font-bold text-red-500 uppercase tracking-wider">{{ _t('customerprofile', 'return') || 'Return' }}</span>
                    <p class="text-base font-bold text-gray-900 mt-0.5">
                      {{ formatDateShort(booking?.return_date) }}
                      <span class="font-normal text-gray-500">&middot;</span>
                      <span class="text-[var(--primary-600,#245f7d)]">{{ formatTime(booking?.return_time) }}</span>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                      <svg class="w-3.5 h-3.5 inline -mt-0.5 mr-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                      {{ booking?.return_location || booking?.pickup_location }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Map -->
              <div
                v-if="(vehicle && vehicle.latitude && vehicle.longitude) || pickupDetails?.latitude"
                class="mt-5"
              >
                <div id="booking-map" class="h-44 rounded-2xl border border-gray-200"></div>
              </div>
            </div>
          </div>

          <!-- Location Details (for external providers) -->
          <div v-if="pickupDetails?.name || dropoffDetails?.name" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-[var(--primary-50,#f0f8fc)]">
                <svg class="w-4 h-4 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
              </div>
              <h3>Location Details</h3>
              <button
                v-if="hasPickupHours || hasDropoffHours"
                @click="showHoursModal = true"
                class="ml-auto text-xs font-semibold text-[var(--primary-600,#245f7d)] hover:underline"
              >
                View hours
              </button>
            </div>
            <div class="bd-card-body">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="pickupDetails?.name" class="rounded-xl border border-gray-100 p-4">
                  <p class="text-xs font-bold text-emerald-600 uppercase mb-2">Pickup</p>
                  <p class="font-semibold text-[var(--gray-800,#153b4f)] text-sm">{{ pickupDetails.name }}</p>
                  <div v-if="pickupLines.length" class="text-xs text-gray-500 mt-1.5 space-y-0.5">
                    <p v-for="(line, i) in pickupLines" :key="i">{{ line }}</p>
                  </div>
                  <div class="text-xs text-gray-500 mt-2 space-y-0.5">
                    <p v-if="pickupDetails.telephone || pickupDetails.phone">Tel: {{ pickupDetails.telephone || pickupDetails.phone }}</p>
                    <p v-if="pickupDetails.email">{{ pickupDetails.email }}</p>
                  </div>
                  <p v-if="pickupInstructions" class="text-xs text-gray-600 mt-2 p-2 bg-amber-50 border border-amber-100 rounded-lg">{{ pickupInstructions }}</p>
                </div>
                <div v-if="dropoffDetails?.name" class="rounded-xl border border-gray-100 p-4">
                  <p class="text-xs font-bold text-red-500 uppercase mb-2">Return</p>
                  <p class="font-semibold text-[var(--gray-800,#153b4f)] text-sm">{{ dropoffDetails.name }}</p>
                  <div v-if="dropoffLines.length" class="text-xs text-gray-500 mt-1.5 space-y-0.5">
                    <p v-for="(line, i) in dropoffLines" :key="i">{{ line }}</p>
                  </div>
                  <div class="text-xs text-gray-500 mt-2 space-y-0.5">
                    <p v-if="dropoffDetails.telephone || dropoffDetails.phone">Tel: {{ dropoffDetails.telephone || dropoffDetails.phone }}</p>
                    <p v-if="dropoffDetails.email">{{ dropoffDetails.email }}</p>
                  </div>
                  <p v-if="dropoffInstructions" class="text-xs text-gray-600 mt-2 p-2 bg-amber-50 border border-amber-100 rounded-lg">{{ dropoffInstructions }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Extras / Add-ons -->
          <div v-if="displayExtras.length" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-purple-50">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'extras') || 'Extras & Add-ons' }}</h3>
            </div>
            <div class="bd-card-body space-y-2.5">
              <div
                v-for="(extra, index) in displayExtras"
                :key="`extra-${index}`"
                class="flex items-center justify-between p-3.5 rounded-xl bg-gray-50 border border-gray-100"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-800">{{ extra.name }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                      Qty: {{ extra.quantity }}
                      <span v-if="extra.included" class="text-emerald-500 font-semibold ml-1">Included</span>
                    </p>
                  </div>
                </div>
                <span class="text-sm font-bold text-gray-900">{{ bookingData.formatCurrency(extra.total) }}</span>
              </div>
            </div>
          </div>

          <!-- Your Details (from metadata customer_snapshot) -->
          <div class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-[var(--primary-50,#f0f8fc)]">
                <svg class="w-4 h-4 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'your_details') || 'Your Details' }}</h3>
            </div>
            <div class="bd-card-body">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                <div>
                  <p class="bd-label">{{ _t('customerprofile', 'full_name') || 'Full Name' }}</p>
                  <p class="bd-value">{{ customerSnapshot.name || `${booking?.customer?.first_name || ''} ${booking?.customer?.last_name || ''}`.trim() || '-' }}</p>
                </div>
                <div>
                  <p class="bd-label">{{ _t('customerprofile', 'email') || 'Email' }}</p>
                  <p class="bd-value">{{ customerSnapshot.email || booking?.customer?.email || '-' }}</p>
                </div>
                <div>
                  <p class="bd-label">{{ _t('customerprofile', 'phone') || 'Phone' }}</p>
                  <p class="bd-value">{{ customerSnapshot.phone || booking?.customer?.phone || '-' }}</p>
                </div>
                <div>
                  <p class="bd-label">{{ _t('customerprofile', 'driver_age') || 'Driver Age' }}</p>
                  <p class="bd-value">{{ customerSnapshot.driver_age || booking?.customer?.driver_age || '-' }}</p>
                </div>
                <div v-if="customerSnapshot.flight_number || booking?.customer?.flight_number">
                  <p class="bd-label">{{ _t('customerprofile', 'flight_number') || 'Flight Number' }}</p>
                  <div class="flex items-center gap-1.5">
                    <span class="inline-flex items-center justify-center w-5 h-5 rounded bg-[var(--primary-50,#f0f8fc)]">
                      <svg class="w-3 h-3 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </span>
                    <p class="text-sm font-bold text-[var(--primary-700,#1c4d66)]">{{ customerSnapshot.flight_number || booking?.customer?.flight_number }}</p>
                  </div>
                </div>
                <div v-if="customerSnapshot.address">
                  <p class="bd-label">{{ _t('customerprofile', 'address') || 'Address' }}</p>
                  <p class="bd-value">{{ customerSnapshot.address }}</p>
                </div>
              </div>
              <div v-if="customerSnapshot.notes" class="mt-5 pt-4 border-t border-gray-100">
                <p class="bd-label">{{ _t('customerprofile', 'notes') || 'Notes' }}</p>
                <p class="text-sm text-gray-600 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 mt-1">{{ customerSnapshot.notes }}</p>
              </div>
            </div>
          </div>

          <!-- Plan Features -->
          <div v-if="planFeatures.length > 0" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-indigo-50">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
              </div>
              <h3>{{ booking?.plan }} Plan Features</h3>
            </div>
            <div class="bd-card-body">
              <p v-if="plan?.plan_description" class="text-sm text-gray-500 mb-3">{{ plan.plan_description }}</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <div v-for="(feature, idx) in planFeatures" :key="idx" class="flex items-center gap-2 p-2.5 rounded-lg bg-gray-50 border border-gray-100">
                  <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span class="text-sm text-gray-700">{{ typeof feature === 'string' ? feature : (feature.name || feature.label || feature.feature || JSON.stringify(feature)) }}</span>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- RIGHT (2/5) -->
        <div class="lg:col-span-2 space-y-5">

          <!-- Price Breakdown -->
          <div class="bd-card overflow-hidden">
            <div class="bd-card-header bg-gray-50/60">
              <div class="bd-icon-box bg-[var(--primary-50,#f0f8fc)]">
                <svg class="w-4 h-4 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'price_breakdown') || 'Price Breakdown' }}</h3>
              <span class="ml-auto text-[11px] font-bold text-[var(--primary-600,#245f7d)] bg-[var(--primary-50,#f0f8fc)] px-2 py-0.5 rounded-full">{{ pricingSummary.currency }}</span>
            </div>
            <div class="bd-card-body space-y-0">
              <div class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'vehicle_rental') || 'Vehicle Rental' }} ({{ rentalPeriodDisplay }})</span>
                <span class="bd-info-value">{{ bookingData.formatCurrency(pricingSummary.vehicleTotal) }}</span>
              </div>
              <div v-for="(extra, index) in displayExtras" :key="`price-extra-${index}`" class="bd-info-row">
                <span class="bd-info-label">{{ extra.name }} &times; {{ extra.quantity }}</span>
                <span class="bd-info-value">{{ bookingData.formatCurrency(extra.total) }}</span>
              </div>
              <div v-if="!displayExtras.length && pricingSummary.extrasTotal > 0" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'extras') || 'Extras' }}</span>
                <span class="bd-info-value">{{ bookingData.formatCurrency(pricingSummary.extrasTotal) }}</span>
              </div>
              <div v-if="pricingSummary.taxTotal > 0" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'tax_fees') || 'Tax & Fees' }}</span>
                <span class="bd-info-value">{{ bookingData.formatCurrency(pricingSummary.taxTotal) }}</span>
              </div>
              <div v-if="pricingSummary.discountTotal > 0" class="bd-info-row">
                <span class="bd-info-label text-emerald-600">{{ _t('customerprofile', 'discount') || 'Discount' }}</span>
                <span class="bd-info-value text-emerald-600">-{{ bookingData.formatCurrency(pricingSummary.discountTotal) }}</span>
              </div>

              <!-- Grand total -->
              <div class="mt-2 -mx-5 -mb-5 px-5 py-4 bg-[var(--gray-800,#153b4f)] rounded-b-2xl">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-semibold text-white/70">{{ _t('customerprofile', 'grand_total') || 'Grand Total' }}</span>
                  <span class="text-2xl font-extrabold text-white tracking-tight">{{ bookingData.formatCurrency(pricingSummary.grandTotal) }}</span>
                </div>
                <p v-if="pricingSummary.isPOA" class="text-right text-[11px] text-white/40 mt-0.5">{{ _t('customerprofile', 'includes_platform_fee') || 'Includes 15% platform fee' }}</p>
              </div>
            </div>
          </div>

          <!-- Deposit & Insurance -->
          <div v-if="pricingSummary.deposit || pricingSummary.excess || pricingSummary.securityDeposit" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-blue-50">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'deposit_insurance') || 'Deposit & Insurance' }}</h3>
            </div>
            <div class="bd-card-body space-y-3">
              <!-- Internal vehicle security deposit -->
              <div v-if="pricingSummary.securityDeposit" class="flex justify-between items-center">
                <span class="text-sm text-gray-500">{{ _t('customerprofile', 'security_deposit') || 'Security Deposit' }}</span>
                <span class="text-sm font-bold text-gray-800">{{ bookingData.formatCurrency(pricingSummary.securityDeposit, pricingSummary.deposit?.currency) }}</span>
              </div>
              <!-- External provider deposit -->
              <div v-else-if="pricingSummary.deposit" class="flex justify-between items-center">
                <span class="text-sm text-gray-500">{{ _t('customerprofile', 'security_deposit') || 'Security Deposit' }}</span>
                <span class="text-sm font-bold text-gray-800">{{ bookingData.formatCurrency(pricingSummary.deposit.amount, pricingSummary.deposit.currency) }}</span>
              </div>
              <!-- Customer's chosen deposit type -->
              <div v-if="pricingSummary.selectedDepositType" class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Deposit Method</span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-sm font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                  {{ formatDepositMethod(pricingSummary.selectedDepositType) }}
                </span>
              </div>
              <!-- Vendor accepted methods (if customer hasn't selected one) -->
              <div v-else-if="pricingSummary.depositPaymentMethod" class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Accepted Methods</span>
                <span class="text-sm font-semibold text-gray-600">{{ formatDepositMethod(pricingSummary.depositPaymentMethod) }}</span>
              </div>
              <div v-if="pricingSummary.excess" class="flex justify-between items-center">
                <span class="text-sm text-gray-500">{{ _t('customerprofile', 'insurance_excess') || 'Insurance Excess' }}</span>
                <span class="text-sm font-bold text-gray-800">{{ bookingData.formatCurrency(pricingSummary.excess.amount, pricingSummary.excess.currency) }}</span>
              </div>
              <div v-if="pricingSummary.excessTheft" class="flex justify-between items-center">
                <span class="text-sm text-gray-500">Theft Excess</span>
                <span class="text-sm font-bold text-gray-800">{{ bookingData.formatCurrency(pricingSummary.excessTheft.amount, pricingSummary.excessTheft.currency) }}</span>
              </div>
              <div class="p-3 rounded-xl bg-blue-50 border border-blue-100">
                <div class="flex items-start gap-2">
                  <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                  <p class="text-xs text-blue-700 leading-relaxed">{{ _t('customerprofile', 'deposit_info') || 'Held on your card at pickup. Released within 7-14 days after return, subject to vehicle inspection.' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Company Information -->
          <div v-if="vendorProfile?.company_name" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-[var(--primary-50,#f0f8fc)]">
                <svg class="w-4 h-4 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'company_name') || 'Company Details' }}</h3>
            </div>
            <div class="bd-card-body space-y-0">
              <div class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'company_name') || 'Company Name' }}</span>
                <span class="bd-info-value">{{ vendorProfile.company_name }}</span>
              </div>
              <div v-if="vendorProfile.company_email" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'email') || 'Email' }}</span>
                <a :href="`mailto:${vendorProfile.company_email}`" class="bd-info-value text-[var(--primary-600,#245f7d)] hover:underline">{{ vendorProfile.company_email }}</a>
              </div>
              <div v-if="vendorProfile.company_phone_number" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'phone') || 'Phone' }}</span>
                <a :href="`tel:${vendorProfile.company_phone_number}`" class="bd-info-value text-[var(--primary-600,#245f7d)] hover:underline">{{ vendorProfile.company_phone_number }}</a>
              </div>
              <div v-if="vendorProfile.company_address" class="bd-info-row" style="border-bottom: none;">
                <span class="bd-info-label">{{ _t('customerprofile', 'address') || 'Address' }}</span>
                <span class="bd-info-value text-right max-w-[60%]">{{ vendorProfile.company_address }}</span>
              </div>
            </div>
          </div>

          <!-- Vendor Contact -->
          <div v-if="vendorProfile" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-[var(--primary-50,#f0f8fc)]">
                <svg class="w-4 h-4 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'vendor_contact_info') || 'Vendor Contact' }}</h3>
            </div>
            <div class="bd-card-body">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-11 h-11 rounded-full bg-[var(--primary-100,#dceef6)] flex items-center justify-center text-[var(--primary-700,#1c4d66)] font-bold text-sm ring-2 ring-[var(--primary-200,#b0d4e6)]/50">{{ vendorInitials }}</div>
                <div>
                  <p class="text-sm font-bold text-[var(--gray-800,#153b4f)]">{{ vendorProfile.user?.first_name }} {{ vendorProfile.user?.last_name }}</p>
                  <p class="text-[11px] text-gray-400 uppercase tracking-wide font-semibold">{{ _t('customerprofile', 'contact_person') || 'Contact Person' }}</p>
                </div>
              </div>
              <div class="space-y-2 mb-4">
                <div v-if="vendorProfile.user?.email" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50">
                  <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  <a :href="`mailto:${vendorProfile.user.email}`" class="text-sm text-gray-700 font-medium truncate hover:text-[var(--primary-600,#245f7d)]">{{ vendorProfile.user.email }}</a>
                </div>
                <div v-if="vendorProfile.user?.phone || vendorProfile.company_phone_number" class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50">
                  <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                  <a :href="`tel:${vendorProfile.user?.phone || vendorProfile.company_phone_number}`" class="text-sm text-gray-700 font-medium hover:text-[var(--primary-600,#245f7d)]">{{ vendorProfile.user?.phone || vendorProfile.company_phone_number }}</a>
                </div>
              </div>
              <Link
                :href="route('messages.index', { locale, vendor_id: vendorProfile.user_id })"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-[var(--gray-800,#153b4f)] text-white rounded-xl text-sm font-bold hover:bg-[var(--gray-900,#0f2936)] transition"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                {{ _t('customerprofile', 'chat_with_owner') || 'Chat with Vendor' }}
              </Link>
            </div>
          </div>

          <!-- Booking References -->
          <div class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-gray-100">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'booking_references') || 'Booking References' }}</h3>
            </div>
            <div class="bd-card-body space-y-0">
              <div class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'booking_number') || 'Booking Number' }}</span>
                <span class="bd-info-value font-mono text-[var(--primary-700,#1c4d66)]">{{ booking?.booking_number }}</span>
              </div>
              <div v-if="booking?.provider_source" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'provider') || 'Provider' }}</span>
                <span class="bd-info-value capitalize">{{ booking.provider_source.replace('_', ' ') }}</span>
              </div>
              <div v-if="payment?.payment_method" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'payment_method') || 'Payment Method' }}</span>
                <span class="bd-info-value capitalize">{{ payment.payment_method }}</span>
              </div>
              <div v-if="payment?.transaction_id" class="bd-info-row">
                <span class="bd-info-label">{{ _t('customerprofile', 'transaction_id') || 'Transaction' }}</span>
                <span class="bd-info-value font-mono text-xs text-gray-500">{{ payment.transaction_id }}</span>
              </div>
              <div class="bd-info-row" style="border-bottom: none;">
                <span class="bd-info-label">{{ _t('customerprofile', 'booked_on') || 'Booked On' }}</span>
                <span class="bd-info-value">{{ formatDate(booking?.created_at) }}</span>
              </div>
            </div>
          </div>

          <!-- Rental Policies -->
          <div v-if="bookingData.policies.fuelPolicy || bookingData.policies.mileage || bookingData.policies.minimumDriverAge || bookingData.policies.cancellation" class="bd-card">
            <div class="bd-card-header">
              <div class="bd-icon-box bg-purple-50">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              </div>
              <h3>{{ _t('customerprofile', 'rental_policies') || 'Rental Policies' }}</h3>
              <button
                @click="showPoliciesModal = true"
                class="ml-auto text-xs font-semibold text-[var(--primary-600,#245f7d)] hover:underline"
              >
                {{ _t('customerprofile', 'view_all') || 'View all' }}
              </button>
            </div>
            <div class="bd-card-body">
              <div class="grid grid-cols-2 gap-2.5">
                <div v-if="bookingData.policies.fuelPolicy" class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">{{ _t('customerprofile', 'fuel_policy') || 'Fuel Policy' }}</p>
                  <p class="text-sm font-bold text-gray-800 mt-1 capitalize">{{ bookingData.policies.fuelPolicy }}</p>
                </div>
                <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">{{ _t('customerprofile', 'mileage') || 'Mileage' }}</p>
                  <p class="text-sm font-bold text-gray-800 mt-1">{{ bookingData.policies.mileage }}</p>
                </div>
                <div v-if="bookingData.policies.minimumDriverAge" class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">{{ _t('customerprofile', 'min_age') || 'Min Age' }}</p>
                  <p class="text-sm font-bold text-gray-800 mt-1">{{ bookingData.policies.minimumDriverAge }}+</p>
                </div>
                <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">{{ _t('customerprofile', 'cancellation') || 'Cancellation' }}</p>
                  <p class="text-sm font-bold mt-1" :class="bookingData.policies.cancellation?.includes('Free') ? 'text-emerald-600' : 'text-amber-600'">{{ bookingData.policies.cancellation }}</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <Footer />

  <!-- Hours Modal -->
  <div v-if="showHoursModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full">
      <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
        <div class="flex items-center gap-3">
          <h3 class="text-lg font-bold text-[var(--gray-800,#153b4f)]">Hours & Policies</h3>
          <div v-if="pickupDetails && dropoffDetails" class="flex gap-2">
            <button
              @click="hoursTab = 'pickup'"
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="hoursTab === 'pickup' ? 'bg-[var(--gray-800,#153b4f)] text-white' : 'bg-gray-100 text-gray-600'"
            >
              Pickup
            </button>
            <button
              @click="hoursTab = 'dropoff'"
              class="px-3 py-1 rounded-full text-xs font-semibold"
              :class="hoursTab === 'dropoff' ? 'bg-[var(--gray-800,#153b4f)] text-white' : 'bg-gray-100 text-gray-600'"
            >
              Dropoff
            </button>
          </div>
        </div>
        <button @click="showHoursModal = false" class="text-gray-500 hover:text-gray-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
        <div v-if="hoursTab === 'pickup' && pickupDetails">
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

  <!-- Policies Modal -->
  <div v-if="showPoliciesModal" class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/50 px-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
      <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 flex-shrink-0">
        <h3 class="text-lg font-bold text-[var(--gray-800,#153b4f)]">{{ _t('customerprofile', 'rental_policies') || 'Rental Policies & Information' }}</h3>
        <button @click="showPoliciesModal = false" class="text-gray-500 hover:text-gray-700">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
      </div>
      <div class="px-6 py-5 overflow-y-auto flex-1">
        <div class="space-y-6">
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--primary-50,#f0f8fc)] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-[var(--gray-800,#153b4f)]">Fuel Policy</h4>
              <p class="text-gray-600 capitalize">{{ bookingData.policies.fuelPolicy }}</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--primary-50,#f0f8fc)] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-[var(--gray-800,#153b4f)]">Mileage</h4>
              <p class="text-gray-600">{{ bookingData.policies.mileage }}</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--primary-50,#f0f8fc)] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-[var(--gray-800,#153b4f)]">Driver Requirements</h4>
              <p class="text-gray-600">Minimum age: <span class="font-semibold">{{ bookingData.policies.minimumDriverAge }}+ years</span></p>
            </div>
          </div>
          <div v-if="pricingSummary.deposit || pricingSummary.excess" class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--primary-50,#f0f8fc)] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-[var(--gray-800,#153b4f)]">Security Deposit & Excess</h4>
              <p v-if="pricingSummary.deposit" class="text-gray-600">Deposit: <span class="font-semibold">{{ bookingData.formatCurrency(pricingSummary.deposit.amount, pricingSummary.deposit.currency) }}</span></p>
              <p v-if="pricingSummary.excess" class="text-gray-600">Excess: <span class="font-semibold">{{ bookingData.formatCurrency(pricingSummary.excess.amount, pricingSummary.excess.currency) }}</span></p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--primary-50,#f0f8fc)] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-[var(--gray-800,#153b4f)]">Cancellation</h4>
              <p class="text-gray-600">{{ bookingData.policies.cancellation }}</p>
            </div>
          </div>
          <div class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-full bg-[var(--primary-50,#f0f8fc)] flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[var(--primary-600,#245f7d)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
            </div>
            <div>
              <h4 class="font-semibold text-[var(--gray-800,#153b4f)]">Payment</h4>
              <p class="text-gray-600">
                <span class="font-semibold text-emerald-600">{{ bookingData.formatCurrency(pricingSummary.paidNow) }}</span> paid now
                <span v-if="pricingSummary.dueOnArrival > 0">, <span class="font-semibold text-amber-600">{{ bookingData.formatCurrency(pricingSummary.dueOnArrival) }}</span> due at pickup</span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Hero highlight card */
.hero-card {
  background: linear-gradient(135deg, var(--gray-800, #153b4f) 0%, var(--gray-700, #1c4d66) 55%, var(--gray-600, #245f7d) 100%);
  border-radius: 20px;
  color: white;
  position: relative;
  overflow: hidden;
}
.hero-card::before {
  content: '';
  position: absolute;
  top: -40%;
  right: -20%;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(46, 167, 173, 0.08);
}

/* Quick pills */
.quick-pills-row { display: flex; gap: 12px; }
.quick-pill {
  display: flex;
  flex-direction: column;
  gap: 2px;
  padding: 14px 18px;
  border-radius: 14px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.1);
  flex: 1;
  min-width: 0;
}
.quick-pill--accent {
  background: rgba(46,167,173,0.15);
  border-color: rgba(46,167,173,0.3);
}
.pill-label { font-size: 11px; font-weight: 500; opacity: 0.65; text-transform: uppercase; letter-spacing: 0.06em; }
.pill-value { font-size: 15px; font-weight: 700; }
.pill-sub { font-size: 12px; opacity: 0.7; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Payment amount */
.amount-xl {
  font-size: 28px;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1;
}

/* Cards */
.bd-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e8ecf1;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}
.bd-card-header {
  padding: 16px 20px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  align-items: center;
  gap: 10px;
}
.bd-card-header h3 {
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800, #153b4f);
  letter-spacing: -0.01em;
}
.bd-card-body { padding: 20px; }

.bd-icon-box {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

/* Spec tag */
.bd-spec-tag {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  border-radius: 6px;
  background: white;
  border: 1px solid #e2e8f0;
  font-size: 11px;
  font-weight: 600;
  color: #475569;
  text-transform: capitalize;
}

/* Location timeline */
.bd-loc-timeline { position: relative; }
.bd-loc-timeline::before {
  content: '';
  position: absolute;
  left: 8px;
  top: 16px;
  bottom: 16px;
  width: 2px;
  background: repeating-linear-gradient(to bottom, #cbd5e1 0, #cbd5e1 4px, transparent 4px, transparent 8px);
}
.bd-loc-dot {
  position: absolute;
  left: 0;
  top: 2px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bd-loc-dot::after {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: block;
}
.bd-loc-dot--pickup { background: #d1fae5; border: 2px solid #34d399; }
.bd-loc-dot--pickup::after { background: #10b981; }
.bd-loc-dot--return { background: #fee2e2; border: 2px solid #f87171; }
.bd-loc-dot--return::after { background: #ef4444; }

/* Info rows */
.bd-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #f1f5f9;
}
.bd-info-row:last-child { border-bottom: none; }
.bd-info-label { font-size: 13px; color: #64748b; }
.bd-info-value { font-size: 13px; font-weight: 600; color: #1e293b; }

/* Labels */
.bd-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; }
.bd-value { font-size: 14px; font-weight: 600; color: #1e293b; }

/* Status pulse */
@keyframes pulseGlow {
  0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.3); }
  50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
}
.status-pulse { animation: pulseGlow 2s infinite; }

/* Map marker */
:deep(.custom-div-icon) { background: transparent !important; border: none !important; }
:deep(.marker-pin) { display: flex; align-items: center; justify-content: center; }
:deep(.marker-pin img) { width: 40px; height: 40px; }

/* Responsive */
@media (max-width: 768px) {
  .quick-pills-row { flex-direction: column; }
  .amount-xl { font-size: 22px; }
}
</style>
