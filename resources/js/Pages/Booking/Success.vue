<script setup>
import { Link, usePage, router } from "@inertiajs/vue3";
import { computed, onMounted, onUnmounted } from "vue";
import { Vue3Lottie } from 'vue3-lottie';
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import BookingLocationBlock from "@/Components/Booking/BookingLocationBlock.vue";
import paymentSuccessAnimation from '../../../assets/payment-successful.json';
import { getCapturedPaymentReview } from '@/utils/bookingPaymentPresentation';
import { getCurrencyMinorUnit } from '@/utils/currencyRegistry';

const page = usePage();
const props = page.props;
const isAuthenticated = computed(() => Boolean(props.auth?.user));
const booking = props.booking || {};
// Reactive view of the booking — partial reloads (supplier-pending polling)
// replace page props, and the status branches must follow.
const liveBooking = computed(() => page.props.booking || booking);
const vehicle = props.vehicle || {};
const locale = props.locale || 'en';
const awinTestMode = ['1', 1, true, 'true'].includes(props.awin_test_mode) ? '1' : '0';
const awinEnabled = Boolean(props.awin_enabled);
const awinMerchantId = props.awin_advertiser_id || '';
// Must match the S2S job's commission base or Awin receives two different
// amounts for one ref.
const awinAmount = (b) => {
  const base = props.awin_commission_base || 'collected';
  const value = base === 'gross' ? b.total_amount
    : base === 'net' ? (b.provider_grand_total || b.total_amount)
    : b.amount_paid;
  return parseFloat(value || 0).toFixed(2);
};
// One conversion signal per booking, ever: the supplier-pending poll reloads
// this page every 20s and a refresh re-runs onMounted — without this guard
// each of those re-pushed a 'purchase' to the Awin tag.
const awinAlreadySignalled = (bookingNumber) => {
  const key = `awin_signalled_${bookingNumber}`;
  try {
    if (window.sessionStorage.getItem(key)) return true;
    window.sessionStorage.setItem(key, '1');
  } catch { /* storage unavailable — fall through and signal once */ }
  return false;
};

const customerSafeLocation = (value) => String(value || '').toLowerCase().includes('needs correction')
  ? 'Being confirmed — our team will contact you'
  : (value || '');
const pickupDetails = computed(() => liveBooking.value?.provider_metadata?.pickup_location_details || null);
const dropoffDetails = computed(() => liveBooking.value?.provider_metadata?.dropoff_location_details || null);
const paymentReview = computed(() => getCapturedPaymentReview(liveBooking.value, getCurrencyMinorUnit));
const capturedPaymentLabel = computed(() => paymentReview.value
  ? `${paymentReview.value.currency} ${paymentReview.value.amount.toFixed(paymentReview.value.minorUnit)}`
  : '');
const isFailedState = computed(() => ['cancelled', 'reservation_failed', 'rejected', 'expired'].includes(liveBooking.value?.booking_status));
const isSupplierPending = computed(() => {
  return !isFailedState.value
    && liveBooking.value?.provider_source
    && liveBooking.value.provider_source !== 'internal'
    && !liveBooking.value.provider_booking_ref;
});
const outcomeCopy = computed(() => {
  if (paymentReview.value) {
    return {
      title: 'Payment Under Review',
      subtitle: `Stripe captured ${capturedPaymentLabel.value}. Our team is checking the booking details and will contact you if anything is required.`,
      status: 'Under review',
      badgeClass: 'bg-amber-100 text-amber-700',
      dotClass: 'bg-amber-500',
    };
  }
  if (liveBooking.value?.booking_status === 'expired') {
    return {
      title: 'Checkout Expired',
      subtitle: 'This payment session expired before the booking was completed.',
      status: 'Expired',
      badgeClass: 'bg-slate-100 text-slate-700',
      dotClass: 'bg-slate-500',
    };
  }
  if (isFailedState.value) {
    return {
      title: 'Booking Under Review',
      subtitle: 'This booking could not be completed. Our team will contact you about next steps.',
      status: 'Under review',
      badgeClass: 'bg-rose-100 text-rose-700',
      dotClass: 'bg-rose-500',
    };
  }

  if (isSupplierPending.value) {
    return {
      title: 'Payment Received',
      subtitle: 'We are confirming your reservation with the supplier.',
      status: 'Supplier confirmation pending',
      badgeClass: 'bg-amber-100 text-amber-700',
      dotClass: 'bg-amber-500',
    };
  }

  return {
    title: 'Booking Confirmed!',
    subtitle: 'Your reservation is all set. Have a great trip!',
    status: 'Confirmed',
    badgeClass: 'bg-emerald-100 text-emerald-700',
    dotClass: 'bg-emerald-500',
  };
});

