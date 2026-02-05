<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref, computed, watch } from 'vue';
import axios from 'axios';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/Components/ui/card';

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
        <Card>
            <CardHeader>
                <CardTitle>My Review</CardTitle>
                <CardDescription>Your review history and vendor replies.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <div v-if="isLoading" class="text-sm text-slate-500">Loading reviews...</div>
                <div v-else-if="reviews.length === 0"
                    class="rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center text-sm text-slate-500">
                    No reviews available yet.
                </div>
                <div v-else v-for="review in reviews" :key="review.id" class="border rounded-xl p-6">
                    <div class="flex items-center gap-3">
                        <img :src="review.user.profile?.avatar ? `${review.user.profile?.avatar}` : '/storage/avatars/default-avatar.svg'"
                            alt="User Avatar" class="w-[50px] h-[50px] rounded-full object-cover" />
                        <div>
                            <h4 class="font-medium text-slate-900">{{
                                review.user.first_name }} {{ review.user.last_name }}</h4>
                            <div class="flex items-center gap-1">
                                <div class="star-rating">
                                    <img v-for="n in 5" :key="n" :src="getStarIcon(review.rating, n)"
                                        :alt="getStarAltText(review.rating, n)" class="w-[20px] h-[20px]" />
                                </div>
                                <span class="text-sm text-slate-600">{{ review.rating }}</span>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-slate-700">{{ review.review_text }}</p>
                    <div v-if="review.reply_text"
                        class="mt-4 rounded-xl border border-slate-200 px-4 py-3 bg-slate-50">
                        <p class="text-xs font-semibold text-slate-500">Vendor Reply</p>
                        <p class="text-sm text-slate-700">{{ review.reply_text }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </MyProfileLayout>
</template>

<style></style>
