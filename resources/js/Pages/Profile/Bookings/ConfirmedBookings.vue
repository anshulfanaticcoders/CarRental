<template>
  <MyProfileLayout>
    <div class="container mx-auto px-4 max-[768px]:px-0">

      <!-- Flash Message for Success -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        <button @click="$page.props.flash.success = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
          <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <title>Close</title>
            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
          </svg>
        </button>
      </div>
      
      <!-- Flash Message for Error -->
      <div v-if="$page.props.flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <span class="block sm:inline">{{ $page.props.flash.error }}</span>
        <button @click="$page.props.flash.error = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
          <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <title>Close</title>
            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
          </svg>
        </button>
      </div>
      <p
        class="text-[1.5rem] max-[768px]:text-[1.2rem] text-customPrimaryColor font-bold mb-[2rem] bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem]">
        Confirmed Bookings</p>

        <div v-if="!bookings.data || bookings.data.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
          <img :src="bookingstatusIcon" alt="" class="w-[30rem] max-[768px]:w-full">
          <p>No completed bookings found.</p>
        </div>
      </div>

      <div v-else>
        <div v-for="booking in bookings.data" :key="booking.id"
          class="bg-white shadow-md rounded-lg p-6 gap-10 flex justify-between mb-6 max-[768px]:flex-col">
          <Link :href="`/vehicle/${booking.vehicle.id}`">
          <div class="w-20% max-[768px]:w-full"> 
            <img v-if="booking.vehicle?.images"
              :src="`${booking.vehicle.images.find(image => image.image_type === 'primary')?.image_url}`"
              alt="Primary Vehicle Image" class="w-full h-[250px] object-cover rounded-md" /> <img v-else
              src="/path/to/placeholder-image.jpg" alt="Placeholder Image"
              class="w-full h-[250px] object-cover rounded-md" />
          </div>
          </Link>
          <div class="w-[67%] flex flex-col gap-5 max-[768px]:w-full">
            <div class="flex justify-between items-center max-[768px]:flex-wrap">
              <div class="flex justify-between items-center gap-10 max-[768px]:gap-5">
                <span class="text-[2rem] font-medium text-customPrimaryColor max-[768px]:text-[1.2rem]">{{ booking.vehicle.brand }}</span> <span
                  class="bg-customLightPrimaryColor p-3 rounded-[99px] text-[1rem] max-[768px]:text-[0.5rem]">{{ booking.vehicle?.category.name
                  }}</span>
              </div>
              <span class="bg-[#0099001A] text-[#009900] px-[1.5rem] py-[0.75rem] rounded-[99px] max-[768px]:text-[0.75rem]">Confirmed</span>
            </div>

            <div class="flex items-end gap-2 max-[768px]:text-[0.875rem]">
              <img :src="carIcon" alt="Car Icon"> <span class="capitalize text-customLightGrayColor">{{
                booking.vehicle.transmission }} .</span>
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.fuel }} .</span>
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.seating_capacity }} Seats</span>
            </div>

            <div class="flex justify-between w-[70%] max-[768px]:w-full max-[768px]:flex-col max-[768px]:gap-5">
              <div class="col">
                <div>
                  <strong>From:</strong>
                  <span class="ml-2">{{ booking.pickup_location }}</span>
                </div>
                <div class="flex gap-2">
                  <span class="text-customLightGrayColor">{{ formatDate(booking.pickup_date) }}</span>
                  <span class="text-customLightGrayColor">{{ booking.pickup_time }}</span>
                </div>
              </div>
              <div class="col">
                <div>
                  <strong>To:</strong>
                  <span class="ml-2">{{ booking.return_location }}</span>
                </div>
                <div class="flex gap-2">
                  <span class="text-customLightGrayColor">{{ formatDate(booking.return_date) }}</span>
                  <span class="text-customLightGrayColor">{{ booking.return_time }}</span>
                </div>
              </div>
            </div>
            <div>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium">{{ formatPrice(booking.total_amount) }}</span>
            </div>
            <div class="flex gap-4">
            <Link :href="`/booking-success/details?payment_intent=${booking.payments[0]?.transaction_id}`" class="underline">View Booking Details</Link>
            <button 
                @click="openCancellationModal(booking)" 
                class="text-red-600 underline"
              >
                Cancel Booking
              </button>
          </div>
          </div>
        </div>

         <!-- Pagination -->
         <div class="mt-4 flex justify-end">
            <Pagination 
              :current-page="bookings.current_page" 
              :total-pages="bookings.last_page" 
              @page-change="handlePageChange" 
            />
          </div>
      </div>
    </div>

    <!-- Cancellation Modal -->
    <div v-if="showCancellationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4">Cancel Booking</h2>
        <p class="mb-4">Are you sure you want to cancel this booking?</p>
        
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="cancellationReason">
            Cancellation Reason <span class="text-red-500">*</span>
          </label>
          <textarea 
            id="cancellationReason" 
            v-model="cancellationReason" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            rows="3"
            placeholder="Please provide a reason for cancellation"
          ></textarea>
          <p v-if="reasonError" class="text-red-500 text-xs mt-1">{{ reasonError }}</p>
        </div>

        <div class="flex justify-end gap-2">
          <button 
            @click="closeModal" 
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
          >
            Cancel
          </button>
          <button 
            @click="confirmCancellation" 
            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Processing...' : 'Yes, Cancel Booking' }}
          </button>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import carIcon from '../../../../assets/carIcon.svg'; // Import car icon
import { defineProps, ref } from 'vue';
import { Link,router } from '@inertiajs/vue3';
import Pagination from './Pagination.vue';

const props = defineProps({
  bookings: {
    type: Object,
    default: () => ({
      data: [],
      current_page: 1,
      last_page: 1,
    })
  }
});

// Cancellation related state
const showCancellationModal = ref(false);
const selectedBooking = ref(null);
const cancellationReason = ref('');
const reasonError = ref('');
const isSubmitting = ref(false);

const openCancellationModal = (booking) => {
  selectedBooking.value = booking;
  showCancellationModal.value = true;
  cancellationReason.value = '';
  reasonError.value = '';
};

const closeModal = () => {
  showCancellationModal.value = false;
  selectedBooking.value = null;
  cancellationReason.value = '';
  reasonError.value = '';
};

const confirmCancellation = () => {
  // Validate reason
  if (!cancellationReason.value.trim()) {
    reasonError.value = 'Please provide a reason for cancellation';
    return;
  }
  
  isSubmitting.value = true;
  
  // Submit cancellation request to the server
  router.post('/booking/cancel', {
    booking_id: selectedBooking.value.id,
    cancellation_reason: cancellationReason.value
  }, {
    onSuccess: () => {
      isSubmitting.value = false;
      closeModal();
      // Optionally show success notification
    },
    onError: (errors) => {
      isSubmitting.value = false;
      if (errors.cancellation_reason) {
        reasonError.value = errors.cancellation_reason;
      }
    }
  });
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatPrice = (price, vehicle) => {
    const currencySymbol = vehicle?.vendor_profile?.currency ?? '$'; // Default to '$' if missing
    return `${currencySymbol}${price}`;
};

// Handle pagination
const handlePageChange = (page) => {
  router.get('/profile/bookings/confirmedbookings', { page }, {
    preserveState: true,
    replace: true,
  });
};
</script>