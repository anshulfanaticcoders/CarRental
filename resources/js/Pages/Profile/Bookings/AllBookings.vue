<script setup>
import { ref, computed, getCurrentInstance } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import { CalendarCheck } from 'lucide-vue-next';
import { getCurrencySymbol as registryCurrencySymbol } from '@/utils/currencyRegistry';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const page = usePage();

const t = (key, fallback) => {
  const value = _t('customerbooking', key);
  if (!value) return fallback;

  const normalizedValue = String(value).trim().toLowerCase().replace(/[\s-]+/g, '_');
  const normalizedKey = String(key).trim().toLowerCase();

  return normalizedValue === normalizedKey ? fallback : value;
};

const props = defineProps({
  bookings: Object,
});

const activeTab = ref('all');
const isLoading = ref(false);
const bookingRows = computed(() => props.bookings?.data || []);
const totalBookings = computed(() => props.bookings?.total || bookingRows.value.length);

// Status tabs configuration
const statusTabs = [
  { key: 'all', label: t('all_bookings', 'All Bookings') },
  { key: 'pending', label: t('pending', 'Pending') },
  { key: 'confirmed', label: t('confirmed', 'Confirmed') },
  { key: 'completed', label: t('completed', 'Completed') },
  { key: 'cancelled', label: t('cancelled', 'Cancelled') },
];

// Filter bookings by status (client-side)
const filteredBookings = computed(() => {
  if (!bookingRows.value.length) return [];

  if (activeTab.value === 'all') {
    return bookingRows.value;
  }

  return bookingRows.value.filter(booking =>
    booking.booking_status === activeTab.value
  );
});

const getTabCount = (tabKey) => {
  if (tabKey === 'all') return totalBookings.value;

  return bookingRows.value.filter((booking) => booking.booking_status === tabKey).length;
};

// Get status badge styling
const getStatusBadge = (status) => {
  const normalizedStatus = (status || 'pending').toLowerCase();
  const statusConfig = {
    pending: {
      label: t('pending', 'Pending'),
      tone: 'pending',
    },
    confirmed: {
      label: t('confirmed', 'Confirmed'),
      tone: 'confirmed',
    },
    active: {
      label: t('active', 'Active'),
      tone: 'confirmed',
    },
    completed: {
      label: t('completed', 'Completed'),
      tone: 'completed',
    },
    cancelled: {
      label: t('cancelled', 'Cancelled'),
      tone: 'cancelled',
    }
  };

  return statusConfig[normalizedStatus] || {
    label: normalizedStatus.replace(/_/g, ' '),
    tone: 'pending',
  };
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
    xdrive: { bg: 'bg-amber-50', text: 'text-amber-700' },
    favrica: { bg: 'bg-amber-50', text: 'text-amber-700' },
    internal: { bg: 'bg-sky-50', text: 'text-sky-700' }
  };

  return providerConfig[provider.toLowerCase()] || { bg: 'bg-gray-100', text: 'text-gray-700' };
};

const formatDate = (dateString) => {
  if (!dateString) return t('date_pending', 'Date pending');

  const date = new Date(dateString);
  if (Number.isNaN(date.getTime())) return dateString;

  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const formatTime = (timeString) => {
  if (!timeString || typeof timeString !== 'string') return t('time_pending', 'Time pending');

  const [hours, minutes] = timeString.split(':');
  const hour = parseInt(hours);
  if (Number.isNaN(hour) || !minutes) return timeString;

  const ampm = hour >= 12 ? 'PM' : 'AM';
  const formattedHour = hour % 12 || 12;
  return `${formattedHour}:${minutes} ${ampm}`;
};

const getCurrencySymbol = (currency) => registryCurrencySymbol(currency);

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

const formatProviderName = (provider) => {
  if (!provider) return t('internal_provider', 'Internal fleet');

  return provider
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (letter) => letter.toUpperCase());
};

const getVehicleName = (booking) => {
  const vehicleName = [booking.vehicle?.brand, booking.vehicle?.model].filter(Boolean).join(' ').trim();
  return vehicleName || booking.vehicle_name || t('vehicle', 'Vehicle');
};

