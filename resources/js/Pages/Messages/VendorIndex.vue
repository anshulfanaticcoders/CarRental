<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import ChatComponent from '@/Components/ChatComponent.vue';
import axios from 'axios';
import { Link, usePage } from '@inertiajs/vue3';
import arrowBackIcon from '../../../assets/arrowBack.svg'; // Assuming path relative to Pages/Messages

const props = defineProps({
    chatPartners: Array, // Changed from bookings
});

const searchQuery = ref("");
const selectedPartner = ref(null); // Changed from selectedBooking
const selectedBookingId = ref(null); // To store the booking_id for ChatComponent
const messages = ref([]);
const otherUser = ref(null); // This will be the user object of the chat partner
const loadingChat = ref(false);
const showChat = ref(false); // Controls mobile chat visibility
const isMobile = ref(false); // Track if we're on mobile

const filteredChatPartners = computed(() => { // Renamed from filteredBookings
    if (!props.chatPartners) return [];
    if (!searchQuery.value) return props.chatPartners;
    const query = searchQuery.value.toLowerCase();
    return props.chatPartners.filter(partner =>
        partner.user?.first_name?.toLowerCase().includes(query) ||
        partner.user?.last_name?.toLowerCase().includes(query) ||
        partner.vehicle_name?.toLowerCase().includes(query) ||
        partner.last_message_preview?.toLowerCase().includes(query)
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
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const formatLastSeen = (chatStatus) => {
    if (!chatStatus) return "Status unavailable";
    if (chatStatus.is_online) return "Online";
    if (!chatStatus.last_logout_at) return "Offline";

    const now = new Date();
    const lastSeenDate = new Date(chatStatus.last_logout_at);
    const diffMs = now - lastSeenDate;
    const diffSecs = Math.round(diffMs / 1000);
    const diffMins = Math.round(diffSecs / 60);
    const diffHours = Math.round(diffMins / 60);
    const diffDays = Math.round(diffHours / 24);

    if (diffSecs < 60) return `Last seen ${diffSecs}s ago`;
    if (diffMins < 60) return `Last seen ${diffMins}m ago`;
    if (diffHours < 24) return `Last seen ${diffHours}h ago`;
    if (diffDays === 1) return `Last seen yesterday`;
    return `Last seen ${diffDays}d ago`;
};


const getProfileImage = (user) => { // Changed parameter from customer to user
    if (user?.profile?.avatar) return user.profile.avatar;
    return '/storage/avatars/default-avatar.svg'; // Ensure this path is correct
};

const loadChat = async (partner) => {
    if (!partner || !partner.latest_booking_id) {
        console.error("Invalid partner or missing booking ID for chat.", partner);
        return;
    }
    loadingChat.value = true;
    selectedPartner.value = partner;
    selectedBookingId.value = partner.latest_booking_id; // Store booking_id
    otherUser.value = partner.user; // Store the other user's details
    showChat.value = true; // Show chat view on mobile

    // Mark messages as read
    if (partner.unread_count > 0) {
        try {
            // console.log(`Marking messages as read for booking ID: ${partner.latest_booking_id}`);
            await axios.post(route('messages.mark-as-read', { locale: usePage().props.locale, booking: partner.latest_booking_id }));
            // Optimistically update the unread count on the client side
            const partnerInList = props.chatPartners.find(p => p.latest_booking_id === partner.latest_booking_id);
            if (partnerInList) {
                partnerInList.unread_count = 0;
            }
            // If the selectedPartner is from filtered list, ensure its unread_count is also updated
            if (selectedPartner.value && selectedPartner.value.latest_booking_id === partner.latest_booking_id) {
                 selectedPartner.value.unread_count = 0;
            }
        } catch (error) {
            console.error('Failed to mark messages as read:', error);
            // Optionally, handle the error, e.g., by not clearing the count or showing a notification
        }
    }
    
    try {
        // The ChatComponent will use selectedBookingId.value for its operations.
        // The messages are fetched by ChatComponent itself or passed if already loaded.
        // For now, we assume ChatComponent handles fetching messages based on booking_id.
        // If ChatComponent expects messages prop, we need to fetch them here.
        // The existing ChatComponent seems to get messages via /messages/{booking.id}
        // Let's keep the existing logic for fetching messages for now.
        const response = await axios.get(route('messages.show', { locale: usePage().props.locale, booking: partner.latest_booking_id }));
        
        if (response.data && response.data.props) {
            messages.value = response.data.props.messages || [];
            // otherUser is already set from partner.user
            // We might need to ensure response.data.props.otherUser matches partner.user structure
            // or rely on the partner.user passed.
            // For consistency, let's use the one from the partner object.
        } else {
            messages.value = [];
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
        messages.value = []; // Clear messages on error
    } finally {
        loadingChat.value = false;
    }
};


const backToInbox = () => {
    showChat.value = false;
    selectedPartner.value = null; // Clear selected partner
    selectedBookingId.value = null;
};

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768; // Adjusted breakpoint for sm/md
};

const handleMessageReceived = (message) => {
    const partner = props.chatPartners.find(p => p.user.id === message.sender_id);
    if (partner) {
        partner.last_message_preview = message.message;
        partner.last_message_at = message.created_at;
        if (selectedPartner.value?.user.id !== message.sender_id) {
            partner.unread_count++;
        }
    }
};

onMounted(() => {
    // console.log("Chat Partners Data:", props.chatPartners);
    
    checkIfMobile();
    window.addEventListener('resize', checkIfMobile);
    
    if (isMobile.value) {
        showChat.value = false;
    } else if (props.chatPartners && props.chatPartners.length > 0) {
        loadChat(props.chatPartners[0]);
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', checkIfMobile);
});
</script>

<template>
    <div class="flex flex-col h-screen bg-gray-100">
        <!-- New Page Header -->
        <header class="bg-white shadow-sm p-3 flex items-center justify-between flex-shrink-0 border-b">
            <div class="flex items-center">
                <Link :href="route('profile.edit', { locale: usePage().props.locale })" class="mr-3 p-1.5 rounded-full hover:bg-gray-100">
                    <img :src="arrowBackIcon" alt="Back to Profile" class="w-5 h-5" />
                </Link>
                <h1 class="text-lg font-semibold text-gray-800">Inbox</h1>
            </div>
            <!-- Placeholder for any right-side header actions if needed -->
        </header>

        <!-- Main Chat Area -->
        <div class="flex flex-row flex-grow overflow-hidden">
            <!-- Chat List (Left Panel) -->
            <div v-if="!showChat || !isMobile"
                 class="w-full md:w-1/3 lg:w-1/4 border-r bg-white flex flex-col flex-shrink-0">
                <!-- Search bar for chat list -->
                <div class="p-3 border-b">
                    <input v-model="searchQuery" type="text" placeholder="Search chats..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
                </div>
                <!-- Scrollable list of partners -->
                <div class="overflow-y-auto flex-grow">
                    <div v-if="!filteredChatPartners || filteredChatPartners.length === 0 && !loadingChat" class="text-center py-10">
                        <p class="text-gray-500">No conversations found.</p>
                    </div>
                    <div v-else v-for="partner in filteredChatPartners" :key="partner.user.id"
                        class="border-b cursor-pointer"
                        :class="{'bg-blue-50': selectedPartner && partner.user.id === selectedPartner.user.id}"
                        @click="loadChat(partner)">
                        <div class="flex items-start gap-3 w-full py-3 px-3 hover:bg-gray-50">
                            <div class="relative w-10 h-10 flex-shrink-0">
                                <img :src="getProfileImage(partner.user)"
                                    :alt="partner.user.first_name"
                                    class="rounded-full object-cover h-full w-full" />
                                <span v-if="partner.user?.chat_status?.is_online"
                                      class="w-3 h-3 bg-green-500 rounded-full absolute bottom-0 right-0 border-2 border-white"
                                      title="Online"></span>
                                <span v-else
                                      class="w-3 h-3 bg-gray-400 rounded-full absolute bottom-0 right-0 border-2 border-white"
                                      title="Offline"></span>
                            </div>

                            <div class="w-full overflow-hidden">
                                <div class="flex justify-between items-center w-full">
                                    <span class="text-gray-800 font-semibold text-sm truncate" :title="`${partner.user.first_name} ${partner.user.last_name || ''}`">
                                        {{ partner.user.first_name }} {{ partner.user.last_name || '' }}
                                    </span>
                                    <span class="text-gray-500 text-xs flex-shrink-0 ml-2">{{ formatTime(partner.last_message_at) }}</span>
                                </div>
                                <p class="text-gray-500 text-xs truncate mt-0.5" :title="formatLastSeen(partner.user?.chat_status)">
                                    {{ formatLastSeen(partner.user?.chat_status) }}
                                </p>
                                <div class="flex justify-between items-end mt-1">
                                    <p class="text-gray-600 text-xs truncate pr-2" :title="partner.last_message_preview">
                                        {{ partner.last_message_preview }}
                                    </p>
                                    <span v-if="partner.unread_count > 0"
                                        class="bg-blue-600 text-white rounded-full min-w-[20px] h-5 flex items-center justify-center text-xs px-1.5">
                                        {{ partner.unread_count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Component (Right Panel / Full Mobile) -->
            <div v-if="showChat || !isMobile"
                 class="w-full md:flex-grow bg-gray-50 flex flex-col">

                <div v-if="loadingChat" class="flex-1 flex items-center justify-center">
                    <p class="text-gray-600">Loading chat...</p>
                </div>

                <div v-else-if="!selectedPartner" class="flex-1 flex items-center justify-center">
                    <p class="text-gray-500">Select a conversation to start chatting.</p>
                </div>

                <ChatComponent
                    v-else
                    :bookingId="selectedBookingId"
                    :messages="messages"
                    :otherUser="otherUser"
                    :showBackButton="isMobile"
                    @back="backToInbox"
                    @messageSent="messages.push($event)"
                    @messageReceived="handleMessageReceived"
                    class="flex-grow"
                />
            </div>
        </div>
    </div>
</template>
