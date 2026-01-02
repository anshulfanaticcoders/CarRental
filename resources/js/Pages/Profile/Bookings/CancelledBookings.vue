<template>
  <MyProfileLayout>
    <div class="container mx-auto px-4 max-[768px]:px-0">

      <!-- Flash Message for Success -->
      <div v-if="$page.props.flash.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
        <span class="block sm:inline">{{ $page.props.flash.success }}</span>
        <button @click="$page.props.flash.success = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
          <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <title>{{ _t('customerbooking', 'flash_message_close_title') }}</title>
            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
          </svg>
        </button>
      </div>
      
      <!-- Flash Message for Error -->
      <div v-if="$page.props.flash.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
        <span class="block sm:inline">{{ $page.props.flash.error }}</span>
        <button @click="$page.props.flash.error = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
          <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
            <title>{{ _t('customerbooking', 'flash_message_close_title') }}</title>
            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
          </svg>
        </button>
      </div>
      <p
        class="text-[1.5rem] max-[768px]:text-[1.2rem] text-customPrimaryColor font-bold mb-[2rem] bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem]">
        {{ _t('customerbooking', 'cancelled_bookings_header') }}</p>

        <div v-if="!bookings || !bookings.data || bookings.data.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
          <img :src="bookingstatusIcon" alt="" class="w-[30rem] max-[768px]:w-full">
          <p>{{ _t('customerbooking', 'no_cancelled_bookings') }}</p>
        </div>
      </div>

      <div v-else class="flex flex-col gap-10">
        <div v-for="booking in bookings.data" :key="booking.id"
          class="bg-white shadow-md rounded-lg p-6 gap-10 flex justify-between mb-6 max-[768px]:flex-col">
          <!-- Vehicle Image Section -->
          <component 
            :is="booking.vehicle ? 'Link' : 'div'" 
            :href="booking.vehicle ? route('vehicle.show', { locale: usePage().props.locale, id: booking.vehicle.id }) : null" 
            class="w-[30%] max-[768px]:w-full"
          >
            <div class=""> 
              <!-- Internal Vehicle Image -->
              <img v-if="booking.vehicle?.images?.length"
                :src="`${booking.vehicle.images.find(image => image.image_type === 'primary')?.image_url || booking.vehicle.images[0]?.image_url}`"
                alt="Primary Vehicle Image" class="w-full h-[250px] object-cover rounded-md" 
              />
              <!-- External/Fallback Vehicle Image -->
              <img v-else-if="booking.vehicle_image"
                :src="booking.vehicle_image" 
                alt="Vehicle Image"
                class="w-full h-[250px] object-contain bg-gray-50 rounded-md p-4" 
              />
              <!-- Placeholder -->
              <img v-else
                src="/path/to/placeholder-image.jpg" alt="Placeholder Image"
                class="w-full h-[250px] object-cover rounded-md" 
              />
            </div>
          </component>
          <div class="w-[67%] flex flex-col gap-5 max-[768px]:w-full">
            <div class="flex justify-between items-center max-[768px]:flex-wrap">
              <div class="flex justify-between items-center gap-10 max-[768px]:gap-5">
                <span class="text-[2rem] font-medium text-customPrimaryColor max-[768px]:text-[1.2rem]">
                    {{ booking.vehicle?.brand || booking.vehicle_name || 'Vehicle' }}
                </span> 
                <span v-if="booking.vehicle?.category" class="bg-customLightPrimaryColor p-3 rounded-[99px] text-[1rem] max-[768px]:text-[0.5rem]">
                    {{ booking.vehicle.category.name }}
                </span>
                <span v-else-if="booking.provider_source" class="bg-gray-100 p-3 rounded-[99px] text-[1rem] capitalize">
                    {{ booking.provider_source }}
                </span>
              </div>
              <span class="bg-[#FF00001A] text-[#FF0000] px-[1.5rem] py-[0.75rem] rounded-[99px] max-[768px]:text-[0.75rem]">{{ _t('customerbooking', 'booking_status_cancelled') }}</span>
            </div>

            <!-- Specs -->
            <div v-if="booking.vehicle" class="flex items-end gap-2 max-[768px]:text-[0.875rem]">
              <img :src="carIcon" alt="Car Icon"> 
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.transmission }} .</span>
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.fuel }} .</span>
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.seating_capacity }} {{ _t('customerbooking', 'seats_suffix') }}</span>
            </div>

            <div class="flex justify-between w-[70%] max-[768px]:w-full max-[768px]:flex-col max-[768px]:gap-5">
              <div class="col">
                <div>
                  <strong>{{ _t('customerbooking', 'from_label') }}</strong>
                  <span class="ml-2">{{ booking.pickup_location }}</span>
                </div>
                <div class="flex gap-2">
                  <span class="text-red-600 font-bold">Booked on:- {{ formatDate(booking.created_at) }}</span>
                </div>
              </div>
              <div class="col">
                <div>
                  <strong>{{ _t('customerbooking', 'to_label') }}</strong>
                  <span class="ml-2">{{ booking.return_location }}</span>
                </div>
              </div>
            </div>
            <div>
                 <template v-if="booking.vehicle">
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'day'">{{
                    formatPrice(booking.vehicle.price_per_day, booking.vehicle) }}{{_t('customerbooking', 'price_per_day_suffix')}}</strong>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'week'">{{
                    formatPrice(booking.vehicle.price_per_week, booking.vehicle) }}{{_t('customerbooking', 'price_per_week_suffix')}}</strong>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'month'">{{
                    formatPrice(booking.vehicle.price_per_month, booking.vehicle) }}{{_t('customerbooking', 'price_per_month_suffix')}}</strong>
                 </template>
                 <template v-else>
                     <strong class="text-[1.5rem] font-medium">
                         {{ booking.vendor_profile?.currency || '$' }}{{ booking.total_amount }}
                     </strong>
                 </template>
                </div>
            <div class="flex gap-4 max-[768px]:flex-col items-center justify-between">
            <!-- <div class="flex gap-5">
              <Link :href="route('booking.show', { locale: usePage().props.locale, id: booking.id })" class="underline">{{ _t('customerbooking', 'view_booking_details_link') }}</Link>
            </div> -->
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
        <h2 class="text-xl font-bold mb-4">{{ _t('customerbooking', 'modal_cancel_booking_title') }}</h2>
        <p class="mb-4">{{ _t('customerbooking', 'modal_confirm_cancellation_message') }}</p>
        
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2" for="cancellationReason">
            {{ _t('customerbooking', 'modal_cancellation_reason_label') }} <span class="text-red-500">*</span>
          </label>
          <textarea 
            id="cancellationReason" 
            v-model="cancellationReason" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
            rows="3"
            :placeholder="_t('customerbooking', 'modal_cancellation_reason_placeholder')"
          ></textarea>
          <p v-if="reasonError" class="text-red-500 text-xs mt-1">{{ reasonError }}</p> <!-- Assuming reasonError will also be translated if it's a fixed string -->
        </div>

        <div class="flex justify-end gap-2">
          <button 
            @click="closeModal" 
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded"
          >
            {{ _t('customerbooking', 'modal_button_cancel') }}
          </button>
          <button 
            @click="confirmCancellation" 
            class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? _t('customerbooking', 'modal_button_processing') : _t('customerbooking', 'modal_button_confirm_cancellation') }}
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
import { defineProps, ref, getCurrentInstance, computed } from 'vue';
import { Link,router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
    bookings: Object,
    filters: {
        type: Object,
        default: () => ({})
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
    reasonError.value = _t('customerbooking', 'modal_cancellation_reason_error');
    return;
  }
  
  isSubmitting.value = true;
  
  // Submit cancellation request to the server
  router.post(route('booking.cancel', { locale: usePage().props.locale }), {
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

const isCancellationAllowed = (booking) => {
  if (!booking.vehicle?.benefits?.cancellation_available_per_day_date) {
    return false; // Cancellation not available
  }

  const pickupDate = new Date(booking.pickup_date);
  const currentDate = new Date();
  const timeDiff = pickupDate.getTime() - currentDate.getTime();
  const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

  return dayDiff > booking.vehicle.benefits.cancellation_available_per_day_date;
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
    router.get(route('profile.bookings.cancelled', { locale: usePage().props.locale }), {
        page: page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};
</script>
