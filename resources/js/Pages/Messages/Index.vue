<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
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
const showChat = ref(false); // Controls mobile chat visibility
const isMobile = ref(false); // Track if we're on mobile

const filteredBookings = computed(() => {
    if (!searchQuery.value) return props.bookings;
    const query = searchQuery.value.toLowerCase();
    return props.bookings.filter(booking =>
        booking?.booking_number?.toString().toLowerCase().includes(query) ||
        booking?.vehicle?.brand?.toLowerCase().includes(query) ||
        booking?.vehicle?.model?.toLowerCase().includes(query) ||
        booking?.pickup_location?.toLowerCase().includes(query) ||
        booking?.vehicle?.vendor?.first_name?.toLowerCase().includes(query) ||
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

const formatTime = (dateString) => {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getProfileImage = (user) => {
    if (user?.profile?.avatar) return user.profile.avatar;
    return '/storage/avatars/default-avatar.svg';
};

const loadChat = async (booking) => {
    loadingChat.value = true;
    selectedBooking.value = booking;
    showChat.value = true; // Show chat view on mobile
    
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

const backToInbox = () => {
    showChat.value = false;
};

// Check if we're on mobile and update the isMobile ref
const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 640;
};

onMounted(() => {
    // Check initial window size
    checkIfMobile();
    
    // Listen for window resize events
    window.addEventListener('resize', checkIfMobile);
    
    // Default to showing the inbox list on mobile when page loads
    if (isMobile.value) {
        showChat.value = false;
    } else if (props.bookings && props.bookings.length > 0) {
        // On desktop, load the first chat by default
        loadChat(props.bookings[0]);
    }
});

onUnmounted(() => {
    // Clean up the event listener
    window.removeEventListener('resize', checkIfMobile);
});
</script>

<template>
        <!-- Main content container -->
        <div class="flex flex-col  h-[calc(100vh-80px)] max-[768px]:mt-[-3rem]">
            <!-- Mobile-friendly inbox header -->
            <div v-if="!showChat || !isMobile" 
                class="flex flex-row justify-between items-center bg-[#154D6A0D] rounded-t-[12px] px-4 py-3">
                <p class="text-lg font-bold text-customPrimaryColor">Inbox</p>
                <input v-model="searchQuery" type="text" placeholder="Search bookings..."
                    class="w-full max-w-[250px] px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <!-- Empty state -->
            <div v-if="filteredBookings.length === 0" class="text-center py-8 flex-grow">
                <p class="text-gray-500">You don't have any bookings yet.</p>
            </div>

            <!-- Chat interface -->
            <div v-else class="flex flex-row h-full overflow-hidden">
                <!-- Chat List - hidden on mobile when chat is open -->
                <div v-show="!showChat || !isMobile" 
                    class="w-full sm:w-1/3 overflow-y-auto border-r bg-white">
                    <div v-for="booking in filteredBookings" :key="booking.id" 
                        class="border-b cursor-pointer"
                        :class="{'bg-blue-50': selectedBooking && booking.id === selectedBooking.id}"
                        @click="loadChat(booking)">
                        <div class="flex items-center gap-4 w-full py-3 px-3 hover:bg-gray-50">
                            <!-- Avatar with online indicator -->
                            <div class="relative w-12 h-12 flex-shrink-0">
                                <img :src="booking.vehicle?.vendor_profile?.avatar || getProfileImage(booking.customer?.user)" 
                                    alt="User Avatar"
                                    class="rounded-full object-cover h-full w-full" />
                                <p class="w-3 h-3 bg-[#009900] rounded-full absolute bottom-0 right-0 border-2 border-white"></p>
                            </div>
                            
                            <!-- User info and last message -->
                            <div class="w-full">
                                <div class="flex justify-between w-full">
                                    <span class="text-gray-900 font-medium">
                                        {{ booking.vehicle?.vendor?.first_name || booking.customer?.user?.first_name || "N/A" }}
                                    </span>
                                    <span class="text-gray-500 text-xs">{{ formatTime(booking.updated_at) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <p class="text-gray-500 text-xs truncate">
                                        {{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}
                                    </p>
                                    <span v-if="booking.unread_count" 
                                        class="bg-blue-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">
                                        {{ booking.unread_count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Window - full screen on mobile when a chat is selected -->
                <div v-show="showChat || !isMobile" 
                    class="w-full sm:w-2/3 bg-gray-100 flex flex-col h-full">
                    
                    <!-- Loading state -->
                    <div v-if="loadingChat" class="flex-1 flex items-center justify-center">
                        <p>Loading chat...</p>
                    </div>
                    
                    <!-- Empty state -->
                    <div v-else-if="!selectedBooking" class="flex-1 flex items-center justify-center">
                        <p class="text-gray-500">Select a conversation to start chatting</p>
                    </div>
                    
                    <!-- Actual chat component with proper mobile layout -->
                    <ChatComponent 
                        v-else 
                        :booking="selectedBooking" 
                        :messages="messages" 
                        :otherUser="otherUser"
                        :showBackButton="isMobile"
                        @back="backToInbox"
                        class="h-full"
                    />
                </div>
            </div>
        </div>
</template>