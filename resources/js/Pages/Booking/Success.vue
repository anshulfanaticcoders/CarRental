<script setup>
import { ref } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import AuthenticatedHeaderLayout from "@/Layouts/AuthenticatedHeaderLayout.vue";
import Footer from "@/Components/Footer.vue";
import BookingDetailsModal from "@/Components/BookingDetailsModal.vue";

const props = usePage().props;
const booking = props.booking || {};
const vehicle = props.vehicle || {};
const payment = props.payment || {};
const locale = props.locale || 'en';

const showDetailsModal = ref(false);
</script>

<template>
  <AuthenticatedHeaderLayout />
  
  <!-- Booking Details Modal -->
  <BookingDetailsModal 
    :show="showDetailsModal" 
    :booking="booking" 
    :vehicle="vehicle" 
    :payment="payment"
    @close="showDetailsModal = false"
  />

  <div class="min-h-[80vh] flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl w-full space-y-8 bg-white p-8 rounded-2xl shadow-xl transform transition-all">
      
      <!-- Robust Success Animation -->
      <div class="text-center">
        <div class="success-animation mb-6">
          <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
          </svg>
        </div>
        
        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">
          Payment Successful!
        </h2>
        <p class="mt-2 text-lg text-gray-600">
          Your booking has been confirmed and is ready to go.
        </p>
      </div>

      <!-- Booking Details Card -->
      <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
        <!-- Vehicle Info -->
        <div class="p-6 flex flex-col sm:flex-row items-center sm:items-start space-y-4 sm:space-y-0 sm:space-x-6 border-b border-gray-200">
          <div class="w-full sm:w-1/3 flex-shrink-0">
             <img 
               v-if="vehicle.image" 
               :src="vehicle.image" 
               alt="Vehicle" 
               class="w-full h-auto object-contain rounded-lg"
             />
             <div v-else class="w-full h-32 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
               No Image
             </div>
          </div>
          <div class="flex-1 text-center sm:text-left">
            <h3 class="text-xl font-bold text-gray-900">{{ vehicle.brand }} {{ vehicle.model }}</h3>
            <div class="mt-2 text-sm text-gray-600 space-y-1">
              <p><span class="font-medium">Pick-up:</span> {{ booking.pickup_location }}</p>
              <p>{{ booking.pickup_date }} at {{ booking.pickup_time }}</p>
              <p class="mt-2"><span class="font-medium">Drop-off:</span> {{ booking.return_location }}</p>
              <p>{{ booking.return_date }} at {{ booking.return_time }}</p>
            </div>
          </div>
        </div>

        <!-- Payment Breakdown -->
        <div class="bg-white p-6">
          <dl class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
            <div>
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Booking Ref</dt>
              <dd class="mt-1 text-lg font-bold text-customPrimaryColor">{{ booking.booking_number }}</dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</dt>
              <dd class="mt-1 text-lg font-bold text-green-600">
                {{ booking.payment?.amount ? booking.booking_currency + ' ' + booking.payment.amount : 'Paid' }}
              </dd>
            </div>
            <div>
              <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</dt>
              <dd class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                Confirmed
              </dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row gap-4 pt-4">
        <button 
          @click="showDetailsModal = true"
          class="flex-1 flex justify-center py-3.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-customPrimaryColor hover:bg-[#0f2a38] transition-all transform hover:-translate-y-0.5 cursor-pointer"
        >
          View Full Details & Download PDF
        </button>
        <Link 
          :href="`/${locale}/profile/bookings/pending`"
          class="flex-1 flex justify-center py-3.5 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all transform hover:-translate-y-0.5"
        >
          My Bookings
        </Link>
      </div>
    </div>
  </div>

  <Footer />
</template>

<style scoped>
.text-customPrimaryColor { color: #153B4F; }
.bg-customPrimaryColor { background-color: #153B4F; }

/* Robust Checkmark Animation */
.success-animation { margin: 0 auto; width: 100px; height: 100px; }

.checkmark {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: block;
  stroke-width: 2;
  stroke: #fff;
  stroke-miterlimit: 10;
  box-shadow: inset 0px 0px 0px #10B981;
  animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
}

.checkmark__circle {
  stroke-dasharray: 166;
  stroke-dashoffset: 166;
  stroke-width: 2;
  stroke-miterlimit: 10;
  stroke: #10B981;
  fill: none;
  animation: stroke .6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark__check {
  transform-origin: 50% 50%;
  stroke-dasharray: 48;
  stroke-dashoffset: 48;
  animation: stroke .3s cubic-bezier(0.65, 0, 0.45, 1) .8s forwards;
}

@keyframes stroke {
  100% { stroke-dashoffset: 0; }
}

@keyframes scale {
  0%, 100% { transform: none; }
  50% { transform: scale3d(1.1, 1.1, 1); }
}

@keyframes fill {
  100% { box-shadow: inset 0px 0px 0px 50px #10B981; }
}
</style>
