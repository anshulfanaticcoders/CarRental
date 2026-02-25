<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";

const props = usePage().props;
const booking = props.booking || {};
const vehicle = props.vehicle || {};
const locale = props.locale || 'en';

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
</script>

<template>
  <AuthenticatedHeaderLayout />

  <div class="min-h-[80vh] flex items-center justify-center bg-gradient-to-br from-gray-50 to-emerald-50/30 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl w-full">

      <!-- Success Card -->
      <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

        <!-- Green header strip -->
        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 px-6 py-8 text-center">
          <div class="success-animation mb-4">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
              <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
              <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
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
/* Checkmark Animation */
.success-animation { margin: 0 auto; width: 80px; height: 80px; }

.checkmark {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: block;
  stroke-width: 2;
  stroke: #10b981;
  stroke-miterlimit: 10;
  box-shadow: inset 0px 0px 0px rgba(255,255,255,0.3);
  animation: checkmark-fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}

.checkmark__circle {
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  stroke-width: 2;
  stroke-miterlimit: 10;
  stroke: #10b981;
  fill: #fff;
  animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark__check {
  transform-origin: 50% 50%;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  stroke: #10b981;
  stroke-width: 3;
  animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
}

@keyframes stroke {
  100% { stroke-dashoffset: 0; }
}

@keyframes scale {
  0%, 100% { transform: none; }
  50% { transform: scale3d(1.1, 1.1, 1); }
}

@keyframes checkmark-fill {
  100% { box-shadow: inset 0px 0px 0px 50px rgba(255,255,255,0.2); }
}
</style>
