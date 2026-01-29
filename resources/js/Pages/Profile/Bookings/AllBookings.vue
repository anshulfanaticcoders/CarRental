<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import carIcon from '../../../../assets/carIcon.svg';
import { getCurrentInstance } from 'vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
  bookings: Object,
});

const activeTab = ref('all');
const isLoading = ref(false);

// Status tabs configuration
const statusTabs = [
  { key: 'all', label: _t('customerbooking', 'all_bookings') || 'All Bookings' },
  { key: 'pending', label: _t('customerbooking', 'pending') || 'Pending' },
  { key: 'confirmed', label: _t('customerbooking', 'confirmed') || 'Confirmed' },
  { key: 'completed', label: _t('customerbooking', 'completed') || 'Completed' },
  { key: 'cancelled', label: _t('customerbooking', 'cancelled') || 'Cancelled' },
];

// Filter bookings by status (client-side)
const filteredBookings = computed(() => {
  if (!props.bookings?.data) return [];

  if (activeTab.value === 'all') {
    return props.bookings.data;
  }

  return props.bookings.data.filter(booking =>
    booking.booking_status === activeTab.value
  );
});

// Get status badge styling
const getStatusBadge = (status) => {
  const statusConfig = {
    pending: {
      bg: 'bg-amber-50',
      text: 'text-amber-700',
      border: 'border-amber-200',
      icon: '○'
    },
    confirmed: {
      bg: 'bg-emerald-50',
      text: 'text-emerald-700',
      border: 'border-emerald-200',
      icon: '✓'
    },
    completed: {
      bg: 'bg-blue-50',
      text: 'text-blue-700',
      border: 'border-blue-200',
      icon: '●'
    },
    cancelled: {
      bg: 'bg-rose-50',
      text: 'text-rose-700',
      border: 'border-rose-200',
      icon: '✕'
    }
  };

  return statusConfig[status] || statusConfig.pending;
};

// Get provider badge styling
const getProviderBadge = (provider) => {
  if (!provider) return { bg: 'bg-gray-100', text: 'text-gray-700' };

  const providerConfig = {
    greenmotion: { bg: 'bg-teal-50', text: 'text-teal-700' },
    usave: { bg: 'bg-teal-50', text: 'text-teal-700' },
    adobe: { bg: 'bg-orange-50', text: 'text-orange-700' },
    wheelsys: { bg: 'bg-indigo-50', text: 'text-indigo-700' },
    okmobility: { bg: 'bg-purple-50', text: 'text-purple-700' },
    locauto_rent: { bg: 'bg-pink-50', text: 'text-pink-700' },
    renteon: { bg: 'bg-cyan-50', text: 'text-cyan-700' },
    internal: { bg: 'bg-sky-50', text: 'text-sky-700' }
  };

  return providerConfig[provider.toLowerCase()] || { bg: 'bg-gray-100', text: 'text-gray-700' };
};

const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatTime = (timeString) => {
  const [hours, minutes] = timeString.split(':');
  const hour = parseInt(hours);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12;
  return `${formattedHour}:${minutes} ${ampm}`;
};

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

const getBookingCurrency = (booking) => {
  return booking.amounts?.booking_currency || booking.booking_currency || 'EUR';
};

const getBookingAmount = (booking, field) => {
  const bookingFieldMap = {
    total_amount: 'booking_total_amount',
    amount_paid: 'booking_amount_paid',
    pending_amount: 'booking_pending_amount',
  };

  const mappedField = bookingFieldMap[field];
  if (mappedField && booking.amounts?.[mappedField] !== undefined && booking.amounts?.[mappedField] !== null) {
    return parseFloat(booking.amounts[mappedField]);
  }

  if (booking.amounts?.[field] !== undefined && booking.amounts?.[field] !== null) {
    return parseFloat(booking.amounts[field]);
  }

  return parseFloat(booking[field] || 0);
};

const formatNumber = (number) => {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(number);
};

const getBookingImageUrl = (booking) => {
  if (booking.vehicle?.images?.length) {
    return booking.vehicle.images.find(img => img.image_type === 'primary')?.image_url
      || booking.vehicle.images[0]?.image_url
      || null;
  }
  return booking.vehicle_image || null;
};