const successAnimationData = (() => {
  const cloned = JSON.parse(JSON.stringify(paymentSuccessAnimation));

  cloned.w = 960;
  cloned.h = 720;
  cloned.layers = (cloned.layers || [])
    .filter((layer) => layer?.nm !== 'Payment Successful')
    .map((layer) => {
      const point = layer?.ks?.p?.k;

      if (Array.isArray(point) && typeof point[0] === 'number' && typeof point[1] === 'number') {
        layer.ks.p.k = [point[0] - 480, point[1] - 180, point[2] ?? 0];
      }

      return layer;
    });

  return cloned;
})();

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const formatTime = (timeStr) => {
  if (!timeStr) return '';
  const [h, m] = timeStr.split(':');
  const hour = parseInt(h);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  return `${hour % 12 || 12}:${m} ${ampm}`;
};

const discountAmount = parseFloat(booking.discount_amount || 0);
const totalAmount = parseFloat(booking.total_amount || 0);
const discountPercentage = discountAmount > 0 && totalAmount > 0
  ? Math.round(discountAmount / (totalAmount + discountAmount) * 100)
  : 0;
const appliedOffers = Array.isArray(booking.offers) ? booking.offers : [];
const perkOffers = appliedOffers.filter((offer) => offer.effect_type !== 'price_discount_percentage');
const hasFreeEsim = perkOffers.some((offer) => offer.effect_type === 'free_esim');

// While the supplier reference is pending, refresh quietly so the page flips
// to Confirmed on its own (max 10 minutes; email covers the rest).
let supplierPollTimer = null;
const startSupplierPolling = () => {
  if (!isSupplierPending.value || supplierPollTimer) return;
  const startedAt = Date.now();
  supplierPollTimer = setInterval(() => {
    if (!isSupplierPending.value || Date.now() - startedAt > 10 * 60 * 1000) {
      clearInterval(supplierPollTimer);
      supplierPollTimer = null;
      return;
    }
    router.reload({ only: ['booking'] });
  }, 20000);
};
onUnmounted(() => { if (supplierPollTimer) clearInterval(supplierPollTimer); });

onMounted(() => {
  startSupplierPolling();
  // Never report a purchase conversion for a booking that could not be completed.
  if (awinEnabled && booking && booking.booking_number && !isFailedState.value && !paymentReview.value && !awinAlreadySignalled(booking.booking_number)) {
    const amount = awinAmount(booking);
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
      event: 'purchase',
      awinOrderRef: booking.booking_number,
      awinAmount: amount,
      awinCurrency: booking.booking_currency || 'EUR',
      awinVoucher: booking.discount_code || '',
      awinParts: 'DEFAULT:' + amount,
      awinTest: awinTestMode,
    });
  }
});
</script>

