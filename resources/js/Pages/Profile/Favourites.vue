<script setup>
import { ref, watch } from "vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue'; // Added Pagination
import carIcon from "../../../assets/carIcon.svg";
import mileageIcon from "../../../assets/mileageIcon.svg";
import { Heart } from "lucide-vue-next";

// Props will now come from Inertia controller
const props = defineProps({
    favoriteVehicles: Object, // Expects a paginated object
    providerFavorites: Object,
});

import { toast as sonnerToast } from 'vue-sonner';

const favoriteLoading = ref({});
const favoriteVehiclesState = ref(props.favoriteVehicles?.data || []);
const providerFavoritesState = ref(props.providerFavorites?.data || []);

watch(
  () => props.favoriteVehicles?.data,
  (data) => {
    favoriteVehiclesState.value = data || [];
  }
);

watch(
  () => props.providerFavorites?.data,
  (data) => {
    providerFavoritesState.value = data || [];
  }
);
const toggleFavourite = async (vehicle) => {
    const endpoint = vehicle.is_favourite
        ? route('vehicles.unfavourite', { vehicle: vehicle.id })
        : route('vehicles.favourite', { vehicle: vehicle.id });

    try {
        favoriteLoading.value[vehicle.id] = true;
        await axios.post(endpoint);
        vehicle.is_favourite = !vehicle.is_favourite;

        if (!vehicle.is_favourite) {
            favoriteVehiclesState.value = favoriteVehiclesState.value.filter(
                (item) => item.id !== vehicle.id
            );
        }

        // Show toast notification
        sonnerToast.success(
            vehicle.is_favourite ? "Vehicle added to favorite." : "Vehicle removed from favorite.",
            {
                position: 'bottom-right',
                duration: 3000,
            }
        );

    } catch (error) {
        sonnerToast.error('Failed to update favorite.', {
            position: 'bottom-right',
            duration: 3000,
        });
        console.error('Error:', error);
    } finally {
        favoriteLoading.value[vehicle.id] = false;
    }
};

// onMounted(fetchFavorites); // Removed as data comes from props

const formatPrice = (price, vehicle) => {
    const currencySymbol = vehicle?.vendor_profile?.currency ?? '€'; // Default to '€' if missing
    return `${currencySymbol}${price}`;
};

const formatProviderPrice = (payload) => {
    if (!payload) return '';
    const amount = Number(payload.price_per_day);
    if (!Number.isFinite(amount)) return '';
    const currency = payload.currency ? `${payload.currency} ` : '';
    return `${currency}${amount.toFixed(2)}`;
};


const toggleProviderFavourite = async (favorite) => {
    if (!favorite) return;
    favoriteLoading.value[favorite.id] = true;
    try {
        await axios.post(route('favorites.provider.toggle'), {
            vehicle_key: favorite.vehicle_key,
            source: favorite.source,
            payload: favorite.payload || null,
        });

        providerFavoritesState.value = providerFavoritesState.value.filter(
            (item) => item.id !== favorite.id
        );

        sonnerToast.success('Vehicle removed from favorite.', {
            position: 'bottom-right',
            duration: 3000,
        });
    } catch (error) {
        sonnerToast.error('Failed to update favorite.', {
            position: 'bottom-right',
            duration: 3000,
        });
        console.error('Error:', error);
    } finally {
        favoriteLoading.value[favorite.id] = false;
    }
};

const handlePageChange = (page) => {
  // Assuming the route that calls FavoriteController@getFavorites is '/favorites'
  // and it's named 'favorites.index' or similar if using named routes in JS.
  // Using hardcoded path for now as route name is not confirmed for this page.
  router.get(`/favorites?page=${page}`, {}, {
    preserveState: true, // Preserves component state (e.g., scroll position if not for preserveScroll)
    preserveScroll: true, // Preserves scroll position
    // replace: true, // Optional: if you don't want pagination clicks in browser history
  });
};
</script>

