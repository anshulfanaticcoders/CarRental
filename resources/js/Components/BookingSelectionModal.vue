<script setup>
import { ref, computed, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    isVisible: {
        type: Boolean,
        default: false
    },
    bookings: {
        type: Array,
        required: true
    },
    selectedPartner: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'booking-selected']);

const searchQuery = ref('');
const selectedBookingId = ref(null);

// Filter bookings based on search query
const filteredBookings = computed(() => {
    if (!searchQuery.value.trim()) return props.bookings;

    const query = searchQuery.value.toLowerCase();
    return props.bookings.filter(booking =>
        booking.vehicle.name.toLowerCase().includes(query) ||
        booking.vehicle.brand.toLowerCase().includes(query) ||
        booking.vehicle.model.toLowerCase().includes(query) ||
        booking.vehicle.category.toLowerCase().includes(query)
    );
});

// Format date for display
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

// Get status color and icon
const getBookingStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'confirmed': return 'bg-green-100 text-green-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const getBookingStatusIcon = (status) => {
    switch (status) {
        case 'pending': return '🕐';
        case 'confirmed': return '✅';
        case 'completed': return '🎉';
        case 'cancelled': return '❌';
        default: return '📅';
    }
};

// Handle booking selection
const selectBooking = (booking) => {
    selectedBookingId.value = booking.id;
};

// Confirm booking selection
const confirmSelection = () => {
    const selectedBooking = props.bookings.find(b => b.id === selectedBookingId.value);
    if (selectedBooking) {
        emit('booking-selected', selectedBooking);
    }
};

// Handle escape key and backdrop click
const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        emit('close');
    }
};

const handleBackdropClick = (event) => {
    if (event.target === event.currentTarget) {
        emit('close');
    }
};

// Reset state when modal opens/closes
watch(() => props.isVisible, (newValue) => {
    if (newValue) {
        searchQuery.value = '';
        selectedBookingId.value = null;
        // Auto-select first booking if available
        if (props.bookings.length > 0) {
            selectedBookingId.value = props.bookings[0].id;
        }
        // Add event listener
        document.addEventListener('keydown', handleKeydown);
    } else {
        // Remove event listener
        document.removeEventListener('keydown', handleKeydown);
    }
});
</script>

