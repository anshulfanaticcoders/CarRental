<script setup>
import { computed } from 'vue';
import { X, Printer, MapPin, Calendar, Clock, Car, CreditCard } from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  booking: Object,
  vehicle: Object,
  payment: Object
});

const emit = defineEmits(['close']);

const downloadPdf = () => {
  window.print();
};

// Helper for currency extraction
const currencySymbol = computed(() => {
    const currency = props.booking?.booking_currency || 'USD';
    const symbols = {
        'USD': '$', 'EUR': '€', 'GBP': '£', 'AED': 'AED'
    };
    return symbols[currency] || currency;
});

const formatMoney = (amount) => {
    return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(amount || 0);
};

const formatDate = (dateStr) => {
    if(!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString(undefined, { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' });
};
</script>

<template>
  <Teleport to="body">
    <div v-if="show" id="booking-details-modal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal Panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full print:shadow-none print:w-full print:max-w-none print:my-0">
        
        <!-- Header -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-between items-center border-b print:hidden">
          <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
            Booking Details
          </h3>
          <button @click="$emit('close')" type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
            <span class="sr-only">Close</span>
            <X class="h-6 w-6" />
          </button>
        </div>

        <!-- Printable Content -->
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 print:p-0">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Vehicle Section -->
            <div class="col-span-1 md:col-span-2 flex flex-col sm:flex-row gap-4 items-center bg-gray-50 p-4 rounded-lg border print:border-gray-300">
               <div class="w-full sm:w-1/3">
                  <img v-if="vehicle.image" :src="vehicle.image" class="w-full h-32 object-contain bg-white rounded-md border" />
               </div>
               <div class="flex-1">
                 <h4 class="text-xl font-bold text-customPrimaryColor">{{ vehicle.brand }} {{ vehicle.model }}</h4>
                 <div class="mt-2 grid grid-cols-2 gap-2 text-sm text-gray-600">
                    <div class="flex items-center gap-1"><Car class="w-4 h-4" /> {{ vehicle.type || 'Car' }}</div>
                    <div class="flex items-center gap-1"><span class="font-semibold">Plan:</span> {{ booking.plan }}</div>
                 </div>
               </div>
            </div>

            <!-- Locations & Dates -->
            <div class="space-y-4">
                <div class="p-3 border rounded-lg print:border-gray-300">
                    <h5 class="font-semibold text-gray-900 flex items-center gap-2 mb-2">
                        <MapPin class="w-4 h-4 text-green-600" /> Pick-up
                    </h5>
                    <p class="text-sm text-gray-800 font-medium">{{ booking.pickup_location }}</p>
                    <p class="text-sm text-gray-500 flex items-center gap-1 mt-1">
                        <Calendar class="w-3 h-3" /> {{ formatDate(booking.pickup_date) }}
                        <Clock class="w-3 h-3 ml-2" /> {{ booking.pickup_time }}
                    </p>
                </div>
                <div class="p-3 border rounded-lg print:border-gray-300">
                    <h5 class="font-semibold text-gray-900 flex items-center gap-2 mb-2">
                        <MapPin class="w-4 h-4 text-red-600" /> Drop-off
                    </h5>
                    <p class="text-sm text-gray-800 font-medium">{{ booking.return_location }}</p>
                     <p class="text-sm text-gray-500 flex items-center gap-1 mt-1">
                        <Calendar class="w-3 h-3" /> {{ formatDate(booking.return_date) }}
                        <Clock class="w-3 h-3 ml-2" /> {{ booking.return_time }}
                    </p>
                </div>
            </div>

            <!-- Payment Breakdown -->
            <div>
                <div class="bg-gray-50 p-4 rounded-lg border print:border-gray-300 h-full">
                    <h5 class="font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <CreditCard class="w-4 h-4 text-customPrimaryColor" /> Payment Summary
                    </h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Booking Ref:</span>
                            <span class="font-bold">{{ booking.booking_number }}</span>
                        </div>
                        
                        <!-- Extras List (Print Optimized) -->
                        <div v-if="booking.extras && booking.extras.length > 0" class="py-2 border-y my-2">
                             <span class="text-gray-500 text-xs font-semibold uppercase">Selected Extras</span>
                             <div class="mt-1 space-y-1">
                                 <div v-for="extra in booking.extras" :key="extra.id" class="flex justify-between text-xs">
                                     <span class="text-gray-700">{{ extra.extra_name }} (x{{ extra.quantity }})</span>
                                     <span class="text-gray-900">{{ currencySymbol }}{{ formatMoney(extra.price) }}</span>
                                 </div>
                             </div>
                        </div>

                        <div class="flex justify-between mt-2">
                            <span class="text-gray-600">Total Amount:</span>
                            <span class="font-medium">{{ currencySymbol }}{{ formatMoney(booking.total_amount) }}</span>
                        </div>
                        <div class="flex justify-between pt-2 border-t">
                            <span class="text-gray-900 font-bold">Amount Paid:</span>
                            <span class="text-green-600 font-bold">{{ currencySymbol }}{{ formatMoney(booking.payment?.amount || booking.amount_paid) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pending Amount:</span>
                            <span class="text-gray-900 font-medium">{{ currencySymbol }}{{ formatMoney(booking.pending_amount) }}</span>
                        </div>
                         <div v-if="payment" class="mt-4 pt-2 border-t text-xs text-gray-500">
                            <p>Transaction ID: {{ payment.transaction_id }}</p>
                            <p>Paid via: {{ payment.payment_method }}</p>
                            <p>Date: {{ formatDate(payment.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse print:hidden">
          <button @click="downloadPdf" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-customPrimaryColor text-base font-medium text-white hover:bg-[#0f2a38] focus:outline-none sm:ml-3 sm:w-auto sm:text-sm items-center gap-2">
            <Printer class="w-4 h-4" /> Download PDF
          </button>
          <button @click="$emit('close')" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
            Close
          </button>
        </div>
      </div>
    </div>
    </div>
  </Teleport>
</template>

<style scoped>
.text-customPrimaryColor { color: #153B4F; }
.bg-customPrimaryColor { background-color: #153B4F; }

</style>

@media print {
  /* 1. Hide EVERYTHING except modal content */
  body > * {
    display: none !important;
  }

  #app {
    display: none !important;
  }

  /* 2. Only show the modal content directly */
  #booking-details-modal {
    display: block !important;
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: auto !important;
    background: white !important;
    z-index: 99999 !important;
  }

  /* 3. Hide ALL outer wrapper divs - show only the actual modal panel */
  #booking-details-modal > div {
    display: contents !important;
  }

  /* 4. Hide backdrop and helper elements */
  .fixed.inset-0:not(#booking-details-modal),
  .print\:hidden,
  span[aria-hidden="true"] {
    display: none !important;
  }

  /* 5. Show and style the actual modal panel */
  #booking-details-modal .inline-block.align-bottom {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    position: static !important;
    transform: none !important;
    box-shadow: none !important;
    border: none !important;
    margin: 0 !important;
    padding: 0 !important;
    background: white !important;
  }

  /* 6. Hide header and footer */
  #booking-details-modal .bg-gray-50,
  #booking-details-modal .print\:hidden {
    display: none !important;
  }

  /* 7. Content styling */
  #booking-details-modal .bg-white {
    padding: 10mm !important;
  }

  /* 8. Grid Layout Optimization */
  #booking-details-modal .grid.grid-cols-1 {
    display: grid !important;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    page-break-inside: avoid;
  }

  #booking-details-modal .grid > *:first-child {
      grid-column: 1 / -1;
      break-inside: avoid;
  }

  /* 9. Compact Vehicle Image */
  #booking-details-modal img {
    height: 6rem !important;
    object-fit: contain;
  }

  /* 10. Compact Text */
  #booking-details-modal h4 { font-size: 1.1rem !important; }
  #booking-details-modal h5 { font-size: 0.9rem !important; margin-bottom: 0.25rem !important; }
  #booking-details-modal p { font-size: 0.8rem !important; }

  #booking-details-modal .space-y-4 { grid-column: 1; }
  #booking-details-modal .space-y-4 + div { grid-column: 2; }

  /* 11. Force single page */
  @page {
    size: A4;
    margin: 10mm;
  }

  * {
    page-break-after: avoid !important;
    page-break-before: avoid !important;
  }
}