const getVehicleMeta = (booking) => {
  const vehicle = booking.vehicle || {};

  return [
    vehicle.transmission,
    vehicle.fuel,
    vehicle.seating_capacity ? `${vehicle.seating_capacity} ${t('seats_suffix', 'Seats')}` : null,
  ].filter(Boolean);
};

const getCategoryLabel = (booking) => {
  return booking.vehicle?.category?.name || formatProviderName(booking.provider_source);
};

const hasFreeEsim = (booking) => {
  if (booking.free_esim_included || booking.provider_metadata?.free_esim_included) return true;
  if (!Array.isArray(booking.offers)) return false;

  return booking.offers.some((offer) => offer?.effect_type === 'free_esim');
};

const getPaymentStateLabel = (booking) => {
  if (booking.payment_status === 'pending') {
    return t('payment_pending', 'Payment pending');
  }

  if (getBookingAmount(booking, 'amount_paid') > 0) {
    return t('paid_online', 'Paid online');
  }

  return t('pay_at_pickup', 'Pay at pickup');
};

const getLocationLabel = (location, fallback) => {
  return location || fallback;
};

const isPaymentRetryVisible = (booking) => (
  booking.payment_status === 'pending' && booking.booking_status === 'pending'
);

const handleTabChange = (tab) => {
  activeTab.value = tab;
};

const handlePageChange = (pageNumber) => {
  isLoading.value = true;
  router.get(route('profile.bookings.all', { locale: page.props.locale }), { page: pageNumber }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { isLoading.value = false; }
  });
};

const retryPayment = async (bookingId) => {
  isLoading.value = true;
  try {
    const axios = (await import('axios')).default;
    const response = await axios.post(route('payment.retry', { locale: page.props.locale }), { booking_id: bookingId });

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

// Providers that support customer-initiated API cancellation
const CANCELLABLE_PROVIDERS = ['greenmotion', 'usave', 'favrica', 'xdrive', 'surprice', 'renteon', 'sicilybycar'];

// Providers where cancellation is manual (contact support)
const MANUAL_CANCEL_PROVIDERS = ['adobe', 'okmobility', 'locauto_rent', 'wheelsys', 'recordgo'];

const hasProviderCancelApi = (booking) => {
  const source = booking.provider_source?.toLowerCase();
  return CANCELLABLE_PROVIDERS.includes(source);
};

const isManualCancelProvider = (booking) => {
  const source = booking.provider_source?.toLowerCase();
  return MANUAL_CANCEL_PROVIDERS.includes(source);
};

const canCancelProviderBooking = (booking) => {
  if (['cancelled', 'completed'].includes(booking.booking_status)) return false;
  // Internal vehicles can be cancelled by customer
  if (!booking.provider_source) return true;
  // External providers with cancel API
  if (!hasProviderCancelApi(booking)) return false;
  if (!booking.provider_booking_ref) return false;
  return true;
};

const canShowContactSupport = (booking) => {
  if (['cancelled', 'completed'].includes(booking.booking_status)) return false;
  return isManualCancelProvider(booking);
};

const getCancellationDeadlineInfo = (booking) => {
  const metadata = booking.provider_metadata || {};
  const deadline = metadata.cancellation_deadline;
  if (!deadline) return null;
  const deadlineDate = new Date(deadline);
  const now = new Date();
  const isExpired = now > deadlineDate;
  return {
    deadline: deadlineDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }),
    isExpired
  };
};