<template>
    <teleport to="body">
        <div v-if="isVisible"
             class="fixed inset-0 z-50 flex items-center justify-center"
             @click="handleBackdropClick">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black bg-opacity-50 transition-opacity"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-xl shadow-xl max-w-4xl w-full mx-2 sm:mx-4 md:mx-auto max-h-[90vh] sm:max-h-[85vh] md:max-h-[80vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white p-4 sm:p-6">
                    <div class="flex items-start sm:items-center justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl sm:text-2xl font-bold leading-tight">Select Booking</h2>
                            <p class="text-blue-100 mt-1 text-sm sm:text-base leading-snug">
                                Choose which booking you'd like to discuss with {{ selectedPartner?.user?.first_name }} {{ selectedPartner?.user?.last_name }}
                            </p>
                        </div>
                        <button @click="emit('close')"
                                class="text-white hover:text-gray-200 transition-colors p-2 hover:bg-white/10 rounded-lg flex-shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="p-3 sm:p-4 border-b bg-gray-50">
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 sm:w-5 sm:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input v-model="searchQuery"
                               type="text"
                               placeholder="Search by vehicle name, brand, model..."
                               class="w-full pl-9 sm:pl-10 pr-4 py-2.5 sm:py-3 text-sm sm:text-base border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Booking List -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div v-if="filteredBookings.length === 0" class="text-center py-12">
                        <div class="text-gray-400 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-1.618 0-3.095-.607-4.212-1.604M12 4a8 8 0 100 16 0 8 8 0 000-16z"></path>
                            </svg>
                        </div>
                        <p class="text-gray-500 text-lg">No bookings found</p>
                        <p class="text-gray-400 text-sm mt-2">Try adjusting your search terms</p>
                    </div>

                    <div v-else class="space-y-3 sm:space-y-4">
                        <div v-for="booking in filteredBookings"
                             :key="booking.id"
                             @click="selectBooking(booking)"
                             :class="[
                                 'border rounded-lg p-3 sm:p-4 cursor-pointer transition-all duration-200 active:scale-98',
                                 selectedBookingId === booking.id
                                     ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-200'
                                     : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
                             ]">
                            <div class="flex gap-3 sm:gap-4">
                                <!-- Vehicle Image -->
                                <div class="flex-shrink-0">
                                    <img v-if="booking.vehicle.image"
                                         :src="booking.vehicle.image"
                                         :alt="booking.vehicle.name"
                                         class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-lg">
                                    <div v-else
                                         class="w-16 h-16 sm:w-20 sm:h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <svg class="w-6 h-6 sm:w-8 sm:h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Booking Details -->
                                <div class="flex-1 min-w-0">
                                    <!-- Vehicle Info -->
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-semibold text-gray-900 text-base sm:text-lg truncate">{{ booking.vehicle.name }}</h3>
                                            <p class="text-gray-600 text-xs sm:text-sm">{{ booking.vehicle.brand }} {{ booking.vehicle.model }}</p>
                                            <p class="text-gray-500 text-xs">{{ booking.vehicle.category }}</p>
                                        </div>
                                        <!-- Status Badge -->
                                        <span :class="`px-2 py-1 rounded-full text-xs font-medium flex-shrink-0 ${getBookingStatusColor(booking.booking_status)}`">
                                            {{ getBookingStatusIcon(booking.booking_status) }} {{ booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1) }}
                                        </span>
                                    </div>

                                    <!-- Booking Dates -->
                                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-2">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>{{ formatDate(booking.pickup_date) }}</span>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                        </svg>
                                        <span>{{ formatDate(booking.return_date) }}</span>
                                    </div>

                                    <!-- Times -->
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Pickup: {{ booking.pickup_time }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Return: {{ booking.return_time }}
                                        </div>
                                    </div>

                                    <!-- Price -->
                                    <div class="mt-2 flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Total Amount:</span>
                                        <span class="text-lg font-semibold text-gray-900">${{ Number(booking.total_amount).toFixed(2) }}</span>
                                    </div>
                                </div>

                                <!-- Selection Indicator -->
                                <div class="flex-shrink-0">
                                    <div :class="[
                                        'w-5 h-5 rounded-full border-2 flex items-center justify-center',
                                        selectedBookingId === booking.id
                                            ? 'border-blue-500 bg-blue-500'
                                            : 'border-gray-300'
                                    ]">
                                        <svg v-if="selectedBookingId === booking.id" class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 border-t p-3 sm:p-4">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                        <p class="text-sm text-gray-600 order-2 sm:order-1">
                            {{ filteredBookings.length }} booking{{ filteredBookings.length !== 1 ? 's' : '' }} available
                        </p>
                        <div class="flex gap-2 sm:gap-3 order-1 sm:order-2">
                            <button @click="emit('close')"
                                    class="flex-1 sm:flex-none px-3 sm:px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors text-sm sm:text-base">
                                Cancel
                            </button>
                            <PrimaryButton
                                @click="confirmSelection"
                                :disabled="!selectedBookingId"
                                class="flex-1 sm:flex-none px-3 sm:px-6 py-2 text-sm sm:text-base">
                                Start Chat
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<style scoped>
/* Custom scrollbar for modal content */
.overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Mobile responsive improvements */
@media (max-width: 640px) {
    /* Full-screen modal on very small screens */
    .fixed.inset-0 .bg-white {
        margin: 8px;
        max-height: calc(100vh - 16px);
        border-radius: 12px;
    }
}

@media (max-width: 480px) {
    /* Full-screen modal on tiny screens */
    .fixed.inset-0 .bg-white {
        margin: 0;
        max-height: 100vh;
        border-radius: 0;
    }
}

/* Tablet responsiveness */
@media (min-width: 768px) and (max-width: 1024px) {
    /* Slightly smaller modal on tablets */
    .bg-white {
        max-width: 90vw;
    }
}

/* Touch-friendly improvements */
@media (hover: none) and (pointer: coarse) {
    /* Better touch targets for mobile devices */
    button {
        min-height: 44px;
    }

    .cursor-pointer {
        min-height: 44px;
    }
}

/* Prevent horizontal scrolling on mobile */
@media (max-width: 640px) {
    body {
        overflow-x: hidden;
    }
}
</style>