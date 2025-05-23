<template>
  <MyProfileLayout>
    <div class="container mx-auto px-4 max-[768px]:px-0">
      <p
        class="text-[1.5rem] max-[768px]:text-[1.2rem] text-customPrimaryColor font-bold mb-[2rem] bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem]">
        {{ _t('customerbooking', 'completed_bookings_header') }}</p>

        <div v-if="!bookings.data || bookings.data.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
          <img :src="bookingstatusIcon" alt="" class="w-[30rem] max-[768px]:w-full">
          <p>{{ _t('customerbooking', 'no_completed_bookings_found') }}</p>
        </div>
      </div>

      <div v-else class="flex flex-col gap-10">
        <div v-for="booking in bookings.data" :key="booking.id"
          class="bg-white shadow-md rounded-lg p-6 gap-10 flex justify-between mb-6 max-[768px]:flex-col">
          <Link :href="`/vehicle/${booking.vehicle.id}`" class="w-[30%] max-[768px]:w-full">
          <div class="">
            <img v-if="booking.vehicle?.images"
              :src="`${booking.vehicle.images.find(image => image.image_type === 'primary')?.image_url}`"
              :alt="_t('customerbooking', 'booked_vehicle_image_alt')"
              class="w-full h-[250px] object-cover rounded-md" />
            <img v-else src="/path/to/placeholder-image.jpg" :alt="_t('customerbooking', 'placeholder_image_alt')"
              class="w-full h-[250px] object-cover rounded-md" />
          </div>
          </Link>
          <div class="w-[67%] flex flex-col gap-5 max-[768px]:w-full">
            <div class="flex justify-between items-center max-[768px]:flex-wrap max-[768px]:gap-4">
              <div class="flex justify-between items-center gap-10 max-[768px]:gap-5">
                <span class="text-[2rem] font-medium text-customPrimaryColor max-[768px]:text-[1.2rem]">{{
                  booking.vehicle.brand }}</span>
                <span class="bg-customLightPrimaryColor p-3 rounded-[99px] text-[1rem] max-[768px]:text-[0.5rem]">{{
                  booking.vehicle?.category?.name }}</span>
              </div>
              <span
                class="bg-[#0099001A] text-[#009900] px-[1.5rem] py-[0.75rem] rounded-[99px] max-[768px]:text-[0.75rem]">{{ _t('customerbooking', 'trip_completed_status') }}</span>
            </div>

            <div class="flex items-end gap-2 max-[768px]:text-[0.875rem]">
              <img :src="carIcon" :alt="_t('customerbooking', 'car_icon_alt')">
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
                  <span class="text-customLightGrayColor">{{ formatDate(booking.pickup_date) }}</span>
                  <span class="text-customLightGrayColor">{{ booking.pickup_time }}</span>
                </div>
              </div>
              <div class="col">
                <div>
                  <strong>{{ _t('customerbooking', 'to_label') }}</strong>
                  <span class="ml-2">{{ booking.return_location }}</span>
                </div>
                <div class="flex gap-2">
                  <span class="text-customLightGrayColor">{{ formatDate(booking.return_date) }}</span>
                  <span class="text-customLightGrayColor">{{ booking.return_time }}</span>
                </div>
              </div>
            </div>
            <div class='flex justify-between items-center'>
              <div>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'day'">{{
                    formatPrice(booking.vehicle.price_per_day, booking.vehicle) }}{{ _t('customerbooking', 'price_per_day_suffix') }}</strong>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'week'">{{
                    formatPrice(booking.vehicle.price_per_week, booking.vehicle) }}{{ _t('customerbooking', 'price_per_week_suffix') }}</strong>
                  <strong class="text-[1.5rem] font-medium" v-if="booking.preferred_day === 'month'">{{
                    formatPrice(booking.vehicle.price_per_month, booking.vehicle) }}{{ _t('customerbooking', 'price_per_month_suffix') }}</strong>
                </div>
              <div>
                <button v-if="!booking.review" @click="openReviewModal(booking)"
                  class="button-primary px-[1.5rem] py-[0.75rem] max-[768px]:text-[0.75rem]">
                  {{ _t('customerbooking', 'write_a_review_button') }}
                </button>
                <div v-else class="text-green-600 flex items-center gap-2">
                  <CheckCircle class="w-5 h-5" />
                  <span>{{ _t('customerbooking', 'review_submitted_text') }}</span>
                </div>
                <!-- Redundant block was here, its closing div is removed below -->
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end">
          <Pagination :current-page="bookings.current_page" :total-pages="bookings.last_page"
            @page-change="handlePageChange" />
        </div>
      </div>

      <Dialog v-model:open="isReviewModalOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>{{ _t('customerbooking', 'dialog_title_write_review') }}</DialogTitle>
          </DialogHeader>
          <FormReview :booking="selectedBooking" @close="closeReviewModal" @reviewSubmitted="handleReviewSubmitted" />
        </DialogContent>
      </Dialog>

      <!-- Toast Notifications -->
      <div v-if="notification.show" :class="[
        'fixed bottom-4 right-4 p-4 rounded-md shadow-lg transition-all duration-500',
        notification.type === 'success' ? 'bg-green-500' : 'bg-red-500'
      ]">
        <div class="text-white">{{ notification.message }}</div> <!-- Assuming notification.message is already translated or dynamic -->
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import carIcon from '../../../../assets/carIcon.svg';
import { defineProps, ref, onMounted, getCurrentInstance } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import FormReview from '@/Components/ReviewForm.vue';
import { CheckCircle } from 'lucide-vue-next';
import Pagination from './Pagination.vue';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;

const props = defineProps({
    bookings: Object,
    filters: {
        type: Object,
        default: () => ({})
    }
});

console.log(props.bookings.data);


const notification = ref({
  show: false,
  message: '',
  type: 'success'
});

const showNotification = (message, type = 'success') => {
  notification.value = {
    show: true,
    message,
    type
  };

  setTimeout(() => {
    notification.value.show = false;
  }, 3000);
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const page = usePage();
const auth = page.props.auth;
const isReviewModalOpen = ref(false);
const selectedBooking = ref(null);

const openReviewModal = (booking) => {
  selectedBooking.value = booking;
  isReviewModalOpen.value = true;
};

const closeReviewModal = () => {
  isReviewModalOpen.value = false;
  selectedBooking.value = null;
};

const handleReviewSubmitted = () => {
  showNotification(_t('customerbooking', 'toast_review_submitted_success'));
  closeReviewModal();

  // Update the local booking to show review submitted
  if (selectedBooking.value) {
    const bookingIndex = props.bookings.data.findIndex(b => b.id === selectedBooking.value.id);
    if (bookingIndex !== -1) {
      props.bookings.data[bookingIndex].review = true;
    }
  }
};

// Handle pagination
// Handle pagination
  const handlePageChange = (page) => {
    router.get(route('profile.bookings.completed'), {
        ...props.filters,
        page
    }, {
        preserveState: true,
        preserveScroll: true
    });
};

// Watch for flash messages from the backend
onMounted(() => {
  const flash = page.props.flash || {}; // Default empty object
  if (flash.success) {
    showNotification(flash.success);
  }
  if (flash.error) {
    showNotification(flash.error, 'error');
  }
});

const formatPrice = (price, vehicle) => {
  const currencySymbol = vehicle?.vendor_profile?.currency ?? '$'; // Default to '$' if missing
  return `${currencySymbol}${price}`;
};


</script>
