<template>
  <MyProfileLayout>
    <div class="container mx-auto px-4">
      <p
        class="text-[1.5rem] text-customPrimaryColor font-bold mb-[2rem] bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem]">
        Confirmed Bookings</p>

      <div v-if="bookings.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
          <img :src="bookingstatusIcon" alt="" class="w-[30rem]">
          <p>No completed bookings found.</p>
        </div>
      </div>

      <div v-else>
        <div v-for="booking in bookings" :key="booking.id"
          class="bg-white shadow-md rounded-lg p-6 gap-10 flex justify-between mb-6">
          <Link :href="`/vehicle/${booking.vehicle.id}`">
          <div class="w-20%"> <img v-if="booking.vehicle?.images"
              :src="`/storage/${booking.vehicle.images.find(image => image.image_type === 'primary')?.image_path}`"
              alt="Primary Vehicle Image" class="w-full h-[250px] object-cover rounded-md" /> <img v-else
              src="/path/to/placeholder-image.jpg" alt="Placeholder Image"
              class="w-full h-[250px] object-cover rounded-md" />
          </div>
          </Link>
          <div class="w-[67%] flex flex-col gap-5">
            <div class="flex justify-between items-center">
              <div class="flex justify-between items-center gap-10">
                <span class="text-[2rem] font-medium text-customPrimaryColor">{{ booking.vehicle.brand }}</span> <span
                  class="bg-customLightPrimaryColor p-3 rounded-[99px] text-[1rem]">{{ booking.vehicle?.category.name
                  }}</span>
              </div>
              <span class="bg-[#0099001A] text-[#009900] px-[1.5rem] py-[0.75rem] rounded-[99px]">Confirmed</span>
            </div>

            <div class="flex items-end gap-2">
              <img :src="carIcon" alt="Car Icon"> <span class="capitalize text-customLightGrayColor">{{
                booking.vehicle.transmission }} .</span>
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
            <div>
              <span class="text-customPrimaryColor text-[1.5rem] font-medium">${{ booking.total_amount }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import bookingstatusIcon from '../../../../assets/bookingstatusIcon.svg';
import carIcon from '../../../../assets/carIcon.svg'; // Import car icon
import { defineProps } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  bookings: {
    type: Array,
    default: () => []
  }
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};
</script>