const cancelProviderBooking = async (booking) => {
  // Check cancellation deadline
  const deadlineInfo = getCancellationDeadlineInfo(booking);
  if (deadlineInfo?.isExpired) {
    alert(`Free cancellation period expired on ${deadlineInfo.deadline}. Please contact support for assistance.`);
    return;
  }

  let confirmMsg = t('modal_confirm_cancellation_message', 'Are you sure you want to cancel this booking?');
  if (deadlineInfo) {
    confirmMsg += `\n\nFree cancellation available until: ${deadlineInfo.deadline}`;
  }
  if (!confirm(confirmMsg)) return;

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
    await axios.post(route('booking.cancel', { locale: page.props.locale }), {
      booking_id: booking.id,
      cancellation_reason: trimmedReason
    });
    router.reload({ preserveScroll: true });
  } catch (error) {
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
    <div v-if="isLoading" class="fixed inset-0 z-50 flex items-center justify-center bg-white/80 backdrop-blur-sm">
      <div class="flex flex-col items-center gap-4">
        <div class="w-12 h-12 border-4 border-[#153B4F] border-t-transparent rounded-full animate-spin"></div>
        <p class="text-gray-600 font-medium">{{ t('loading', 'Loading') }}</p>
      </div>
    </div>

    <div class="customer-bookings-page">
      <div class="vr-phead">
        <div>
          <span class="vr-eyebrow"><CalendarCheck /> {{ t('trip_ledger', 'Trip ledger') }}</span>
          <h2>{{ t('my_bookings', 'My Bookings') }}</h2>
          <p class="vr-sub">{{ t('bookings_subtitle', 'Manage pickup details, payments, included perks, and support actions from one clear place.') }}</p>
        </div>
        <div class="vr-phead-actions">
          <div class="bk-total-chip">
            <strong>{{ totalBookings }}</strong>
            <span>{{ t('total_rentals', 'total rentals') }}</span>
          </div>
        </div>
      </div>

      <div class="customer-bookings-tabs" role="tablist" :aria-label="t('booking_status_filters', 'Booking status filters')">
        <div class="status-tabs-scroll">
          <div class="status-tabs-row">
          <button
            v-for="tab in statusTabs"
            :key="tab.key"
            @click="handleTabChange(tab.key)"
            type="button"
            class="status-tab-btn"
            :class="{ 'is-active': activeTab === tab.key }"
            :aria-selected="activeTab === tab.key"
          >
            {{ tab.label }}
            <span>{{ getTabCount(tab.key) }}</span>
          </button>
          </div>
        </div>
      </div>

      <div v-if="filteredBookings.length === 0" class="customer-bookings-empty">
        <div class="flex flex-col items-center">
          <img :src="bookingstatusIcon" alt="No bookings" class="customer-bookings-empty__image" />
          <h3>
            {{ t('no_bookings_found', 'No bookings found') }}
          </h3>
          <p>
            {{ activeTab === 'all'
              ? t('no_bookings_yet', 'You haven\'t made any bookings yet.')
              : t('no_status_bookings', `No ${activeTab} bookings found.`)
            }}
          </p>
          <Link
            :href="route('search', { locale: page.props.locale })"
            class="customer-booking-btn customer-booking-btn--primary customer-booking-btn--inline"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            {{ t('search_vehicles', 'Search Vehicles') }}
          </Link>
        </div>
      </div>

      <section v-else class="customer-bookings-list">
        <article
          v-for="(booking, index) in filteredBookings"
          :key="booking.id"
          class="customer-booking-card"
          :style="{ animationDelay: `${getCardDelay(index)}ms` }"
        >
          <div class="customer-booking-media">
            <Link
              v-if="booking.vehicle"
              :href="route('vehicle.show', { locale: page.props.locale, id: booking.vehicle.id })"
              class="customer-booking-media__target"
            >
              <img
                v-if="getBookingImageUrl(booking)"
                :src="getBookingImageUrl(booking)"
                :alt="getVehicleName(booking)"
              />
              <div v-else class="customer-booking-media__placeholder">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 17h8m-10 0H5a2 2 0 0 1-2-2v-3l2.1-4.2A3 3 0 0 1 7.8 6h8.4a3 3 0 0 1 2.7 1.8L21 12v3a2 2 0 0 1-2 2h-1M7 17a2 2 0 1 0 4 0m2 0a2 2 0 1 0 4 0M5 12h14" />
                </svg>
              </div>
            </Link>
            <div v-else class="customer-booking-media__target">
              <img
                v-if="getBookingImageUrl(booking)"
                :src="getBookingImageUrl(booking)"
                :alt="getVehicleName(booking)"
              />
              <div v-else class="customer-booking-media__placeholder">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 17h8m-10 0H5a2 2 0 0 1-2-2v-3l2.1-4.2A3 3 0 0 1 7.8 6h8.4a3 3 0 0 1 2.7 1.8L21 12v3a2 2 0 0 1-2 2h-1M7 17a2 2 0 1 0 4 0m2 0a2 2 0 1 0 4 0M5 12h14" />
                </svg>
              </div>
            </div>
            <span class="customer-booking-status" :class="`customer-booking-status--${getStatusBadge(booking.booking_status).tone}`">
              <svg v-if="getStatusBadge(booking.booking_status).tone === 'confirmed'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M20 6 9 17l-5-5" />
              </svg>
              <svg v-else-if="getStatusBadge(booking.booking_status).tone === 'completed'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M20 6 9 17l-5-5" />
              </svg>
              <svg v-else-if="getStatusBadge(booking.booking_status).tone === 'cancelled'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M18 6 6 18M6 6l12 12" />
              </svg>
              <svg v-else fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9" stroke-width="2.4" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M12 7v5l3 2" />
              </svg>
              {{ getStatusBadge(booking.booking_status).label }}
            </span>
          </div>

          <div class="customer-booking-main">
            <div class="customer-booking-title-row">
              <div>
                <h2>{{ getVehicleName(booking) }}</h2>
                <p>{{ getCategoryLabel(booking) }}</p>
              </div>
              <span v-if="booking.booking_number" class="customer-booking-ref">{{ booking.booking_number }}</span>
            </div>

            <div class="customer-booking-chips">
              <span
                v-for="meta in getVehicleMeta(booking)"
                :key="meta"
                class="customer-booking-chip"
              >
                {{ meta }}
              </span>
              <span class="customer-booking-chip customer-booking-chip--vendor">
                {{ formatProviderName(booking.provider_source) }}
              </span>
              <span v-if="hasFreeEsim(booking)" class="customer-booking-chip customer-booking-chip--perk">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <rect x="7" y="2" width="10" height="20" rx="2" stroke-width="2" />
                  <path stroke-linecap="round" stroke-width="2" d="M11 18h2M10 6h4" />
                </svg>
                {{ t('free_esim_included', 'Free eSIM included') }}
              </span>
              <span class="customer-booking-chip">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1M12 15h1M6 19h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z" />
                </svg>
                {{ getPaymentStateLabel(booking) }}
              </span>
            </div>
          </div>

          <div class="customer-booking-trip">
            <div class="customer-booking-trip-point">
              <span>{{ t('pickup', 'Pickup') }}</span>
              <strong>{{ getLocationLabel(booking.pickup_location, t('pickup_location', 'Pickup location')) }}</strong>
              <p>{{ formatDate(booking.pickup_date) }} {{ t('at', 'at') }} {{ formatTime(booking.pickup_time) }}</p>
            </div>
            <div class="customer-booking-route-icon">
              <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M13 5l7 7-7 7" />
              </svg>
            </div>
            <div class="customer-booking-trip-point">
              <span>{{ t('return', 'Return') }}</span>
              <strong>{{ getLocationLabel(booking.return_location, t('return_location', 'Return location')) }}</strong>
              <p>{{ formatDate(booking.return_date) }} {{ t('at', 'at') }} {{ formatTime(booking.return_time) }}</p>
            </div>
          </div>

          <div class="customer-booking-money">
            <div>
              <span class="customer-booking-amount-label">{{ t('total_amount', 'Total amount') }}</span>
              <strong class="customer-booking-amount">
                {{ getCurrencySymbol(getBookingCurrency(booking)) }}{{ formatNumber(getBookingAmount(booking, 'total_amount')) }}
              </strong>
              <div class="customer-booking-split">
                <div>
                  <span>{{ t('paid_online', 'Paid online') }}</span>
                  <strong>{{ getCurrencySymbol(getBookingCurrency(booking)) }}{{ formatNumber(getBookingAmount(booking, 'amount_paid')) }}</strong>
                </div>
                <div>
                  <span>{{ t('due_at_pickup', 'Due at pickup') }}</span>
                  <strong>{{ getCurrencySymbol(getBookingCurrency(booking)) }}{{ formatNumber(getBookingAmount(booking, 'pending_amount')) }}</strong>
                </div>
              </div>
            </div>

            <div class="customer-booking-actions">
              <button
                v-if="isPaymentRetryVisible(booking)"
                @click="retryPayment(booking.id)"
                :disabled="isLoading"
                type="button"
                class="customer-booking-btn customer-booking-btn--primary"
              >
                {{ t('complete_payment', 'Complete payment') }}
              </button>
              <Link
                :href="route('booking.show', { locale: page.props.locale, id: booking.id })"
                class="customer-booking-btn"
                :class="isPaymentRetryVisible(booking) ? 'customer-booking-btn--secondary' : 'customer-booking-btn--primary'"
              >
                {{ t('view_details', 'View details') }}
              </Link>
              <Link
                v-if="booking.vehicle?.vendor_id"
                :href="route('messages.index', { locale: page.props.locale, vendor_id: booking.vehicle.vendor_id })"
                class="customer-booking-btn customer-booking-btn--secondary"
              >
                {{ t('message_vendor', 'Message vendor') }}
              </Link>
              <button
                v-if="canCancelProviderBooking(booking)"
                @click="cancelProviderBooking(booking)"
                :disabled="isLoading"
                type="button"
                class="customer-booking-btn customer-booking-btn--danger"
              >
                {{ t('cancel_booking_button', 'Cancel Booking') }}
              </button>
              <a
                v-if="canShowContactSupport(booking)"
                :href="`mailto:${page.props.adminEmail || 'support@vrooem.com'}?subject=Cancel Booking ${booking.booking_number}`"
                class="customer-booking-btn customer-booking-btn--secondary"
              >
                {{ t('contact_support_to_cancel', 'Contact support') }}
              </a>
            </div>

            <p v-if="getCancellationDeadlineInfo(booking) && !['cancelled', 'completed'].includes(booking.booking_status)" class="customer-booking-deadline">
              <template v-if="!getCancellationDeadlineInfo(booking).isExpired">
                {{ t('free_cancellation_until', 'Free cancellation until') }} {{ getCancellationDeadlineInfo(booking).deadline }}
              </template>
              <template v-else>
                {{ t('free_cancellation_expired', 'Free cancellation expired') }} {{ getCancellationDeadlineInfo(booking).deadline }}
              </template>
            </p>
          </div>
        </article>
      </section>

      <div v-if="bookings?.last_page > 1" class="customer-bookings-pagination">
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
.customer-bookings-page {
  --booking-brand: #153b4f;
  --booking-brand-dark: #0f2936;
  --booking-cyan: #22d3ee;
  --booking-cyan-dark: #0891b2;
  --booking-line: #dce8ef;
  --booking-muted: #64748b;
  width: 100%;
}

.bk-total-chip {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-width: 100px;
  padding: 12px 20px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  box-shadow: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
}

.bk-total-chip strong {
  font-family: "Plus Jakarta Sans", sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

.bk-total-chip span {
  font-size: 0.72rem;
  color: #64748b;
  margin-top: 4px;
}

.customer-bookings-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 1rem;
  align-items: end;
  margin-bottom: 1rem;
  padding: 1.35rem;
  border: 1px solid rgba(21, 59, 79, 0.12);
  border-radius: 1.5rem;
  background:
    radial-gradient(circle at 12% 8%, rgba(34, 211, 238, 0.14), transparent 18rem),
    rgba(255, 255, 255, 0.92);
  box-shadow: 0 16px 36px rgba(21, 59, 79, 0.09);
}

.customer-bookings-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.4rem;
  color: var(--booking-cyan-dark);
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.customer-bookings-eyebrow::before {
  content: "";
  width: 1.15rem;
  height: 2px;
  border-radius: 999px;
  background: currentColor;
}

.customer-bookings-hero h1 {
  margin: 0;
  color: var(--booking-brand-dark);
  font-size: 2rem;
  font-weight: 800;
  line-height: 1.12;
  letter-spacing: 0;
}

.customer-bookings-hero p {
  max-width: 62ch;
  margin: 0.55rem 0 0;
  color: var(--booking-muted);
  line-height: 1.6;
}

.customer-bookings-total {
  min-width: 8.5rem;
  padding: 0.9rem 1rem;
  border: 1px solid var(--booking-line);
  border-radius: 1rem;
  background: linear-gradient(180deg, #ffffff, #f7fbfd);
  text-align: center;
}

.customer-bookings-total strong {
  display: block;
  color: var(--booking-brand);
  font-size: 1.8rem;
  line-height: 1;
}

.customer-bookings-total span {
  display: block;
  margin-top: 0.3rem;
  color: var(--booking-muted);
  font-size: 0.76rem;
  font-weight: 800;
}

.customer-bookings-tabs {
  margin-bottom: 1rem;
}

.status-tabs-scroll {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding: 0.25rem;
  border: 1px solid rgba(21, 59, 79, 0.12);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.88);
  box-shadow: 0 8px 24px rgba(21, 59, 79, 0.07);
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
}

.status-tabs-row {
  display: inline-flex;
  gap: 0.45rem;
  min-width: max-content;
}

.status-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: 2.45rem;
  padding: 0 0.9rem;
  border: 0;
  border-radius: 999px;
  color: #425b6e;
  background: transparent;
  font-size: 0.87rem;
  font-weight: 800;
  white-space: nowrap;
  transition: color 160ms ease, background 160ms ease, box-shadow 160ms ease;
}

