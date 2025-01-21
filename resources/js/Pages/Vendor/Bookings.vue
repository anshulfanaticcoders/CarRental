<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { ref} from 'vue';

const props = defineProps({
    bookings: {
        type: Array,
        required: true
    }
});

const error = ref(null);
const loading = ref(false);

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return `${String(date.getMonth() + 1).padStart(2, '0')}/${String(date.getDate()).padStart(2, '0')}/${date.getFullYear()}`;
};

const cancelBooking = async (bookingId) => {
    if (confirm('Are you sure you want to cancel this booking?')) {
        try {
            await axios.post(`/api/bookings/${bookingId}/cancel`);
            window.location.reload();
        } catch (err) {
            error.value = "Failed to cancel booking. Please try again.";
            console.error("Error canceling booking:", err);
        }
    }
};
</script>

<template>
    <MyProfileLayout>
        <div class="p-6 bg-white rounded shadow-md">
            <h1 class="text-xl font-semibold mb-4">Booking Details</h1>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-6">
                <span class="text-gray-500">Loading booking details...</span>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="text-center py-6 text-red-600">
                {{ error }}
            </div>

            <!-- Bookings Table -->
            <div v-else-if="bookings.length" class="overflow-x-auto">
                <table class="min-w-full border border-gray-200">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Booking ID</th>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Customer Name</th>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Vehicle</th>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Booking Date</th>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Return Date</th>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Payment Status</th>
                            <th class="px-4 py-2 text-left text-sm font-bold text-customPrimaryColor">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="booking in bookings" 
                            :key="booking.id" 
                            class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm text-gray-700">#{{ booking.booking_number }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ booking.customer?.first_name }} {{ booking.customer?.last_name }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">
                                {{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ formatDate(booking.pickup_date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ formatDate(booking.return_date) }}</td>
                            <td class="px-4 py-2 text-sm">
                                <span :class="{
                                    'text-green-600 font-semibold capitalize': booking.booking_status === 'completed',
                                    'text-green-600 font-semibold capitalize': booking.booking_status === 'confirmed',
                                    'text-yellow-500 font-semibold capitalize': booking.booking_status === 'pending',
                                    'text-[#EE1D52] font-semibold capitalize': booking.booking_status === 'cancelled'
                                }">
                                    {{ booking.booking_status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-sm">
                                <button 
                                    v-if="booking.booking_status !== 'cancelled'"
                                    class="text-red-600 font-semibold hover:underline"
                                    @click="cancelBooking(booking.id)"
                                >
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- No Bookings State -->
            <div v-else class="text-center py-6">
                <span class="text-gray-500">No bookings found.</span>
            </div>
        </div>
    </MyProfileLayout>
</template>

<style scoped>
/* Your existing styles remain the same */
</style>