const handleTabChange = (tab) => {
  activeTab.value = tab;
};

const handlePageChange = (page) => {
  isLoading.value = true;
  router.get(route('profile.bookings.all', { locale: usePage().props.locale }), { page }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { isLoading.value = false; }
  });
};

const retryPayment = async (bookingId) => {
  isLoading.value = true;
  try {
    const axios = (await import('axios')).default;
    const response = await axios.post(route('payment.retry', { locale: usePage().props.locale }), { booking_id: bookingId });

    if (response.data.sessionId) {
      const { loadStripe } = await import('@stripe/stripe-js');
      const stripe = await loadStripe(import.meta.env.VITE_STRIPE_KEY);
      await stripe.redirectToCheckout({ sessionId: response.data.sessionId });
    }
  } catch (error) {
    console.error('Error retrying payment:', error);
    alert('Failed to retry payment. Please try again later.');
  } finally {
    isLoading.value = false;
  }
};

const isGreenMotionBooking = (booking) => {
  const source = booking.provider_source?.toLowerCase();
  return source === 'greenmotion' || source === 'usave';
};

const canCancelGreenMotion = (booking) => {
  if (!isGreenMotionBooking(booking)) return false;
  if (!booking.provider_booking_ref) return false;
  return !['cancelled', 'completed'].includes(booking.booking_status);
};

const cancelGreenMotionBooking = async (booking) => {
  const confirmMessage = _t('customerbooking', 'modal_confirm_cancellation_message')
    || 'Are you sure you want to cancel this booking?';
  if (!confirm(confirmMessage)) return;

  const reasonPrompt = 'Please enter a cancellation reason:';
  const reason = prompt(reasonPrompt) || '';
  const trimmedReason = reason.trim();

  if (trimmedReason.length < 3) {
    alert('Cancellation reason is required.');
    return;
  }

  isLoading.value = true;
  try {
    const axios = (await import('axios')).default;
    await axios.post(route('booking.cancel', { locale: usePage().props.locale }), {
      booking_id: booking.id,
      cancellation_reason: trimmedReason
    });
    router.reload({ preserveScroll: true });
  } catch (error) {
    console.error('Error canceling booking:', error);
    const message = error?.response?.data?.message || 'Failed to cancel booking. Please try again.';
    alert(message);
  } finally {
    isLoading.value = false;
  }
};

// Stagger animation delays
const getCardDelay = (index) => {
  return index * 50;
};
</script>

