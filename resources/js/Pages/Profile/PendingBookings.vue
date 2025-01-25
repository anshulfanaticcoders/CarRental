<template>
 <MyProfileLayout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-2xl font-bold mb-6">Pending Bookings</h1>
      
      <div v-if="bookings.length === 0" class="text-center text-gray-500">
        <div class="flex flex-col justify-center items-center">
            <img :src=bookingstatusIcon alt="" class="w-[30rem]">
            <p>No pending bookings found.</p>
        </div>
      </div>
  
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="booking in bookings" :key="booking.id" class="bg-white shadow-md rounded-lg p-6">
          <div class="mb-4">
            <img 
            v-if="booking.vehicle?.images" 
            :src="`/storage/${booking.vehicle.images.find(
              (image) => image.image_type === 'primary'
            )?.image_path}`" 
            alt="Primary Vehicle Image" 
            class="w-full h-full object-cover rounded-md"
          />
          </div>
          <div>
            <h2 class="text-xl font-semibold mb-2">{{ booking.vehicle.name }}</h2>
            <p><strong>Booking Number:</strong> {{ booking.booking_number }}</p>
            <p><strong>Pickup Date:</strong> {{ formatDate(booking.pickup_date) }}</p>
            <p><strong>Return Date:</strong> {{ formatDate(booking.return_date) }}</p>
            <p><strong>Total Amount:</strong> ${{ booking.total_amount }}</p>
            <span class="inline-block bg-yellow-100 text-yellow-800 px-2 py-1 rounded mt-2">
              Pending
            </span>
          </div>
        </div>
      </div>
    </div>
 </MyProfileLayout>
  </template>
  
  <script setup>
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  import bookingstatusIcon from '../../../assets/bookingstatusIcon.svg';
import { defineProps } from 'vue'
  
  // Define props to receive bookings from server-side
  const props = defineProps({
    bookings: {
      type: Array,
      default: () => []
    }
  })
  
  // Helper function to format dates
  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    })
  }
  </script>