.status-tab-btn span {
  display: inline-grid;
  place-items: center;
  min-width: 1.45rem;
  height: 1.45rem;
  padding: 0 0.4rem;
  border-radius: 999px;
  color: var(--booking-brand);
  background: #e9f5fa;
  font-size: 0.72rem;
}

.status-tab-btn.is-active {
  color: white;
  background: linear-gradient(135deg, var(--booking-brand), #245f7b);
  box-shadow: 0 12px 28px rgba(21, 59, 79, 0.18);
}

.status-tab-btn.is-active span {
  color: var(--booking-brand);
  background: white;
}

.customer-bookings-list {
  display: grid;
  gap: 0.9rem;
}

.customer-booking-card {
  display: grid;
  grid-template-columns: 190px minmax(0, 1fr) 270px;
  grid-template-areas:
    "image main money"
    "image trip money";
  gap: 0.9rem 1.1rem;
  align-items: stretch;
  padding: 1rem;
  border: 1px solid rgba(21, 59, 79, 0.12);
  border-radius: 1.55rem;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 10px 28px rgba(21, 59, 79, 0.08);
  opacity: 0;
  animation: bookingFadeUp 0.32s ease-out forwards;
  transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
}

.customer-booking-card:hover {
  transform: translateY(-2px);
  border-color: rgba(34, 211, 238, 0.5);
  box-shadow: 0 18px 42px rgba(21, 59, 79, 0.13);
}

.customer-booking-media {
  grid-area: image;
  position: relative;
  overflow: hidden;
  min-height: 224px;
  border-radius: 1.2rem;
  background: #dce8ef;
}

.customer-booking-media__target {
  display: block;
  width: 100%;
  height: 100%;
  min-height: inherit;
}

.customer-booking-media img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.customer-booking-media::after {
  content: "";
  position: absolute;
  inset: 0;
  pointer-events: none;
  background: linear-gradient(180deg, rgba(15, 41, 54, 0.12), transparent 42%, rgba(15, 41, 54, 0.18));
}

.customer-booking-media__placeholder {
  display: grid;
  place-items: center;
  width: 100%;
  height: 100%;
  min-height: inherit;
  color: #9ab0be;
  background: linear-gradient(135deg, #eef5f8, #dce8ef);
}

.customer-booking-media__placeholder svg {
  width: 4.25rem;
  height: 4.25rem;
}

.customer-booking-status {
  position: absolute;
  top: 0.7rem;
  left: 0.7rem;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  max-width: calc(100% - 1.4rem);
  padding: 0.4rem 0.6rem;
  border-radius: 999px;
  color: #0f766e;
  background: rgba(255, 255, 255, 0.95);
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.14);
  font-size: 0.76rem;
  font-weight: 900;
  text-transform: capitalize;
}

.customer-booking-status svg {
  width: 0.9rem;
  height: 0.9rem;
  flex: 0 0 auto;
}

.customer-booking-status--pending {
  color: #b45309;
}

.customer-booking-status--confirmed {
  color: #047857;
}

.customer-booking-status--completed {
  color: #1d4ed8;
}

.customer-booking-status--cancelled {
  color: #be123c;
}

.customer-booking-main {
  grid-area: main;
  min-width: 0;
  padding-top: 0.1rem;
}

.customer-booking-title-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.8rem;
  margin-bottom: 0.75rem;
}

