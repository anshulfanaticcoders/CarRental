<template>
    <MyProfileLayout>
      <div class="container mx-auto px-4 max-[768px]:px-0">
        <p
          class="text-[1.5rem] max-[768px]:text-[1.2rem] text-customPrimaryColor font-bold mb-[2rem] bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem]">
          {{ _t('customerbooking', 'pending_bookings_header') }}</p>
  
          <div v-if="!bookings.data || bookings.data.length === 0" class="text-center text-gray-500">
          <div class="flex flex-col justify-center items-center">
            <img :src="bookingstatusIcon" alt="" class="w-[30rem] max-[768px]:w-full">
            <p>{{ _t('customerbooking', 'no_pending_bookings_found') }}</p>
          </div>
        </div>
  
        <div v-else class="flex flex-col gap-10">
          <div v-for="booking in bookings.data" :key="booking.id"
            class="bg-white shadow-md rounded-lg p-6 gap-10 flex justify-between max-[768px]:flex-col">
            <Link :href="`/vehicle/${booking.vehicle.id}`" class="w-[30%] max-[768px]:w-full">
              <div class="">
                <img v-if="booking.vehicle?.images" :src="`${booking.vehicle.images.find(
                  (image) => image.image_type === 'primary'
                )?.image_url}`" alt="Primary Vehicle Image"
                  class="w-full h-[250px] object-cover rounded-md" />
              </div>
            </Link>
            <div class="w-[67%] flex flex-col gap-5 max-[768px]:w-full">
              <div class="flex justify-between items-center max-[768px]:flex-wrap max-[768px]:gap-4">
                <div class="flex justify-between items-center gap-10 max-[768px]:gap-5"><span
                    class="text-[2rem] font-medium text-customPrimaryColor max-[768px]:text-[1.2rem]">{{ booking.vehicle.brand
                    }}</span> <span class="bg-customLightPrimaryColor p-3 rounded-[99px] text-[1rem] max-[768px]:text-[0.5rem]">{{
                      booking.vehicle?.category.name
                    }}</span>
                    </div>
                <span class="bg-[#906F001A] text-[#906F00] px-[1.5rem] py-[0.75rem] rounded-[99px] max-[768px]:text-[0.75rem]">{{ _t('customerbooking', 'booking_under_progress_status') }}</span>
              </div>
  
              <div class="flex items-end gap-2 max-[768px]:text-[0.875rem]">
                <img :src="carIcon" alt="">
                <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.transmission }}
                  .</span>
                <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.fuel }} .</span>
                <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.seating_capacity }}
                  {{ _t('customerbooking', 'seats_suffix') }}</span>
              </div>
  
              <div class="flex justify-between w-[70%] max-[768px]:w-full max-[768px]:flex-col max-[768px]:gap-5">
                <div class="col">
                  <div>
                    <strong>{{ _t('customerbooking', 'from_label') }}</strong>
                    <span class="ml-2">{{ booking.pickup_location }}</span>
                  </div>
                  <div class="flex gap-2">
                    <span class="text-customLightGrayColor">{{ formatDate(booking.pickup_date) }}</span>
                    <span class="text-customLightGrayColor">{{ booking.pickup_time }}</span>
                  </div>
                </div>
                <div class="col">
                  <div>
                    <strong>{{ _t('customerbooking', 'to_label') }}</strong>
                    <span class="ml-2">{{ booking.pickup_location }}</span>
                  </div>
                  <div class="flex gap-2">
                    <span class="text-customLightGrayColor">{{ formatDate(booking.return_date) }}</span>
                    <span class="text-customLightGrayColor">{{ booking.return_time }}</span>
                  </div>
                </div>
              </div>
              <div>
                <div>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'day'">{{
                    formatPrice(booking.vehicle.price_per_day, booking.vehicle) }}{{_t('customerbooking', 'price_per_day_suffix')}}</strong>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'week'">{{
                    formatPrice(booking.vehicle.price_per_week, booking.vehicle) }}{{_t('customerbooking', 'price_per_week_suffix')}}</strong>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'month'">{{
                    formatPrice(booking.vehicle.price_per_month, booking.vehicle) }}{{_t('customerbooking', 'price_per_month_suffix')}}</strong>
                </div>
                <div class="flex items-center justify-between">
                  <Link :href="`/booking-success?payment_intent=${booking.payments[0]?.transaction_id}`" class="underline">{{ _t('customerbooking', 'view_booking_details_link') }}</Link>
                <Link
                  v-if="booking.vehicle && booking.vehicle.vendor_id"
                  class="button-primary px-5 py-4 max-[768px]:text-[0.75rem] ml-4"
                  :href="`/messages?vendor_id=${booking.vehicle.vendor_id}`"
                >
                  {{ _t('customerbooking', 'chat_with_owner_link') }}
                </Link>
                </div>
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
    </MyProfileLayout>
  </template>
  
  <script setup>
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
  import carIcon from '../../../../assets/carIcon.svg';
  import { Link, usePage, router } from '@inertiajs/vue3';
   import Pagination from '@/Components/ReusableComponents/Pagination.vue';
  import { ref, getCurrentInstance } from 'vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
    bookings: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});
  
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  const handlePageChange = (page) => {
    router.get(route('profile.bookings.pending'), {
        ...props.filters,
        page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

  const formatPrice = (price, vehicle) => {
    const currencySymbol = vehicle?.vendor_profile?.currency ?? '$'; // Default to '$' if missing
    return `${currencySymbol}${price}`;
};
  </script>