<template>
  <MyProfileLayout>
    <!-- Loading Overlay -->
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm">
      <div class="flex flex-col items-center gap-4">
        <div class="w-12 h-12 border-4 border-[#153B4F] border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-600 font-medium">{{ _t('customerbooking', 'loading') }}</p>
      </div>
    </div>

    <div class="container mx-auto px-4 max-[768px]:px-0 py-8">
      <!-- Page Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-[#153B4F] mb-2">
          {{ _t('customerbooking', 'my_bookings') || 'My Bookings' }}
        </h1>
        <p class="text-gray-500">
          {{ _t('customerbooking', 'bookings_subtitle') || 'Manage all your car rental bookings in one place' }}
        </p>
      </div>

      <!-- Status Filter Tabs -->
      <div class="mb-8 overflow-x-auto">
        <div class="flex gap-2 min-w-max">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            @click="handleTabChange(tab.key)"
            class="px-5 py-2.5 rounded-full font-medium text-sm transition-all duration-200"
            :class="[
              activeTab === tab.key
                ? 'bg-[#153B4F] text-white shadow-lg shadow-[#153B4F]/20'
                : 'bg-white text-gray-600 border border-gray-200 hover:border-[#153B4F] hover:text-[#153B4F]'
            ]"
          >
            {{ tab.label }}
            <span v-if="tab.key === 'all' && bookings?.total" class="ml-2 opacity-70">({{ bookings.total }})</span>
          </button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredBookings.length === 0" class="text-center py-16">
        <div class="flex flex-col items-center">
          <img :src="bookingstatusIcon" alt="No bookings" class="w-64 h-64 opacity-50 mb-6" />
          <h3 class="text-xl font-semibold text-gray-700 mb-2">
            {{ _t('customerbooking', 'no_bookings_found') || 'No bookings found' }}
          </h3>
          <p class="text-gray-500 mb-6">
            {{ activeTab === 'all'
              ? (_t('customerbooking', 'no_bookings_yet') || 'You haven\'t made any bookings yet.')
              : (_t('customerbooking', 'no_status_bookings') || `No ${activeTab} bookings found.`)
            }}
          </p>
          <Link
            :href="route('search', { locale: usePage().props.locale })"
            class="inline-flex items-center gap-2 px-6 py-3 bg-[#153B4F] text-white rounded-lg font-medium hover:bg-[#0f2a38] transition-colors shadow-lg shadow-[#153B4F]/20"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            {{ _t('customerbooking', 'search_vehicles') || 'Search Vehicles' }}
          </Link>
        </div>
      </div>

      <!-- Bookings Grid -->
      <div v-else class="space-y-6">
        <div
          v-for="(booking, index) in filteredBookings"
          :key="booking.id"
          class="booking-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-xl hover:border-gray-200 transition-all duration-300"
          :style="{ animationDelay: `${getCardDelay(index)}ms` }"
        >
          <div class="flex flex-col lg:flex-row">
            <!-- Vehicle Image Section -->
            <div class="lg:w-[30%] relative overflow-hidden min-h-56 lg:min-h-0">
              <Link
                v-if="booking.vehicle"
                :href="route('vehicle.show', { locale: usePage().props.locale, id: booking.vehicle.id })"
                class="absolute inset-0"
              >
                <div
                  v-if="getBookingImageUrl(booking)"
                  class="w-full h-full bg-center bg-no-repeat bg-cover"
                  :style="{ backgroundImage: `url(${getBookingImageUrl(booking)})` }"
                  :aria-label="booking.vehicle?.brand || booking.vehicle_name"
                ></div>
              </Link>
              <div
                v-else-if="getBookingImageUrl(booking)"
                class="absolute inset-0 w-full h-full bg-center bg-no-repeat bg-cover"
                :style="{ backgroundImage: `url(${getBookingImageUrl(booking)})` }"
                :aria-label="booking.vehicle_name"
              ></div>
              <div v-else class="w-full h-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                <svg class="w-24 h-24 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
              </div>
              <div class="absolute inset-0 bg-gradient-to-t from-black/25 via-black/0 to-black/10"></div>

              <!-- Status Badge Overlay -->
              <div class="absolute top-4 left-4">
                <span
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold border shadow-sm backdrop-blur-sm bg-white/85"
                  :class="[getStatusBadge(booking.booking_status).text, getStatusBadge(booking.booking_status).border]"
                >
                  <span>{{ getStatusBadge(booking.booking_status).icon }}</span>
                  <span class="capitalize">{{ booking.booking_status }}</span>
                </span>
              </div>
            </div>

            <!-- Booking Details Section -->
            <div class="lg:w-[70%] p-6 lg:p-8 flex flex-col">
              <!-- Header -->
              <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div class="flex items-start gap-4">
                  <div>
                    <h3 class="text-2xl font-bold text-[#153B4F]">
                      {{ booking.vehicle?.brand || booking.vehicle_name?.split(' ')[0] || 'Vehicle' }}
                      <span v-if="booking.vehicle?.model" class="font-normal">{{ booking.vehicle.model }}</span>
                    </h3>
                    <p v-if="booking.booking_number" class="text-sm text-gray-500 mt-1">
                      Ref: {{ booking.booking_number }}
                    </p>
                  </div>
                  <span
                    v-if="booking.vehicle?.category"
                    class="px-3 py-1 rounded-full text-sm font-medium bg-[#154D6A0D] text-[#153B4F]"
                  >
                    {{ booking.vehicle.category.name }}
                  </span>
                  <span
                    v-else-if="booking.provider_source"
                    class="px-3 py-1 rounded-full text-sm font-medium capitalize border"
                    :class="getProviderBadge(booking.provider_source)"
                  >
                    {{ booking.provider_source.replace('_', ' ') }}
                  </span>
                </div>

                <!-- Action Button -->
                <Link
                  :href="route('booking.show', { locale: usePage().props.locale, id: booking.id })"
                  class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#153B4F] text-white rounded-lg font-medium hover:bg-[#0f2a38] transition-colors shadow-md hover:shadow-lg"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  {{ _t('customerbooking', 'view_details') }}
                </Link>
              </div>

              <!-- Vehicle Specs -->
              <div v-if="booking.vehicle" class="flex items-center gap-4 text-gray-600 mb-6">
                <div class="flex items-center gap-2">
                  <img :src="carIcon" alt="" class="w-5 h-5 opacity-60" />
                  <span class="capitalize">{{ booking.vehicle.transmission }}</span>
                </div>
                <span class="text-gray-300">•</span>
                <span class="capitalize">{{ booking.vehicle.fuel }}</span>
                <span class="text-gray-300">•</span>
                <span>{{ booking.vehicle.seating_capacity }} {{ _t('customerbooking', 'seats_suffix') }}</span>
              </div>

              <!-- Pickup & Return Info -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 p-4 bg-gray-50 rounded-xl">
                <!-- Pickup -->
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-full bg-[#0099001A] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#009900]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ _t('customerbooking', 'pickup') }}</p>
                    <p class="font-medium text-gray-900">{{ booking.pickup_location }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                      {{ formatDate(booking.pickup_date) }} at {{ formatTime(booking.pickup_time) }}
                    </p>
                  </div>
                </div>

                <!-- Return -->
                <div class="flex items-start gap-3">
                  <div class="w-10 h-10 rounded-full bg-[#EE1D521A] flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-[#EE1D52]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                  </div>
                  <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{{ _t('customerbooking', 'return') }}</p>
                    <p class="font-medium text-gray-900">{{ booking.return_location }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                      {{ formatDate(booking.return_date) }} at {{ formatTime(booking.return_time) }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Footer: Price & Actions -->
              <div class="mt-auto pt-4 border-t border-gray-100">
                <div class="flex flex-wrap items-center justify-between gap-4">
                  <!-- Price -->
                  <div>
                    <p class="text-sm text-gray-500">{{ _t('customerbooking', 'total_amount') }}</p>
                    <p class="text-2xl font-bold text-[#153B4F]">
                      {{ getCurrencySymbol(getBookingCurrency(booking)) }}{{ formatNumber(getBookingAmount(booking, 'total_amount')) }}
                    </p>
                    <p v-if="getBookingAmount(booking, 'amount_paid') > 0" class="text-sm text-green-600 mt-1">
                      {{ getCurrencySymbol(getBookingCurrency(booking)) }}{{ formatNumber(getBookingAmount(booking, 'amount_paid')) }} {{ _t('customerbooking', 'paid') }}
                    </p>
                  </div>

                  <!-- Additional Actions -->
                  <div class="flex items-center gap-3">
                    <button
                      v-if="booking.payment_status === 'pending' && booking.booking_status === 'pending'"
                      @click="retryPayment(booking.id)"
                      :disabled="isLoading"
                      class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-lg font-medium hover:bg-amber-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                      </svg>
                      {{ _t('customerbooking', 'complete_payment') }}
                    </button>

                    <Link
                      v-if="booking.vehicle?.vendor_id"
                      :href="route('messages.index', { locale: usePage().props.locale, vendor_id: booking.vehicle.vendor_id })"
                      class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                      </svg>
                      Chat
                    </Link>

                    <button
                      v-if="canCancelGreenMotion(booking)"
                      @click="cancelGreenMotionBooking(booking)"
                      :disabled="isLoading"
                      class="inline-flex items-center gap-2 px-4 py-2 border border-rose-200 text-rose-700 rounded-lg font-medium hover:bg-rose-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      {{ _t('customerbooking', 'cancel_booking_button') || 'Cancel Booking' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="bookings?.last_page > 1" class="mt-8 flex justify-center">
        <Pagination
          :current-page="bookings.current_page"
          :total-pages="bookings.last_page"
          @page-change="handlePageChange"
        />
      </div>
    </div>
  </MyProfileLayout>
</template>

<style scoped>
.booking-card {
  animation: fadeUp 0.4s ease-out forwards;
  opacity: 0;
}

@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.booking-card:hover {
  transform: translateY(-4px);
}

/* Custom scrollbar for tabs */
.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
