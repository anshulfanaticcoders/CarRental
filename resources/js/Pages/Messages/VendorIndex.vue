<script setup>
import { ref, computed, onMounted } from "vue";
import MyProfileLayout from "@/Layouts/MyProfileLayout.vue";
import ChatComponent from '@/Components/ChatComponent.vue';
import axios from 'axios';

const props = defineProps({
    bookings: Array,
});

const searchQuery = ref("");
const selectedBooking = ref(null);
const messages = ref([]);
const otherUser = ref(null);
const loadingChat = ref(false);
// Only adding this for mobile responsiveness
const showChat = ref(false);

const filteredBookings = computed(() => {
    if (!searchQuery.value) return props.bookings;
    const query = searchQuery.value.toLowerCase();
    return props.bookings.filter(booking =>
        booking?.booking_number?.toString().toLowerCase().includes(query) ||
        booking?.vehicle?.brand?.toLowerCase().includes(query) ||
        booking?.vehicle?.model?.toLowerCase().includes(query) ||
        booking?.pickup_location?.toLowerCase().includes(query) ||
        booking?.customer?.user?.first_name?.toLowerCase().includes(query)
    );
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const getStatusClass = (status) => {
    switch (status) {
        case "pending":
            return "bg-yellow-100 text-yellow-800";
        case "confirmed":
            return "bg-green-100 text-green-800";
        case "completed":
            return "bg-blue-100 text-blue-800";
        case "cancelled":
            return "bg-red-100 text-red-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

const loadChat = async (booking) => {
    loadingChat.value = true;
    selectedBooking.value = booking;
    showChat.value = true; // For mobile responsiveness
    
    try {
        const response = await axios.get(`/messages/${booking.id}`);
        
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

const getProfileImage = (customer) => {
  return customer.user.profile && customer.user.profile.avatar ? `${customer.user.profile.avatar}` : '/storage/avatars/default-avatar.svg';
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

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
    
    if (props.bookings && props.bookings.length > 0) {
        loadChat(props.bookings[0]);
    }
});
</script>

<template>
    <MyProfileLayout>
        <div class="flex flex-col sm:flex-row justify-between items-center bg-[#154D6A0D] rounded-[12px] px-[1rem] py-[1rem] mb-[2rem]">
            <p class="text-[1.5rem] text-customPrimaryColor font-bold">Inbox</p>
            <input v-model="searchQuery" type="text" placeholder="Search bookings..."
                class="w-full sm:w-64 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mt-2 sm:mt-0" />
        </div>

        <div v-if="filteredBookings.length === 0" class="text-center py-8">
            <p class="text-gray-500">
                You don't have any bookings yet.
            </p>
        </div>

        <div v-else class="flex flex-col sm:flex-row h-[calc(95vh-200px)]">
            <!-- Chat List -->
            <div :class="{'hidden sm:block sm:w-[35%]': showChat, 'w-full sm:w-[35%]': !showChat}" class="overflow-y-auto border-r">
                <div v-for="booking in filteredBookings" :key="booking.id" 
                    class="border-b cursor-pointer"
                    :class="{'bg-blue-50': selectedBooking && booking.id === selectedBooking.id}"
                    @click="loadChat(booking)">
                    <div class="flex items-center gap-4 w-full py-3 px-3 hover:bg-gray-50">
                        <!-- Display Customer Avatar -->
                        <div class="relative w-[50px] h-[50px] flex-shrink-0">
                            <img :src="getProfileImage(booking.customer)" 
                                alt="Customer Avatar"
                                class="rounded-full object-cover h-full w-full" />
                            <p
                                class="w-[0.85rem] h-[0.85rem] bg-[#009900] rounded-[99px] absolute bottom-0 right-0 border-2">
                            </p>
                        </div>
                        <div class="w-full">
                            <div class="flex justify-between w-full">
                                <span class="text-customDarkBlackColor font-medium text-[1.1rem]">{{
                                    booking.customer?.user?.first_name || "N/A" }}</span>
                                <span class="text-customLightGrayColor text-sm">{{ formatTime(booking.updated_at) }}</span>
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
            <div v-if="!showChat" class="hidden sm:block sm:w-[65%] bg-[#154D6A0D] rounded-tr-[12px] rounded-br-[12px] p-4 flex flex-col">
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

            <!-- Mobile Chat Window -->
            <div v-if="showChat" class="w-full sm:w-[65%] bg-[#154D6A0D] rounded-tr-[12px] rounded-br-[12px] p-4 flex flex-col">
                <button @click="showChat = false" class="sm:hidden text-blue-500 mb-2">Back to Inbox</button>
                
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