<template>
  <AuthenticatedHeaderLayout />

  <div class="booking-success-page min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl w-full">

      <!-- Success Card -->
      <div class="success-card bg-white rounded-2xl overflow-hidden">

        <!-- Header strip -->
        <div class="success-hero-strip px-6 py-8 text-center">
          <div class="mb-4 flex justify-center success-lottie-wrap">
            <Vue3Lottie
              class="success-lottie"
              :animation-data="successAnimationData"
              :height="132"
              :width="132"
              :scale="1.2"
              :no-margin="true"
              :loop="false"
            />
          </div>
          <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ outcomeCopy.title }}</h2>
          <p class="mt-1.5 text-emerald-100 text-sm">{{ outcomeCopy.subtitle }}</p>
        </div>

        <!-- Body -->
        <div class="p-6 space-y-5">

          <!-- Booking Reference -->
          <div class="text-center py-3 px-4 bg-gray-50 rounded-xl border border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Booking Reference</p>
            <p class="text-2xl font-extrabold text-[#153B4F] tracking-wide mt-1">{{ booking.booking_number }}</p>
          </div>

          <!-- Vehicle -->
          <div class="flex items-center gap-4">
            <div class="w-20 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
              <img v-if="vehicle.image" :src="vehicle.image" alt="" class="w-full h-full object-contain" />
              <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17h.01M16 17h.01M3 11l1.5-5A2 2 0 016.4 4h11.2a2 2 0 011.9 1.38L21 11M3 11v6a1 1 0 001 1h1m16-7v6a1 1 0 01-1 1h-1M3 11h18"/></svg>
              </div>
            </div>
            <div class="min-w-0">
              <p class="font-bold text-gray-900 text-base truncate">{{ booking.vehicle_name || (vehicle.brand + ' ' + vehicle.model) }}</p>
              <p class="text-xs text-gray-500">{{ booking.total_days }} day{{ booking.total_days > 1 ? 's' : '' }} rental</p>
            </div>
          </div>

          <!-- Trip Details (dates only — locations below) -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
              <div class="flex items-center gap-1.5 mb-1.5">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pick-up</span>
              </div>
              <p class="text-sm font-semibold text-gray-900">{{ formatDate(booking.pickup_date) }}</p>
              <p class="text-xs text-gray-500">{{ formatTime(booking.pickup_time) }}</p>
            </div>
            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
              <div class="flex items-center gap-1.5 mb-1.5">
                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Return</span>
              </div>
              <p class="text-sm font-semibold text-gray-900">{{ formatDate(booking.return_date) }}</p>
              <p class="text-xs text-gray-500">{{ formatTime(booking.return_time) }}</p>
            </div>
          </div>

          <BookingLocationBlock
            :pickup-string="customerSafeLocation(booking.pickup_location)"
            :return-string="customerSafeLocation(booking.return_location)"
            :pickup-details="pickupDetails"
            :dropoff-details="dropoffDetails"
            compact
          />

          <!-- Payment Summary -->
          <div class="flex items-center justify-between py-3 px-4 rounded-xl bg-[#153B4F]/5 border border-[#153B4F]/10">
            <div>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ paymentReview ? 'Captured — Under Review' : 'Amount Paid' }}</p>
              <p class="text-lg font-extrabold" :class="paymentReview ? 'text-amber-600' : 'text-emerald-600'">
                {{ paymentReview ? capturedPaymentLabel : `${booking.booking_currency} ${parseFloat(booking.amount_paid || 0).toFixed(2)}` }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</p>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold" :class="outcomeCopy.badgeClass">
                <span class="w-1.5 h-1.5 rounded-full" :class="outcomeCopy.dotClass"></span>
                {{ outcomeCopy.status }}
              </span>
            </div>
          </div>

          <div v-if="isSupplierPending" class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <p class="text-xs text-amber-800 leading-relaxed">
              Your payment is safe. The supplier reference is still being confirmed, and we will update your booking when it is ready.
            </p>
          </div>

          <!-- Discount savings notice -->
          <div v-if="discountAmount > 0" class="flex items-center gap-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200">
            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            <div>
              <p class="text-sm font-bold text-emerald-700">You saved {{ booking.booking_currency }} {{ discountAmount.toFixed(2) }}<span v-if="discountPercentage > 0"> ({{ discountPercentage }}% off)</span></p>
              <p class="text-xs text-emerald-600/70">Promotional discount applied to your booking</p>
            </div>
          </div>

          <div v-if="perkOffers.length > 0" class="rounded-xl border border-sky-200 bg-sky-50 p-3">
            <p class="text-sm font-bold text-sky-800">Included offers</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="offer in perkOffers"
                :key="offer.id || offer.effect_type"
                class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-sky-700 border border-sky-200"
              >
                {{ offer.title || offer.name }}
              </span>
            </div>
            <p v-if="hasFreeEsim" class="mt-2 text-xs text-sky-700/80">A free eSIM is attached to this booking offer.</p>
          </div>

          <!-- Due at pickup notice -->
          <div v-if="booking.pending_amount > 0" class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <p class="text-xs text-amber-800 leading-relaxed">
              <strong>{{ booking.booking_currency }} {{ parseFloat(booking.pending_amount).toFixed(2) }}</strong> remaining to be paid at pickup.
            </p>
          </div>

          <!-- Awin fallback tracking pixel -->
          <img
            v-if="awinEnabled && awinMerchantId && booking && booking.booking_number && !isFailedState"
            :src="`https://www.awin1.com/sread.img?tt=ns&tv=2&merchant=${encodeURIComponent(awinMerchantId)}&amount=${awinAmount(booking)}&ch=aw&parts=DEFAULT:${awinAmount(booking)}&ref=${encodeURIComponent(booking.booking_number)}&cr=${booking.booking_currency || 'EUR'}&vc=${encodeURIComponent(booking.discount_code || '')}&testmode=${awinTestMode}`"
            width="0"
            height="0"
            style="display: none;"
            alt=""
          />

          <!-- Action Buttons (details/list need a logged-in session — guests get
               a hint instead of a link that bounces them to a login wall) -->
          <div v-if="isAuthenticated" class="flex gap-3 pt-1">
            <Link
              :href="route('booking.show', { id: booking.id, locale })"
              class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-[#153B4F] hover:bg-[#0f2a38] transition-all shadow-md hover:shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              View Details
            </Link>
            <Link
              :href="route('profile.bookings.all', { locale })"
              class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-gray-700 bg-white border-2 border-gray-200 hover:border-gray-300 hover:bg-gray-50 transition-all"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              My Bookings
            </Link>
          </div>
          <div v-else class="pt-1">
            <div class="p-3 rounded-xl bg-[#153B4F]/5 border border-[#153B4F]/10 text-center">
              <p class="text-xs text-gray-600">
                An account was created for you — check <strong>{{ booking?.customer?.email || 'your email' }}</strong> for your login details to view and manage this booking.
              </p>
              <Link :href="route('login', { locale })" class="inline-block mt-2 text-sm font-bold text-[#153B4F] underline">Log in</Link>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <Footer />
</template>

<style scoped>
.booking-success-page {
  background: linear-gradient(180deg, var(--gray-50) 0%, var(--white) 52%, var(--gray-50) 100%);
  font-family: "IBM Plex Sans", serif;
}

.booking-success-page :deep(h1),
.booking-success-page :deep(h2),
.booking-success-page :deep(h3),
.booking-success-page :deep(.font-extrabold),
.booking-success-page :deep(.font-bold) {
  font-family: var(--jakarta-font-family);
}

.success-card {
  border: 1px solid rgba(21, 59, 79, 0.08);
  box-shadow: var(--shadow-xl);
}

.success-hero-strip {
  background:
    radial-gradient(circle at 18% 12%, rgba(34, 211, 238, 0.16), transparent 44%),
    linear-gradient(135deg, #0a1d28 0%, #153b4f 52%, #0c2535 100%);
}

.success-lottie-wrap {
  min-height: 74px;
}
</style>