<template>
    <MyProfileLayout>
        <p class="text-[1.5rem] max-[768px]:text-[1.2rem] text-customPrimaryColor font-bold mb-[2rem] bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem]">
            {{ _t('common','favorite_title') }}
        </p>
        <div v-if="favoriteVehiclesState.length === 0 && providerFavoritesState.length === 0" class="text-gray-500">
            No favorite vehicles yet.
        </div>
        <div v-if="favoriteVehiclesState.length > 0" class="grid grid-cols-3 gap-6 max-[768px]:grid-cols-1">
            <div v-for="vehicle in favoriteVehiclesState" :key="vehicle.id"
                class="favorite-card">
                <div class="favorite-image">
                    <img v-if="vehicle.images" :src="`${vehicle.images.find(
                        (image) =>
                            image.image_type === 'primary'
                    )?.image_url
                        }`" alt="Primary Image" />
                    <button class="favorite-toggle" :class="{ 'is-loading': favoriteLoading[vehicle.id] }"
                        :disabled="favoriteLoading[vehicle.id]" @click.stop="toggleFavourite(vehicle)"
                        :aria-busy="favoriteLoading[vehicle.id] ? 'true' : 'false'">
                        <Heart class="w-5 h-5 transition-all duration-300 fill-red-500 stroke-red-500" />
                    </button>
                </div>
                <div class="favorite-content">
                    <span class="favorite-pill">{{ vehicle.model }}</span>
                    <h5 class="favorite-title">{{ vehicle.brand }}</h5>
                    <div class="favorite-meta">
                        <img :src="carIcon" alt="" />
                        <span>{{ vehicle.transmission }} · {{ vehicle.fuel }} · {{ vehicle.seating_capacity }} Seats</span>
                    </div>
                    <div class="favorite-meta">
                        <img :src="mileageIcon" alt="" />
                        <span>{{ vehicle.mileage }}km/d</span>
                    </div>
                    <div class="favorite-price">
                        <span class="favorite-amount">{{ formatPrice(vehicle.price_per_day, vehicle) }}</span>
                        <span class="favorite-unit">/day</span>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="providerFavoritesState.length > 0" class="mt-10">
            <p class="text-[1.25rem] text-customPrimaryColor font-semibold mb-[1rem]">
                Provider favorites
            </p>
            <div class="grid grid-cols-3 gap-6 max-[768px]:grid-cols-1">
                <div v-for="favorite in providerFavoritesState" :key="favorite.id"
                    class="favorite-card">
                    <div class="favorite-image">
                        <img :src="favorite.payload?.image || '/images/dummyCarImaage.png'" alt="Primary Image" />
                        <button class="favorite-toggle" :class="{ 'is-loading': favoriteLoading[favorite.id] }"
                            :disabled="favoriteLoading[favorite.id]"
                            @click.stop="toggleProviderFavourite(favorite)"
                            :aria-busy="favoriteLoading[favorite.id] ? 'true' : 'false'">
                            <Heart class="w-5 h-5 transition-all duration-300 fill-red-500 stroke-red-500" />
                        </button>
                    </div>
                    <div class="favorite-content">
                        <span class="favorite-pill">{{ favorite.payload?.model || 'Vehicle' }}</span>
                        <h5 class="favorite-title">{{ favorite.payload?.brand || 'Provider Vehicle' }}</h5>
                        <div class="favorite-meta">
                            <img :src="mileageIcon" alt="" />
                            <span>Provider listing</span>
                        </div>
                        <div class="favorite-price">
                            <span class="favorite-amount">{{ formatProviderPrice(favorite.payload) }}</span>
                            <span class="favorite-unit">/day</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Pagination -->
        <div v-if="props.favoriteVehicles && props.favoriteVehicles.last_page > 1" class="mt-6 flex justify-center">
            <Pagination
                :currentPage="props.favoriteVehicles.current_page"
                :totalPages="props.favoriteVehicles.last_page"
                @page-change="handlePageChange"
            />
        </div>
    </MyProfileLayout>
</template>

<style scoped>
.favorite-card {
  background: #fff;
  border-radius: 18px;
  border: 1px solid #e7e7e7;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
  overflow: hidden;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
}

.favorite-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
}

.favorite-image {
  position: relative;
  height: 220px;
  overflow: hidden;
}

.favorite-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.favorite-image .favorite-toggle {
  position: absolute;
  top: 14px;
  right: 14px;
  z-index: 2;
}

.favorite-content {
  padding: 18px 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.favorite-pill {
  display: inline-flex;
  align-self: flex-start;
  padding: 6px 14px;
  border-radius: 999px;
  background: #f5f5f5;
  font-size: 0.8rem;
  font-weight: 600;
  color: #1f3d57;
}

.favorite-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #153b4f;
}

.favorite-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.95rem;
  color: #5c6b7a;
}

.favorite-meta img {
  width: 18px;
  height: 18px;
}

.favorite-price {
  margin-top: 6px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #153b4f;
  font-weight: 700;
}

.favorite-amount {
  font-size: 1.5rem;
}

.favorite-unit {
  font-size: 0.95rem;
  color: #6b7280;
}


.favorite-toggle {
  position: relative;
  width: 36px;
  height: 36px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #e7e7e7;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.favorite-toggle:hover {
  transform: scale(1.06);
  box-shadow: 0 8px 18px rgba(239, 68, 68, 0.18);
}

.favorite-toggle.is-loading {
  cursor: wait;
}

.favorite-toggle.is-loading::after {
  content: '';
  position: absolute;
  inset: -6px;
  border-radius: 999px;
  border: 2px solid rgba(15, 23, 42, 0.18);
  border-top-color: rgba(15, 23, 42, 0.7);
  animation: favorite-spin 0.8s linear infinite;
}

@keyframes favorite-spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
