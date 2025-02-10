<template>
    <div>
      <h2>Customer Reviews</h2>
      <div v-if="reviews.length > 0">
        <div v-for="review in reviews" :key="review.id" class="review-card">
          <div class="user-info">
            <img :src="review.user.profile?.profile_picture || '/images/default_profile.jpg'" alt="Profile Picture" class="profile-picture">
            <span class="user-name">{{ review.user.name }}</span>
          </div>
          <div class="rating">
            <StarRating :rating="review.rating" :read-only="true" />
          </div>
          <p class="review-text">{{ review.review_text }}</p>
          <p class="review-status">Status: {{ review.status }}</p>
          <div v-if="review.reply_text">
            <p class="reply-text">Vendor Reply: {{ review.reply_text }}</p>
          </div>
        </div>
      </div>
      <div v-else>
        <p>No reviews yet.</p>
      </div>
    </div>
  </template>
  
  <script setup>
  import { defineProps, ref, onMounted } from 'vue';
  import StarRating from 'vue-star-rating';
  import axios from 'axios';
  
  const props = defineProps({
    vehicleId: {
      type: Number,
      required: true,
    },
  });
  
  const reviews = ref([]);
  
  onMounted(async () => {
    try {
      const response = await axios.get(`/api/vehicles/${props.vehicleId}/reviews`); // Use the API route
      reviews.value = response.data.reviews;
    } catch (error) {
      console.error("Error fetching reviews:", error);
    }
  });
  
  </script>