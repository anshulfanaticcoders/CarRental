<script setup>
import { ref, computed, onMounted, watch } from "vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import ChatComponent from '@/Components/ChatComponent.vue'; // Import the new component
import axios from 'axios';

const props = defineProps({
    bookings: Array,
});

const searchQuery = ref("");
const selectedBooking = ref(null);
const messages = ref([]);
const otherUser = ref(null);
const loadingChat = ref(false);

const filteredBookings = computed(() => {
    if (!searchQuery.value) return props.bookings;

    const query = searchQuery.value.toLowerCase();

    return props.bookings.filter(booking =>
        booking?.booking_number?.toString().toLowerCase().includes(query) || // Ensure it's a string
        booking?.vehicle?.brand?.toLowerCase().includes(query) ||
        booking?.vehicle?.model?.toLowerCase().includes(query) ||
        booking?.pickup_location?.toLowerCase().includes(query) ||
        booking?.vehicle?.vendor?.first_name?.toLowerCase().includes(query)
    );
});

const loadChat = async (booking) => {
    loadingChat.value = true;
    selectedBooking.value = booking;
    
    try {
        const response = await axios.get(`/messages/${booking.id}`);
        
        // Extract data from the Inertia response props
        if (response.data && response.data.props) {
            messages.value = response.data.props.messages || [];
            otherUser.value = response.data.props.otherUser || null;
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
    } finally {
        loadingChat.value = false;
    }
};

// Format time for last message display
const formatTime = (dateString) => {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Get the last message for a booking
const getLastMessage = async (bookingId) => {
    try {
        const response = await axios.get(`/messages/${bookingId}/last`);
        return response.data.message;
    } catch (error) {
        console.error('Failed to get last message:', error);
        return null;
    }
};

onMounted(() => {
    console.log("Bookings Data:", props.bookings);
    
    // If there are bookings, select the first one by default
    if (props.bookings && props.bookings.length > 0) {
        loadChat(props.bookings[0]);
    }
});
</script>

<template>
    <MyProfileLayout>
        <div class="flex justify-between items-center bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem] mb-[2rem] ">
            <p class="text-[1.5rem] text-customPrimaryColor font-bold">Inbox</p>
            <input v-model="searchQuery" type="text" placeholder="Search bookings..."
                class="w-full sm:w-64 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <div v-if="filteredBookings.length === 0" class="text-center py-8">
            <p class="text-gray-500">
                You don't have any bookings yet.
            </p>
        </div>

        <div v-else class="flex justify-between h-[calc(95vh-200px)]">
            <!-- Chat List -->
            <div class="w-[35%] overflow-y-auto border-r">
                <div v-for="booking in filteredBookings" :key="booking.id" 
                    class="border-b cursor-pointer"
                    :class="{'bg-blue-50': selectedBooking && booking.id === selectedBooking.id}"
                    @click="loadChat(booking)">
                    <div class="flex items-center gap-4 w-full py-3 px-3 hover:bg-gray-50">
                        <!-- Display Vendor Avatar -->
                        <div class="relative w-[50px] h-[50px] flex-shrink-0">
                            <img :src="booking.vehicle?.vendor_profile?.avatar
                                ? `/storage/${booking.vehicle.vendor_profile.avatar}`
                                : '/storage/avatars/default-avatar.svg'" alt="User Avatar"
                                class="rounded-full object-cover h-full w-full" />
                            <p
                                class="w-[0.85rem] h-[0.85rem] bg-[#009900] rounded-[99px] absolute bottom-0 right-0 border-2">
                            </p>
                        </div>
                        <div class="w-full">
                            <div class="flex justify-between w-full">
                                <span class="text-customDarkBlackColor font-medium text-[1.1rem]">{{
                                    booking.vehicle?.vendor?.first_name || "N/A" }}</span>
                                <span class="text-customLightGrayColor text-sm">12:25</span>
                            </div>
                            <div class="flex justify-between">
                                <p class="text-customLightGrayColor text-sm truncate">
                                    {{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}
                                </p>
                                <span 
                                    v-if="booking.unread_count" 
                                    class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                    {{ booking.unread_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Window -->
            <div class="w-[65%] bg-[#154D6A0D] rounded-tr-[12px] rounded-br-[12px] p-4 flex flex-col">
                <div v-if="loadingChat" class="flex-1 flex items-center justify-center">
                    <p>Loading chat...</p>
                </div>
                
                <div v-else-if="!selectedBooking" class="flex-1 flex items-center justify-center">
                    <p class="text-gray-500">Select a conversation to start chatting</p>
                </div>
                
                <ChatComponent 
                    v-else 
                    :booking="selectedBooking" 
                    :messages="messages" 
                    :otherUser="otherUser" 
                    class="h-full"
                />
            </div>
        </div>
    </MyProfileLayout>
</template>