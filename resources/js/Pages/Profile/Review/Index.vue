<template>
    <MyProfileLayout>
        <div class="p-4">
            <h1 class="text-2xl font-semibold mb-4">My Reviews</h1>

            <div v-if="reviews.data.length > 0">
                <div v-for="review in reviews.data" :key="review.id" class="border rounded-lg p-6 mb-4 shadow-md">
                    <div class="flex justify-between items-start mb-4 max-[480px]:flex-col">
                        <div class="flex items-start gap-8 max-[480px]:flex-col">
                            <div class="max-w-[308px] max-h-[100px] max-[480px]:max-w-full max-[480px]:max-h-[500px]">
                                <img :src="getPrimaryImage(review)"
                                    :alt="`${review.vehicle.brand} ${review.vehicle.model}`" alt="Vehicle"
                                    class="object-cover rounded mr-4 w-full h-full" />
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center mb-[-1rem] max-[480px]:gap-2">
                                    <h3 class="text-[1.5rem] font-semibold max-[480px]:text-[1rem]">{{ review.vehicle.brand }} {{
                                        review.vehicle.model }}
                                    </h3>
                                    <span
                                        class="bg-gray-200 text-customPrimaryColor px-4 py-3 rounded-full inline-block ml-[2rem]
                                        max-[480px]:text-[0.75rem]  max-[480px]:ml-0">
                                        {{ review.vehicle.category.name }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 capitalize">{{ review.vehicle.transmission }} . {{
                                    review.vehicle.fuel }} . {{ review.vehicle.seating_capacity }} seats</p>
                                <div class="flex max-[480px]:mb-3">
                                    <img v-if="review.vehicle.vendor_profile && review.vehicle.vendor_profile.avatar"
                                        :src="`${review.vehicle.vendor_profile.avatar}`"
                                        class="w-12 h-12 rounded-full mr-2" />
                                    <div>
                                        <p class="text-[1.1rem]">{{ review.vehicle.user.first_name }}</p>
                                        <p class="text-[0.75rem] text-customLightGrayColor font-medium">Verified
                                            Customer</p>

                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="text-sm">
                            <div class="flex items-center mb-2">
                                <div>
                                    <p class="font-semibold">{{ review.user.name }}</p>
                                    <p v-if="review.user.verified" class="text-green-500">Verified Customer</p>
                                </div>
                            </div>
                            <div
                                class="bg-gray-200 rounded-[6px] min-w-[300px] min-h-[160px] flex flex-col gap-5 justify-between p-4">
                                <div class="flex flex-col gap-1">
                                    <strong class="text-customPrimaryColor">From:</strong>
                                    <p class="text-customPrimaryColor font-medium text-[0.875rem]"> {{
                                        review.booking.pickup_location }}</p>
                                    <div class="flex gap-1">
                                        <p class="text-gray-600">{{ formatDate(review.booking.pickup_date) }},</p>
                                        <p class="text-gray-600">{{ formatTime(review.booking.pickup_time) }}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <strong class="text-customPrimaryColor">To:</strong>
                                    <p class="text-customPrimaryColor font-medium text-[0.875rem]"> {{
                                        review.booking.return_location }}</p>
                                    <div class="flex gap-1">
                                        <p class="text-gray-600">{{ formatDate(review.booking.return_date) }},</p>
                                        <p class="text-gray-600">{{ formatTime(review.booking.return_time) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="flex items-center mb-2">
                            <div class="flex items-center gap-1">
                                <div class="star-rating">
                                    <img v-for="n in 5" :key="n" :src="getStarIcon(review.rating, n)"
                                        :alt="getStarAltText(review.rating, n)" class="w-[20px] h-[20px]" />
                                </div>
                                <span>{{ review.rating.toFixed(1) }} Ratings</span>
                            </div>
                        </div>
                        <p class="text-gray-700">
                            "{{ review.review_text }}"
                        </p>
                    </div>

                </div>
                <Pagination :current-page="reviews.current_page" :total-pages="reviews.last_page"
                    @page-change="handlePageChange" />
            </div>
            <div v-else>
                <p>You have not submitted any reviews yet.</p>
            </div>
        </div>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import Pagination from './Pagination.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import fullStar from "../../../../assets/fullstar.svg";
import halfStar from "../../../../assets/halfstar.svg";
import blankStar from "../../../../assets/blankstar.svg";

const props = defineProps({
    reviews: Object,
    statistics: Object,
});

const search = ref('');

const handlePageChange = (page) => {
    let routeName = 'profile.reviews';
    const params = { page: page, search: search.value };

    router.get(route(routeName, params), { preserveState: true, replace: true });
};

const getPrimaryImage = (review) => {
    if (!review.vehicle.images || review.vehicle.images.length === 0) {
        return '/images/placeholder.jpg'; // Return placeholder if no images
    }
    const primaryImage = review.vehicle.images.find(img => img.image_type === 'primary');
    if (!primaryImage) {
        return `${review.vehicle.images[0].image_path}`; // Return first image if no primary
    }
    return `${primaryImage.image_url}`;
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const formatTime = (timeString) => {
    if (!timeString) return ''; // Handle null or undefined time

    const [hours, minutes] = timeString.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const formattedHours = hours % 12 === 0 ? 12 : hours % 12;

    return `${formattedHours}:${minutes.toString().padStart(2, '0')} ${period}`;
};

// Your star icon functions
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
</script>

<style>
.star-rating {
    display: flex;
    /* Ensure stars are displayed horizontally */
}
</style>