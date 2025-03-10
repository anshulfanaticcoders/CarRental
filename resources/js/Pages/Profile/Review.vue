<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import axios from 'axios';

const { props } = usePage();
const vehicle = ref(null);
const reviews = ref([]);
const isLoading = ref(true);

// Watch for props.vehicle to become available
watch(
    () => props.vehicle,
    (newVehicle) => {
        if (newVehicle) {
            vehicle.value = newVehicle;
            fetchReviews();
        }
    },
    { immediate: true }
);

const fetchReviews = async () => {
    if (!vehicle.value || !vehicle.value.id) {
        console.error("Vehicle is undefined or missing an ID");
        return;
    }

    isLoading.value = true;
    try {
        const response = await axios.get(`/api/vehicles/${vehicle.value.id}/reviews`);
        reviews.value = response.data.reviews;
    } catch (error) {
        console.error("Error fetching reviews:", error);
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    if (props.vehicle) {
        vehicle.value = props.vehicle;
        fetchReviews();
    } else {
        console.error("Vehicle data is not available on mount.");
    }
});
</script>


<template>
    <MyProfileLayout>

        <Head title="My Review" />
        <div class="">
            <h4 class="text-[1.5rem] mb-[2rem] ml-[1rem] text-customPrimaryColor font-medium">My Review</h4>
            <div v-for="review in reviews" :key="review.id" class=" mx-auto">
                <div class="review-item  px-[1rem] py-[2rem] h-full">
                    <div class="flex items-center gap-3">
                        <img :src="review.user.profile?.avatar ? `${review.user.profile?.avatar}` : '/storage/avatars/default-avatar.svg'"
                            alt="User Avatar" class="w-[50px] h-[50px] rounded-full object-cover" />
                        <div>
                            <h4 class="text-customPrimaryColor font-medium">{{
                                review.user.first_name }} {{ review.user.last_name }}</h4>
                            <div class="flex items-center gap-1">
                                <div class="star-rating">
                                    <img v-for="n in 5" :key="n" :src="getStarIcon(review.rating, n)"
                                        :alt="getStarAltText(review.rating, n)" class="w-[20px] h-[20px]" />
                                </div>
                                <span>{{ review.rating }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-2">{{ review.review_text }}</p>
                    <div v-if="review.reply_text"
                        class="mt-2 reply-text border-[1px] rounded-[0.75em] px-[1rem] py-[1rem] bg-[#f5f5f5]">
                        <p class="text-gray-600">Vendor Reply:</p>
                        <p>{{ review.reply_text }}</p>
                    </div>
                </div>
            </div>
        </div>
    </MyProfileLayout>
</template>

<style></style>