.customer-booking-title-row h2 {
  margin: 0;
  color: var(--booking-brand-dark);
  font-size: 1.22rem;
  font-weight: 850;
  line-height: 1.25;
  letter-spacing: 0;
  overflow-wrap: anywhere;
}

.customer-booking-title-row p {
  margin: 0.25rem 0 0;
  color: var(--booking-muted);
  font-size: 0.84rem;
  text-transform: capitalize;
}

.customer-booking-ref {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  min-height: 1.75rem;
  padding: 0 0.65rem;
  border: 1px solid var(--booking-line);
  border-radius: 999px;
  color: var(--booking-brand);
  background: #f7fbfd;
  font-size: 0.72rem;
  font-weight: 900;
}

.customer-booking-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.customer-booking-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 1.95rem;
  padding: 0 0.65rem;
  border: 1px solid var(--booking-line);
  border-radius: 999px;
  color: #365468;
  background: #f8fbfd;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: capitalize;
}

.customer-booking-chip svg {
  width: 0.9rem;
  height: 0.9rem;
  color: var(--booking-brand);
}

.customer-booking-chip--vendor {
  color: #334155;
  background: #f1f5f9;
}

.customer-booking-chip--perk {
  border-color: rgba(34, 211, 238, 0.48);
  color: #0e6377;
  background: #ecfeff;
}

