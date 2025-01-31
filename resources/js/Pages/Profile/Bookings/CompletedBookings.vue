<template>
  <MyProfileLayout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold mb-6">Completed Bookings</h1>

      <div v-if="bookings.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
          <img :src="bookingstatusIcon" alt="" class="w-[30rem]">
          <p>No completed bookings found.</p>
        </div>
      </div>

      <div v-else>
        <div v-for="booking in bookings" :key="booking.id"
             class="bg-white shadow-md rounded-lg p-6 gap-10 flex justify-between mb-6">
          <div class="w-20%">
            <img v-if="booking.vehicle?.images"
                 :src="`/storage/${booking.vehicle.images.find(image => image.image_type === 'primary')?.image_path}`"
                 alt="Image of the booked {{ booking.vehicle.brand }} {{ booking.vehicle.model }}"
                 class="w-full h-[250px] object-cover rounded-md" />
            <img v-else src="/path/to/placeholder-image.jpg" alt="Placeholder Image" class="w-full h-[250px] object-cover rounded-md" />
          </div>
          <div class="w-[67%] flex flex-col gap-5">
            <div class="flex justify-between items-center">
              <div class="flex justify-between items-center gap-10">
                <span class="text-[2rem] font-medium text-customPrimaryColor">{{ booking.vehicle.brand }}</span>
                <span class="bg-customLightPrimaryColor p-3 rounded-[99px] text-[1rem]">{{ booking.vehicle?.category?.name }}</span>
              </div>
              <span class="bg-[#0099001A] text-[#009900] px-[1.5rem] py-[0.75rem] rounded-[99px]">Trip Completed</span>
            </div>

            <div class="flex items-end gap-2">
              <img :src="carIcon" alt="Icon of a car">
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.transmission }} .</span>
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.fuel }} .</span>
              <span class="capitalize text-customLightGrayColor">{{ booking.vehicle.seating_capacity }} Seats</span>
            </div>

            <div class="flex justify-between w-[70%]">
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
            <div class='flex justify-between items-center'>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium">${{ booking.total_amount }}</span>
              <div>
                <button 
                  v-if="!booking.review"
                  @click="openReviewModal(booking)" 
                  class="button-primary px-[1.5rem] py-[0.75rem]"
                >
                  Write a Review
                </button>
                <div v-else class="text-green-600 flex items-center gap-2">
                  <CheckCircle class="w-5 h-5" />
                  <span>Review Submitted</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <Dialog v-model:open="isReviewModalOpen">
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Write a Review</DialogTitle>
          </DialogHeader>
          <FormReview 
            :booking="selectedBooking" 
            @close="closeReviewModal" 
            @reviewSubmitted="handleReviewSubmitted" 
          />
        </DialogContent>
      </Dialog>

      <!-- Toast Notifications -->
      <div v-if="notification.show" 
           :class="[
             'fixed bottom-4 right-4 p-4 rounded-md shadow-lg transition-all duration-500',
             notification.type === 'success' ? 'bg-green-500' : 'bg-red-500'
           ]"
      >
        <div class="text-white">{{ notification.message }}</div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import carIcon from '../../../../assets/carIcon.svg';
import { defineProps, ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import FormReview from '@/Components/ReviewForm.vue';
import { CheckCircle } from 'lucide-vue-next';

const props = defineProps({
  bookings: {
    type: Array,
    default: () => []
  }
});

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
  showNotification('Review submitted successfully!');
  closeReviewModal();
  
  // Update the local booking to show review submitted
  if (selectedBooking.value) {
    const bookingIndex = props.bookings.findIndex(b => b.id === selectedBooking.value.id);
    if (bookingIndex !== -1) {
      props.bookings[bookingIndex].review = true;
    }
  }
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
</script>