<!-- Components/ReviewForm.vue -->
<template>
    <form @submit.prevent="submitReview" class="space-y-6">
      <div class="flex flex-col gap-4">
        <!-- Rating Input -->
        <div>
          <label class="block text-sm font-medium mb-2">Rating</label>
          <div class="flex gap-2">
            <button 
              v-for="star in 5" 
              :key="star"
              type="button"
              @click="form.rating = star"
              class="text-2xl focus:outline-none"
              :class="star <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
            >
              ★
            </button>
          </div>
          <div v-if="form.errors.rating" class="text-red-500 text-sm mt-1">
            {{ form.errors.rating }}
          </div>
        </div>
  
        <!-- Review Text -->
        <div>
          <label for="review_text" class="block text-sm font-medium mb-2">Your Review</label>
          <textarea
            id="review_text"
            v-model="form.review_text"
            rows="4"
            class="w-full border rounded-md p-2"
            placeholder="Share your experience..."
          ></textarea>
          <div v-if="form.errors.review_text" class="text-red-500 text-sm mt-1">
            {{ form.errors.review_text }}
          </div>
        </div>
      </div>
  
      <!-- Submit Button -->
      <div class="flex justify-end">
        <button
          type="submit"
          class="button-primary px-4 py-2"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Submitting...' : 'Submit Review' }}
        </button>
      </div>
    </form>
  </template>
  
  <script setup>
  import { useForm } from '@inertiajs/vue3';
  
  const props = defineProps({
    booking: {
      type: Object,
      required: true
    }
  });
  
  const emit = defineEmits(['close', 'reviewSubmitted']);
  
  const form = useForm({
    booking_id: props.booking.id,
    vehicle_id: props.booking.vehicle.id,
    vendor_profile_id: props.booking.vehicle.vendor_profile_data.id,
    rating: 0,
    review_text: '',
  });
  
  const submitReview = () => {
    form.post(route('reviews.store'), {
      onSuccess: () => {
        emit('reviewSubmitted');
        emit('close');
      },
    });
  };
  </script>