.customer-booking-trip {
  grid-area: trip;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 40px minmax(0, 1fr);
  align-items: center;
  gap: 0.65rem;
  padding: 0.6rem;
  border: 1px solid var(--booking-line);
  border-radius: 1.1rem;
  background:
    linear-gradient(90deg, rgba(16, 185, 129, 0.06), transparent 44%, transparent 56%, rgba(244, 63, 94, 0.06)),
    linear-gradient(180deg, #ffffff, #f8fbfd);
}

.customer-booking-trip-point {
  min-width: 0;
  padding: 0.75rem;
  border: 1px solid rgba(21, 59, 79, 0.1);
  border-radius: 0.95rem;
  background: rgba(255, 255, 255, 0.84);
}

.customer-booking-trip-point span {
  display: block;
  margin-bottom: 0.32rem;
  color: var(--booking-cyan-dark);
  font-size: 0.64rem;
  font-weight: 900;
  letter-spacing: 0.1em;
  text-transform: uppercase;
}

.customer-booking-trip-point strong {
  display: -webkit-box;
  color: var(--booking-brand-dark);
  font-size: 0.9rem;
  line-height: 1.35;
  overflow: hidden;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.customer-booking-trip-point p {
  display: -webkit-box;
  margin: 0.3rem 0 0;
  color: var(--booking-muted);
  font-size: 0.78rem;
  line-height: 1.45;
  overflow: hidden;
  overflow-wrap: anywhere;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
}

.customer-booking-route-icon {
  display: grid;
  place-items: center;
  color: var(--booking-cyan-dark);
}

.customer-booking-route-icon svg {
  width: 1.2rem;
  height: 1.2rem;
}

.customer-booking-money {
  grid-area: money;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  gap: 0.8rem;
  min-width: 0;
  padding: 0.9rem;
  border: 1px solid var(--booking-line);
  border-radius: 1.2rem;
  background: #f7fbfd;
}

.customer-booking-amount-label {
  display: block;
  color: var(--booking-muted);
  font-size: 0.74rem;
  font-weight: 900;
}

.customer-booking-amount {
  display: block;
  margin-top: 0.25rem;
  color: var(--booking-brand);
  font-size: 1.45rem;
  font-weight: 900;
  line-height: 1;
}

.customer-booking-split {
  display: grid;
  gap: 0.45rem;
  margin-top: 0.75rem;
  color: #425b6e;
  font-size: 0.78rem;
  font-weight: 750;
}

.customer-booking-split div {
  display: flex;
  justify-content: space-between;
  gap: 0.6rem;
}

.customer-booking-actions {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.5rem;
}

.customer-booking-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 2.75rem;
  padding: 0 1rem;
  border: 1px solid var(--booking-brand);
  border-radius: 0.9rem;
  color: white;
  background: linear-gradient(135deg, var(--booking-brand), #285f7a);
  box-shadow: 0 14px 24px rgba(21, 59, 79, 0.18);
  font-size: 0.88rem;
  font-weight: 900;
  text-align: center;
  text-decoration: none;
  transition: transform 160ms ease, box-shadow 160ms ease, background 160ms ease;
}

.customer-booking-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 18px 28px rgba(21, 59, 79, 0.2);
}

.customer-booking-btn:disabled {
  cursor: not-allowed;
  opacity: 0.55;
  transform: none;
}

.customer-booking-btn--secondary {
  color: var(--booking-brand);
  background: white;
  border-color: var(--booking-line);
  box-shadow: none;
}

.customer-booking-btn--danger {
  color: #be123c;
  background: #fff1f2;
  border-color: #fecdd3;
  box-shadow: none;
}

.customer-booking-btn--inline {
  display: inline-flex;
  width: auto;
}

.customer-booking-deadline {
  margin: 0;
  color: #047857;
  font-size: 0.76rem;
  line-height: 1.45;
}

.customer-bookings-empty {
  padding: 2rem 1rem;
  border: 1px dashed rgba(21, 59, 79, 0.22);
  border-radius: 1.4rem;
  background: rgba(255, 255, 255, 0.78);
  text-align: center;
}

.customer-bookings-empty__image {
  width: 9rem;
  height: 9rem;
  margin-bottom: 1rem;
  opacity: 0.55;
}

.customer-bookings-empty h3 {
  margin: 0 0 0.45rem;
  color: var(--booking-brand);
  font-size: 1.15rem;
  font-weight: 850;
}

.customer-bookings-empty p {
  max-width: 42rem;
  margin: 0 auto 1.1rem;
  color: var(--booking-muted);
}

.customer-bookings-pagination {
  display: flex;
  justify-content: center;
  margin-top: 1.5rem;
}

@keyframes bookingFadeUp {
  from {
    opacity: 0;
    transform: translateY(14px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 1180px) {
  .customer-booking-card {
    grid-template-columns: 180px minmax(0, 1fr);
    grid-template-areas:
      "image main"
      "image trip"
      "money money";
  }

  .customer-booking-money {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(220px, 0.55fr);
    align-items: start;
  }
}

@media (max-width: 860px) {
  .customer-bookings-page {
    padding-inline: 0.75rem;
  }

  .customer-bookings-hero {
    grid-template-columns: 1fr;
  }

  .customer-bookings-total {
    max-width: 12rem;
  }

  .customer-booking-card {
    grid-template-columns: 1fr;
    grid-template-areas:
      "image"
      "main"
      "trip"
      "money";
  }

  .customer-booking-media {
    min-height: 220px;
  }

  .customer-booking-money {
    display: flex;
  }
}

@media (max-width: 560px) {
  .customer-bookings-page {
    padding: 1.25rem 0 2rem;
  }

  .customer-bookings-hero,
  .customer-booking-card {
    border-radius: 1.1rem;
  }

  .customer-bookings-hero {
    padding: 1rem;
  }

  .customer-bookings-hero h1 {
    font-size: 1.55rem;
  }

  .customer-booking-title-row,
  .customer-booking-trip {
    grid-template-columns: 1fr;
  }

  .customer-booking-title-row {
    display: grid;
  }

  .customer-booking-route-icon {
    display: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  .customer-booking-card,
  .customer-booking-btn {
    animation-duration: 1ms;
    transition-duration: 1ms;
  }
}
</style>
