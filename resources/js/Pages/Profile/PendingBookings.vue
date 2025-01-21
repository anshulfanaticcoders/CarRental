<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    bookings: {
        type: Array,
        required: true,
    },
});

const error = ref(null);
const loading = ref(false);

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const cancelBooking = async (bookingId) => {
    if (confirm('Are you sure you want to cancel this booking?')) {
        try {
            await axios.post(`/api/bookings/${bookingId}/cancel`);
            window.location.reload();
        } catch (err) {
            error.value = 'Failed to cancel booking. Please try again.';
            console.error('Error canceling booking:', err);
        }
    }
};
</script>

<template>
    <MyProfileLayout>
        <div class="p-6 bg-white rounded shadow-md">
            <div class="bg-[#F3F6F7]">
                <h4 class="text-customDarkBlackColor font-medium p-5 mb-[1rem] rounded-[12px]">
                    My Bookings
                </h4>
            </div>
                    <p class="text-[2rem] font-medium my-[2rem] text-customDarkBlackColor">Pending</p>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-6">
                <span class="text-gray-500">Loading booking details...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-6 text-red-600">
                {{ error }}
            </div>
            
            <!-- Bookings Section -->
            <div v-else-if="bookings.length">
                <div
                    v-for="booking in bookings"
                    :key="booking.id"
                    class="bg-white rounded-lg shadow-sm p-6 mb-8"
                >
                    <div
                        class="border-[1px] border-[#E7E7E7] rounded-[12px] flex justify-between gap-5 p-5"
                    >
                        <!-- Vehicle Image -->
                        <div class="column w-[20%]">
                            <img v-if="booking.vehicle.images" :src="`/storage/${vehicle.images.find(
                            (image) => image.image_type === 'primary'
                        )?.image_path
                            }`" alt="Primary Image" class="w-full h-full object-cover rounded-lg" />
                        </div>

                        <!-- Booking Info -->
                        <div class="column flex flex-col w-[70%]">
                            <div class="col">
                                <div class="flex justify-between items-start">
                                    <div class="flex flex-col">
                                        <div class="flex gap-10 items-center">
                                            <span
                                                class="text-[1.75rem] text-customPrimaryColor font-bold"
                                            >
                                                {{ booking.vehicle.brand }}
                                            </span>
                                            <span
                                                class="bg-[#F5F5F5] px-5 py-2 rounded-[99px]"
                                            >
                                                {{ booking.vehicle.model }}
                                            </span>
                                        </div>
                                        <div class="car_short_info mt-[1rem] flex gap-3">
                                            <img :src="carIcon" alt="" />
                                            <div class="features">
                                                <span
                                                    class="text-[1.1rem] capitalize text-customLightGrayColor"
                                                >
                                                    {{ booking.vehicle.transmission }} .
                                                    {{ booking.vehicle.fuel }} .
                                                    {{ booking.vehicle.seating_capacity }} Seats
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="bg-[#906F001A] px-5 py-3 rounded-[99px]"
                                    >
                                        <span
                                            class="text-[#906F00] font-medium capitalize"
                                        >
                                            Booking under progress
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col mt-[2rem]">
                                <div class="grid grid-cols-2 gap-8 mb-6">
                                    <div class="flex items-start gap-[1rem]">
                                        <h3
                                            class="font-medium text-lg text-customPrimaryColor"
                                        >
                                            From:
                                        </h3>
                                        <div class="text-customLightGrayColor">
                                            <p
                                                class="text-customDarkBlackColor text-[1.1rem]"
                                            >
                                                {{ booking.pickup_location }}
                                            </p>
                                            <p class="mt-[0.5rem]">
                                                {{ formatDate(booking.pickup_date) }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-[1rem]">
                                        <h3
                                            class="font-medium text-lg text-customPrimaryColor"
                                        >
                                            To:
                                        </h3>
                                        <div class="text-customLightGrayColor">
                                            <p
                                                class="text-customDarkBlackColor text-[1.1rem]"
                                            >
                                                {{ booking.return_location }}
                                            </p>
                                            <p class="mt-[0.5rem]">
                                                {{ formatDate(booking.return_date) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col mt-[1rem]">
                                <span
                                    class="text-[1.5rem] text-customDarkBlackColor font-bold"
                                >
                                    {{ formatCurrency(booking.vehicle.price_per_day) }}
                                </span
                                ><span>/day</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Bookings State -->
            <div v-else class="text-center py-6">
                <span class="text-gray-500">No bookings found.</span>
            </div>
        </div>
    </MyProfileLayout>
</template>

<style scoped>
/* Existing styles here */
</style>
