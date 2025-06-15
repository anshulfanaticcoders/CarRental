<script setup>
import { ref, onMounted } from "vue"; // onMounted might be removed if fetchFavorites is removed
import axios from "axios"; // Keep for toggleFavourite if it remains axios-based
import { Link, router } from "@inertiajs/vue3"; // Added router
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import Pagination from '@/Components/ReusableComponents/Pagination.vue'; // Added Pagination
import Heart from "../../../assets/Heart.svg";
import FilledHeart from "../../../assets/FilledHeart.svg";
import goIcon from "../../../assets/goIcon.svg";
import carIcon from "../../../assets/carIcon.svg";
import walkIcon from "../../../assets/walking.svg";
import mileageIcon from "../../../assets/mileageIcon.svg";

// Props will now come from Inertia controller
const props = defineProps({
    favoriteVehicles: Object, // Expects a paginated object
});

import { useToast } from 'vue-toastification';
const toast = useToast(); // Initialize toast
const toggleFavourite = async (vehicle) => {
    const action = vehicle.is_favourite ? 'removed from' : 'added to';
    const endpoint = vehicle.is_favourite
        ? route('vehicles.unfavourite', { vehicle: vehicle.id })
        : route('vehicles.favourite', { vehicle: vehicle.id });

    try {
        await axios.post(endpoint);
        vehicle.is_favourite = !vehicle.is_favourite; // Toggle the favorite state

        // Show toast notification
        toast.success(`Vehicle ${action} favorites!`, {
            position: 'top-right',
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
            icon: vehicle.is_favourite ? '❤️' : '💔',
        });

    } catch (error) {
        toast.error('Failed to update favorites', {
            position: 'top-right', 
            timeout: 3000,
            closeOnClick: true,
            pauseOnHover: true,
            draggable: true,
        });
        console.error('Error:', error);
    }
};

// onMounted(fetchFavorites); // Removed as data comes from props

const formatPrice = (price, vehicle) => {
    const currencySymbol = vehicle?.vendor_profile?.currency ?? '€'; // Default to '€' if missing
    return `${currencySymbol}${price}`;
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
        <div v-if="!props.favoriteVehicles || !props.favoriteVehicles.data || props.favoriteVehicles.data.length === 0" class="text-gray-500">
            No favorite vehicles yet.
        </div>
        <div v-else class="grid grid-cols-3 gap-6 max-[768px]:grid-cols-1">
            <div v-for="vehicle in props.favoriteVehicles.data" :key="vehicle.id"
                class="p-[1rem] rounded-[12px] border-[1px] border-[#E7E7E7]">
                <div class="column flex justify-end">
                    <button @click.stop="toggleFavourite(vehicle)" class="heart-icon"
                                :class="{ 'filled-heart': vehicle.is_favourite }">
                                <img :src="vehicle.is_favourite ? FilledHeart : Heart" alt="Favorite"
                                    class="w-full mb-[1rem] transition-colors duration-300" />
                            </button>
                </div>
                <Link :href="route('vehicle.show', { id: vehicle.id })">
                    <div class="column flex flex-col gap-5 items-start">
                        <img v-if="vehicle.images" :src="`${vehicle.images.find(
                            (image) =>
                                image.image_type === 'primary'
                        )?.image_url
                            }`" alt="Primary Image" class="w-full h-[250px] object-cover rounded-lg" />
                        <span class="bg-[#f5f5f5] inline-block px-8 py-2 text-center rounded-[40px]">
                            {{ vehicle.model }}
                        </span>
                    </div>
                    <div class="column mt-[2rem]">
                        <h5 class="font-medium text-[1.5rem] text-customPrimaryColor max-[768px]:text-[1.2rem]">
                            {{ vehicle.brand }}
                        </h5>
                        <div class="car_short_info mt-[1rem] flex gap-3">
                            <img :src="carIcon" alt="" />
                            <div class="features">
                                <span class="capitalize text-[1.15rem] max-[768px]:text-[0.875rem]">{{ vehicle.transmission }} .
                                    {{ vehicle.fuel }} .
                                    {{
                                        vehicle.seating_capacity
                                    }}
                                    Seats</span>
                            </div>
                        </div>
                        <div class="extra_details flex gap-5 mt-[1rem]">
                            <!-- <div class="col flex gap-3">
                                <img :src="walkIcon" alt="" /><span class="text-[1.15rem]">9.3 KM Away</span>
                            </div> -->
                            <div class="col flex gap-3">
                                <img :src="mileageIcon" alt="" /><span class="text-[1.15rem] max-[768px]:text-[0.875rem]">{{ vehicle.mileage
                                    }}km/d</span>
                            </div>
                        </div>
                        <div class="mt-[2rem] flex justify-between items-center">
                            <div>
                                <span class="text-customPrimaryColor text-[1.875rem] font-medium max-[768px]:text-[1.35rem]">{{ formatPrice(vehicle.price_per_day, vehicle) }}</span><span>/day</span>
                            </div>
                            <img :src="goIcon" alt="" class="max-[768px]:w-[35px]"/>
                        </div>
                    </div>
                </Link>
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
