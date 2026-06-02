<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { usePage, Head } from '@inertiajs/vue3';
import { onMounted, ref, watch, getCurrentInstance } from 'vue';
import axios from 'axios';
import { Star } from 'lucide-vue-next';

const { props } = usePage();
const { appContext } = getCurrentInstance();
const _t = appContext.config.globalProperties._t;
const tt = (group, key, fallback) => {
    const v = _t(group, key);
    return (!v || v === key) ? fallback : v;
};

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

        <div class="vr-phead">
            <div>
                <span class="vr-eyebrow"><Star /> {{ tt('customerprofilepages', 'reviews_eyebrow', 'My account') }}</span>
                <h2>{{ tt('customerprofilepages', 'my_reviews_header', 'My Reviews') }}</h2>
                <p class="vr-sub">{{ tt('customerprofilepages', 'my_reviews_subtitle', 'Your review history and vendor replies.') }}</p>
            </div>
        </div>

        <div v-if="isLoading" class="vr-panel">
            <div class="vr-empty">
                <div class="e-ic">
                    <Star />
                </div>
                <h4>{{ tt('common', 'loading', 'Loading reviews…') }}</h4>
            </div>
        </div>

        <div v-else-if="reviews.length === 0" class="vr-panel">
            <div class="vr-empty">
                <div class="e-ic">
                    <Star />
                </div>
                <h4>{{ tt('customerprofilepages', 'no_reviews_submitted', 'No reviews available yet') }}</h4>
                <p>{{ tt('customerprofilepages', 'my_reviews_subtitle', 'Your review history and vendor replies.') }}</p>
            </div>
        </div>

        <template v-else>
            <div v-for="review in reviews" :key="review.id" class="rev-card">
                <div class="rev-top">
                    <img :src="review.user.profile?.avatar ? `${review.user.profile?.avatar}` : '/storage/avatars/default-avatar.svg'"
                        alt="User Avatar" class="rev-avatar" />
                    <div>
                        <div class="rev-name">{{ review.user.first_name }} {{ review.user.last_name }}</div>
                        <div class="rev-rating-row">
                            <span class="stars">
                                <Star v-for="n in 5" :key="n" :class="n <= Math.round(review.rating) ? 'on' : 'off'" />
                            </span>
                            <span class="rev-score">{{ review.rating }}</span>
                        </div>
                    </div>
                </div>
                <p class="rev-text">{{ review.review_text }}</p>
                <div v-if="review.reply_text" class="rev-reply">
                    <div class="lab">{{ tt('customerprofilepages', 'vendor_reply', 'Vendor Reply') }}</div>
                    <p>{{ review.reply_text }}</p>
                </div>
            </div>
        </template>
    </MyProfileLayout>
</template>

<style scoped>
.rev-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 18px;
  box-shadow: 0 2px 4px rgba(21, 59, 79, 0.06), 0 1px 2px rgba(21, 59, 79, 0.04);
  margin-bottom: 14px;
}

.rev-top {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 10px;
}

.rev-avatar {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  object-fit: cover;
}

.rev-name {
  font-family: "Plus Jakarta Sans", sans-serif;
  font-weight: 700;
  font-size: 0.92rem;
  color: #0f172a;
}

.rev-rating-row {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 2px;
}

.stars {
  display: inline-flex;
  gap: 2px;
}

.stars :deep(svg) {
  width: 15px;
  height: 15px;
}

.stars :deep(.on) {
  color: #f59e0b;
  fill: #f59e0b;
}

.stars :deep(.off) {
  color: #cbd5e1;
  fill: none;
}

.rev-score {
  font-size: 0.82rem;
  font-weight: 600;
  color: #64748b;
}

.rev-text {
  font-size: 0.88rem;
  color: #334155;
}

.rev-reply {
  margin-top: 12px;
  padding: 12px 14px;
  background: #f8fafc;
  border-radius: 12px;
  border-left: 3px solid #153b4f;
}

.rev-reply .lab {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  color: #153b4f;
  margin-bottom: 4px;
}

.rev-reply p {
  font-size: 0.84rem;
  color: #64748b;
}
</style>
