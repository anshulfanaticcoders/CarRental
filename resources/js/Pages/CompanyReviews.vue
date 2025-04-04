<template>
    <AuthenticatedHeaderLayout />
  
    <div class="full-w-container company-reviews py-customVerticalSpacing">
      <h1 class="text-4xl font-bold text-customPrimaryColor mb-8">Customer Reviews</h1>
  
      <!-- Main content container with flex layout -->
      <div class="flex flex-col lg:flex-row gap-8">
        <!-- Reviews section (left side) -->
        <div class="flex-grow w-full lg:w-3/4">
          <!-- Review List -->
          <div v-if="reviews.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <div v-for="review in reviews.data" :key="review.id" class="review-card rounded-lg border p-4 shadow-sm">
              <div class="flex items-center gap-3 mb-3">
                <img :src="review.user.profile?.avatar ? review.user.profile.avatar : '/storage/avatars/default-avatar.svg'"
                  alt="User Avatar" class="w-12 h-12 rounded-full object-cover" />
                <div>
                  <span class="text-customPrimaryColor font-medium">{{ review.user.first_name }} {{ review.user.last_name }}</span>
                  <div class="flex items-center gap-1">
                    <img v-for="n in 5" :key="n" :src="getStarIcon(review.rating, n)"
                      :alt="getStarAltText(review.rating, n)" class="w-4 h-4" />
                    <span class="text-sm ml-1">{{ review.rating }}</span>
                  </div>
                </div>
              </div>
              <p class="text-gray-700 mb-2 line-clamp-3">{{ review.review_text }}</p>
              <div v-if="review.reply_text"
                class="reply-text border rounded-lg p-3 bg-gray-50 my-2">
                <p class="text-gray-600 text-sm font-medium">Vendor Reply:</p>
                <p class="text-sm">{{ review.reply_text }}</p>
              </div>
              <p class="text-xs text-gray-500 mt-2">Vehicle: {{ review.vehicle?.brand }} {{ review.vehicle?.model }}</p>
            </div>
          </div>
          <div v-else class="text-center py-12">
            <p class="text-gray-500">No reviews available for this vendor.</p>
          </div>
  
          <!-- Pagination Links -->
          <div class="mt-16 flex justify-center">
            <div v-html="pagination_links"></div>
          </div>
        </div>
  
        <!-- Rating Statistics with Progress Bars (right side - sticky) -->
        <div class="lg:w-1/4 w-full">
          <div class="sticky top-24" v-if="reviews.data.length > 0">
            <div class="rating-stats bg-white p-6 rounded-lg border shadow-sm">
              <h2 class="text-xl font-semibold mb-4 text-customPrimaryColor">Rating Breakdown</h2>
              <div class="grid gap-3">
                <div v-for="rating in [5, 4, 3, 2, 1]" :key="rating" class="flex items-center gap-3">
                  <span class="w-16 text-sm font-medium">{{ rating }} Stars</span>
                  <div class="flex-1 bg-gray-200 rounded-full h-2 overflow-hidden">
                    <div :style="{ width: ratingDistribution[rating].percentage + '%' }"
                      class="bg-customPrimaryColor h-full rounded-full transition-all duration-300"></div>
                  </div>
                  <span class="w-10 text-sm text-gray-600 text-right">{{ ratingDistribution[rating].count }}</span>
                </div>
              </div>
              
              <!-- Average Rating Section -->
              <div class="mt-6 pt-6 border-t">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-medium">Average Rating</span>
                  <div class="flex items-center">
                    <span class="text-2xl font-bold text-customPrimaryColor mr-2">
                      {{ calculateAverageRating() }}
                    </span>
                    <div class="flex">
                      <img v-for="n in 5" :key="n" :src="getStarIcon(calculateAverageRating(), n)"
                        :alt="getStarAltText(calculateAverageRating(), n)" class="w-5 h-5" />
                    </div>
                  </div>
                </div>
                <p class="text-sm text-gray-500 mt-2">Based on {{ calculateTotalReviews() }} reviews</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Footer/>
  </template>
  
  <script setup>
  import { defineProps, computed } from 'vue';
  import fullStar from "../../assets/fullstar.svg";
  import halfStar from "../../assets/halfstar.svg";
  import blankStar from "../../assets/blankstar.svg";
  import AuthenticatedHeaderLayout from '@/Layouts/AuthenticatedHeaderLayout.vue';
import Footer from '@/Components/Footer.vue';
  
  const props = defineProps({
    reviews: Object,
    vendorProfileId: String,
    ratingDistribution: Object,
    pagination_links: String,
  });
  
  // Star rating logic
  const getStarIcon = (rating, starNumber) => {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
  
    if (starNumber <= fullStars) {
      return fullStar;
    } else if (starNumber === fullStars + 1 && hasHalfStar) {
      return halfStar;
    } else {
      return blankStar;
    }
  };
  
  const getStarAltText = (rating, starNumber) => {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;
  
    if (starNumber <= fullStars) {
      return "Full Star";
    } else if (starNumber === fullStars + 1 && hasHalfStar) {
      return "Half Star";
    } else {
      return "Blank Star";
    }
  };
  
  // Calculate average rating
  const calculateAverageRating = () => {
    if (!props.ratingDistribution) return 0;
    
    let totalScore = 0;
    let totalCount = 0;
    
    for (let i = 1; i <= 5; i++) {
      totalScore += i * props.ratingDistribution[i].count;
      totalCount += props.ratingDistribution[i].count;
    }
    
    return totalCount > 0 ? (totalScore / totalCount).toFixed(1) : 0;
  };
  
  // Calculate total reviews
  const calculateTotalReviews = () => {
    if (!props.ratingDistribution) return 0;
    
    let total = 0;
    for (let i = 1; i <= 5; i++) {
      total += props.ratingDistribution[i].count;
    }
    
    return total;
  };
  </script>
  
  <style scoped>
  .review-card {
    background: #fff;
    transition: all 0.2s ease;
  }
  
  .review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }
  
  .sticky {
    position: sticky;
    top: 6rem;
  }
  </style>