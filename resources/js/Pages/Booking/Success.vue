<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { Vue3Lottie } from 'vue3-lottie';
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import paymentSuccessAnimation from '../../../assets/payment-successful.json';

const props = usePage().props;
const booking = props.booking || {};
const vehicle = props.vehicle || {};
const locale = props.locale || 'en';

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
</script>

<template>
  <AuthenticatedHeaderLayout />

  <div class="booking-success-page min-h-[80vh] flex items-center justify-center bg-gradient-to-br from-gray-50 to-emerald-50/30 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl w-full">

      <!-- Success Card -->
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

        <!-- Green header strip -->
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-8 text-center">
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
          <h2 class="text-2xl font-extrabold text-white tracking-tight">Booking Confirmed!</h2>
          <p class="mt-1.5 text-emerald-100 text-sm">Your reservation is all set. Have a great trip!</p>
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

          <!-- Trip Details -->
          <div class="grid grid-cols-2 gap-3">
            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
              <div class="flex items-center gap-1.5 mb-1.5">
                <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Pick-up</span>
              </div>
              <p class="text-sm font-semibold text-gray-900">{{ formatDate(booking.pickup_date) }}</p>
              <p class="text-xs text-gray-500">{{ formatTime(booking.pickup_time) }}</p>
              <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ booking.pickup_location }}</p>
            </div>
            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
              <div class="flex items-center gap-1.5 mb-1.5">
                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Return</span>
              </div>
              <p class="text-sm font-semibold text-gray-900">{{ formatDate(booking.return_date) }}</p>
              <p class="text-xs text-gray-500">{{ formatTime(booking.return_time) }}</p>
              <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ booking.return_location || booking.pickup_location }}</p>
            </div>
          </div>

          <!-- Payment Summary -->
          <div class="flex items-center justify-between py-3 px-4 rounded-xl bg-[#153B4F]/5 border border-[#153B4F]/10">
            <div>
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Amount Paid</p>
              <p class="text-lg font-extrabold text-emerald-600">{{ booking.booking_currency }} {{ parseFloat(booking.amount_paid || 0).toFixed(2) }}</p>
            </div>
            <div class="text-right">
              <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status</p>
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Confirmed
              </span>
            </div>
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

          <!-- Due at pickup notice -->
          <div v-if="booking.pending_amount > 0" class="flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 border border-amber-200">
            <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
            <p class="text-xs text-amber-800 leading-relaxed">
              <strong>{{ booking.booking_currency }} {{ parseFloat(booking.pending_amount).toFixed(2) }}</strong> remaining to be paid at pickup.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="flex gap-3 pt-1">
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

        </div>
      </div>
    </div>
  </div>

  <Footer />
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap');

.booking-success-page {
  font-family: 'DM Sans', sans-serif;
}

.booking-success-page :deep(h1),
.booking-success-page :deep(h2),
.booking-success-page :deep(h3),
.booking-success-page :deep(.font-extrabold),
.booking-success-page :deep(.font-bold) {
  font-family: 'Outfit', sans-serif;
}

.success-lottie-wrap {
  min-height: 74px;
}
</style>
