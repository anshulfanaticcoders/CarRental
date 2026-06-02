<template>
    <MyProfileLayout>
        <div class="vr-phead">
            <div>
                <span class="vr-eyebrow"><Star /> {{ tt('customerprofilepages', 'reviews_eyebrow', 'My account') }}</span>
                <h2>{{ tt('customerprofilepages', 'my_reviews_header', 'My Reviews') }}</h2>
                <p class="vr-sub">{{ tt('customerprofilepages', 'my_reviews_subtitle', 'See what you shared about your rentals.') }}</p>
            </div>
        </div>

        <div v-if="reviews.data.length > 0" class="space-y-6">
                <div v-for="review in reviews.data" :key="review.id" class="rv-card">
                    <div class="flex justify-between items-start mb-4 max-[768px]:flex-col">
                        <div class="flex items-start gap-8 max-[768px]:flex-col">
                            <div class="max-w-[308px] max-[768px]:max-w-full">
                                <img :src="getPrimaryImage(review)"
                                    :alt="`${review.vehicle.brand} ${review.vehicle.model}`" alt="Vehicle"
                                    class="object-cover rounded mr-4 w-full h-full" />
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center mb-[-1rem] max-[768px]:gap-2">
                                    <h3 class="text-[1.5rem] font-semibold max-[768px]:text-[1rem]">{{ review.vehicle.brand }} {{
                                        review.vehicle.model }}
                                    </h3>
                                    <span class="vr-vbadge ml-[2rem] max-[768px]:ml-0">
                                        {{ review.vehicle.category.name }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 capitalize">{{ review.vehicle.transmission }} . {{
                                    review.vehicle.fuel }} . {{ review.vehicle.seating_capacity }} {{ _t('customerprofilepages', 'vehicle_seats') }}</p>
                                <div class="flex max-[768px]:mb-3">
                                    <img v-if="review.vehicle.vendor_profile && review.vehicle.vendor_profile.avatar"
                                        :src="`${review.vehicle.vendor_profile.avatar}`"
                                        class="w-12 h-12 rounded-full mr-2" />
                                    <div>
                                        <p class="text-[1.1rem]">{{ review.vendor_profile_data.company_name }}</p>
                                        <p class="text-[0.75rem] text-customLightGrayColor font-medium">{{ _t('customerprofilepages', 'verified_company') }}</p>

                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="text-sm">
                            <div class="flex items-center mb-2">
                                <div>
                                    <p class="font-semibold">{{ review.user.name }}</p>
                                    <p v-if="review.user.verified" class="text-green-500">{{ _t('customerprofilepages', 'verified_customer') }}</p>
                                </div>
                            </div>
                            <div
                                class="rv-booking min-w-[300px] min-h-[160px] flex flex-col gap-5 justify-between">
                                <div class="flex flex-col gap-1">
                                    <strong class="text-customPrimaryColor">{{ _t('customerprofilepages', 'booking_from') }}</strong>
                                    <p class="text-customPrimaryColor font-medium text-[0.875rem]"> {{
                                        review.booking.pickup_location }}</p>
                                    <div class="flex gap-1">
                                        <p class="text-gray-600">{{ formatDate(review.booking.pickup_date) }},</p>
                                        <p class="text-gray-600">{{ review.booking.pickup_time}}</p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <strong class="text-customPrimaryColor">{{ _t('customerprofilepages', 'booking_to') }}</strong>
                                    <p class="text-customPrimaryColor font-medium text-[0.875rem]"> {{
                                        review.booking.return_location }}</p>
                                    <div class="flex gap-1">
                                        <p class="text-gray-600">{{ formatDate(review.booking.return_date) }},</p>
                                        <p class="text-gray-600">{{ review.booking.return_time }}</p>
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
                                <span>{{ review.rating.toFixed(1) }} {{ _t('customerprofilepages', 'review_ratings') }}</span>
                            </div>
                        </div>
                        <p class="text-gray-700">
                            "{{ review.review_text }}"
                        </p>

                        <div class="flex mt-2 items-center gap-2">
                            <span class="font-bold">{{ tt('customerprofilepages', 'verification_status', 'Verification Status') }} :</span>
                            <span class="vr-chip" :class="{
                                ok: review.status === 'approved',
                                warn: review.status === 'pending',
                                bad: review.status === 'rejected',
                            }">
                                {{ review.status }}
                            </span>
                        </div>
                    </div>

                </div>
                <div class="vr-pager">
                    <span class="info"></span>
                    <Pagination :current-page="reviews.current_page" :total-pages="reviews.last_page"
                        @page-change="handlePageChange" />
                </div>
            </div>
            <div v-else class="vr-panel">
                <div class="vr-empty">
                    <div class="e-ic">
                        <Star />
                    </div>
                    <h4>{{ tt('customerprofilepages', 'no_reviews_submitted', 'No reviews available yet') }}</h4>
                    <p>{{ tt('customerprofilepages', 'my_reviews_subtitle', 'See what you shared about your rentals.') }}</p>
                </div>
            </div>
    </MyProfileLayout>
</template>

<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import Pagination from './Pagination.vue';
import { router } from '@inertiajs/vue3';
import { ref, getCurrentInstance } from 'vue';
import fullStar from "../../../../assets/fullstar.svg";
import halfStar from "../../../../assets/halfstar.svg";
import blankStar from "../../../../assets/blankstar.svg";
import { Star } from 'lucide-vue-next';

const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};

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
        return _t('customerprofilepages', 'alt_full_star');
    } else if (starNumber === fullStars + 1 && hasHalfStar) {
        return _t('customerprofilepages', 'alt_half_star');
    } else {
        return _t('customerprofilepages', 'alt_blank_star');
    }
};
</script>

<style>
.star-rating {
    display: flex;
    /* Ensure stars are displayed horizontally */
}

.rv-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1);
}

.rv-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(21, 59, 79, 0.08), 0 2px 4px rgba(21, 59, 79, 0.04);
}

.rv-booking {
    background: #f8fafc;
    border: 1px solid #eef2f6;
    border-radius: 14px;
    padding: 16px;
}
</style>
