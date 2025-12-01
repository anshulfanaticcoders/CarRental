<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import ChatComponent from '@/Components/ChatComponent.vue';
import BookingSelectionModal from '@/Components/BookingSelectionModal.vue';
import axios from 'axios';
import { Link, usePage, Head  } from '@inertiajs/vue3';
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
const showRecentChats = ref(false); // Toggle between active and recent chats

// NEW: Booking selection modal state
const showBookingSelectionModal = ref(false);
const availableBookings = ref([]);

const activeChatPartners = computed(() => {
    if (!props.chatPartners) return [];
    return props.chatPartners.filter(partner => partner.active_bookings_count > 0);
});

const recentChatPartners = computed(() => {
    if (!props.chatPartners) return [];

    // FIXED: Show partners with completed bookings, regardless of whether they also have active bookings
    return props.chatPartners.filter(partner => partner.completed_bookings_count > 0);
});

const filteredChatPartners = computed(() => {
    const partners = showRecentChats.value ? recentChatPartners.value : activeChatPartners.value;
    if (!searchQuery.value) return partners;
    const query = searchQuery.value.toLowerCase();
    return partners.filter(partner =>
        partner.user?.first_name?.toLowerCase().includes(query) ||
        partner.user?.last_name?.toLowerCase().includes(query) ||
        partner.vehicle?.name?.toLowerCase().includes(query) ||
        partner.vehicle?.brand?.toLowerCase().includes(query) ||
        partner.vehicle?.model?.toLowerCase().includes(query) ||
        partner.vehicle?.category?.toLowerCase().includes(query) ||
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

const getBookingStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'confirmed': return 'bg-green-100 text-green-800';
        case 'completed': return 'bg-gray-100 text-gray-600';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-600';
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

const getVehicleImage = (partner) => {
    if (partner.vehicle?.image) {
        return partner.vehicle.image;
    }
    // Fallback to a placeholder or default vehicle image
    return '/images/vehicles/default-vehicle.jpg';
};

const getBookingStatusBadgeClass = (status) => {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'confirmed': return 'bg-green-100 text-green-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
};

const loadChat = async (partner) => {
    if (!partner) {
        console.error("Invalid partner for chat.", partner);
        return;
    }

    selectedPartner.value = partner;
    otherUser.value = partner.user; // Store the other user's details

    // Determine which booking ID to use based on current tab
    let bookingIdToUse;
    if (showRecentChats.value) {
        // In Recent tab, use the latest completed booking
        bookingIdToUse = partner.latest_completed_booking_id;
    } else {
        // In Active tab, use the active booking
        bookingIdToUse = partner.active_booking_id;
    }

    // Check if partner has multiple active bookings (only for Active tab)
    if (!showRecentChats.value && partner.has_multiple_active_bookings && partner.bookings && partner.bookings.length > 1) {
        // Show booking selection modal
        showBookingSelectionModal.value = true;
        availableBookings.value = partner.bookings;
        return;
    }

    // Load the appropriate booking
    loadChatForBooking(bookingIdToUse, partner);
};

// NEW: Load chat for specific booking
const loadChatForBooking = async (bookingId, partner) => {
    if (!bookingId) {
        console.error("Invalid booking ID for chat.", bookingId);
        return;
    }

    loadingChat.value = true;
    selectedBookingId.value = bookingId;
    showChat.value = true;

    // Mark messages as read
    if (partner && partner.unread_count > 0) {
        try {
            await axios.post(route('messages.mark-as-read', { locale: usePage().props.locale, booking: bookingId }));
            // Optimistically update the unread count on the client side
            const partnerInList = props.chatPartners.find(p => p.user.id === partner.user.id);
            if (partnerInList) {
                partnerInList.unread_count = 0;
            }
            if (selectedPartner.value) {
                 selectedPartner.value.unread_count = 0;
            }
        } catch (error) {
            console.error('Failed to mark messages as read:', error);
        }
    }

    try {
        const response = await axios.get(route('messages.show', { locale: usePage().props.locale, booking: bookingId }));
        if (response.data && response.data.props) {
            messages.value = response.data.props.messages || [];

            // Set booking restrictions from response
            if (response.data.props.booking && selectedPartner.value) {
                const booking = response.data.props.booking;

                // Only disable messaging for completed/cancelled bookings, regardless of tab
                const isCompletedBooking = !['pending', 'confirmed'].includes(booking.booking_status);

                selectedPartner.value.booking.chat_restrictions = {
                    can_send_messages: !isCompletedBooking,
                    reason: isCompletedBooking
                        ? 'Chat is not available for ' + booking.booking_status + ' bookings'
                        : null,
                    read_only: isCompletedBooking
                };
            }
        } else {
            messages.value = [];
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
        messages.value = [];
    } finally {
        loadingChat.value = false;
    }
};

// NEW: Handle booking selection from modal
const handleBookingSelection = (selectedBooking) => {
    showBookingSelectionModal.value = false;
    loadChatForBooking(selectedBooking.id, selectedPartner.value);
};

// NEW: Handle booking change from ChatComponent dropdown
const handleBookingChange = async (newBooking) => {
    if (!selectedPartner.value || newBooking.id === selectedBookingId.value) {
        return;
    }

    // Update selected booking ID
    selectedBookingId.value = newBooking.id;

    // Update selected partner's booking details
    if (selectedPartner.value.bookings) {
        const bookingDetails = selectedPartner.value.bookings.find(b => b.id === newBooking.id);
        if (bookingDetails) {
            selectedPartner.value.booking = bookingDetails;

            // Set chat restrictions for the new booking
            const allowedStatuses = ['pending', 'confirmed'];
            selectedPartner.value.booking.chat_restrictions = {
                can_send_messages: allowedStatuses.includes(bookingDetails.booking_status),
                reason: !allowedStatuses.includes(bookingDetails.booking_status)
                    ? 'Chat is not available for ' + bookingDetails.booking_status + ' bookings'
                    : null,
                read_only: !allowedStatuses.includes(bookingDetails.booking_status)
            };
        }
    }

    // Load messages for the new booking
    try {
        loadingChat.value = true;
        const response = await axios.get(route('messages.show', { locale: usePage().props.locale, booking: newBooking.id }));
        if (response.data && response.data.props) {
            messages.value = response.data.props.messages || [];
        } else {
            messages.value = [];
        }
    } catch (error) {
        console.error('Failed to load messages for booking:', error);
        messages.value = [];
    } finally {
        loadingChat.value = false;
    }
};


const backToInbox = () => {
    showChat.value = false;
    showBookingSelectionModal.value = false; // Also close modal if open
    selectedPartner.value = null; // Clear selected partner
    selectedBookingId.value = null;
    showRecentChats.value = false; // Reset to active chats when going back
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
    } else if (activeChatPartners.value.length > 0) {
        loadChat(activeChatPartners.value[0]); // Prioritize active chats
    } else if (recentChatPartners.value.length > 0) {
        showRecentChats.value = true;
        loadChat(recentChatPartners.value[0]); // Fall back to recent chats
    }
});

onUnmounted(() => {
    window.removeEventListener('resize', checkIfMobile);
});
</script>

<template>
    <Head>
        <meta name="robots" content="noindex, nofollow">
        <title>Inbox</title>
    </Head>
    <div class="flex flex-col h-screen bg-gray-100 message-box">
        <!-- New Page Header -->

        <!-- Main Chat Area -->
        <div class="flex flex-row flex-grow overflow-hidden">
            <!-- Chat List (Left Panel) -->
            <div v-if="!showChat || !isMobile"
                 class="w-full md:w-1/3 lg:w-1/4 border-r bg-white flex flex-col flex-shrink-0">
                <!-- Active/Recent Chats Toggle -->
                <div class="p-3 border-b bg-gray-50">
                    <div class="flex space-x-2">
                        <button @click="showRecentChats = false"
                                :class="{'bg-blue-600 text-white': !showRecentChats, 'bg-gray-200 text-gray-700': showRecentChats}"
                                class="flex-1 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            Active ({{ activeChatPartners.length }})
                        </button>
                        <button @click="showRecentChats = true"
                                :class="{'bg-blue-600 text-white': showRecentChats, 'bg-gray-200 text-gray-700': !showRecentChats}"
                                class="flex-1 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                            Recent ({{ recentChatPartners.length }})
                        </button>
                    </div>
                </div>
                <!-- Search bar for chat list -->
                <div class="p-3 border-b">
                    <input v-model="searchQuery" type="text" :placeholder="showRecentChats ? 'Search recent chats...' : 'Search active chats...'"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" />
                </div>
                <!-- Scrollable list of partners -->
                <div class="overflow-y-auto flex-grow">
                    <div v-if="!filteredChatPartners || filteredChatPartners.length === 0 && !loadingChat" class="text-center py-10">
                        <p class="text-gray-500">
                            {{ showRecentChats ? 'No recent conversations found.' : 'No active conversations found.' }}
                        </p>
                        <p v-if="showRecentChats && activeChatPartners.length > 0" class="text-gray-400 text-sm mt-2">
                            You have {{ activeChatPartners.length }} active conversation{{ activeChatPartners.length !== 1 ? 's' : '' }}
                        </p>
                        <p v-else-if="!showRecentChats && recentChatPartners.length > 0" class="text-gray-400 text-sm mt-2">
                            You have {{ recentChatPartners.length }} recent conversation{{ recentChatPartners.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <div v-else v-for="partner in filteredChatPartners" :key="partner.user.id"
                        class="border-b cursor-pointer hover:bg-gray-50 transition-colors"
                        :class="{'bg-blue-50': selectedPartner && partner.user.id === selectedPartner.user.id}"
                        @click="loadChat(partner)">

                        <!-- Enhanced Partner Card -->
                        <div class="flex gap-4 w-full py-4 px-4">
                            <!-- Vehicle Image with Status Badge -->
                            <div class="relative flex-shrink-0">
                                <img :src="getVehicleImage(partner)"
                                    :alt="partner.vehicle?.name || 'Vehicle'"
                                    class="w-16 h-16 rounded-xl object-cover shadow-sm" />

                                <!-- Booking Status Badge -->
                                <div v-if="partner.booking?.status"
                                     class="absolute -top-1 -right-1 w-6 h-6 rounded-full flex items-center justify-center text-xs"
                                     :class="getBookingStatusBadgeClass(partner.booking.status)">
                                    {{ getBookingStatusIcon(partner.booking.status) }}
                                </div>
                            </div>

                            <!-- Detailed Chat Information -->
                            <div class="flex-1 min-w-0">
                                <!-- Header Row: Customer Name + Timestamp + Unread -->
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <!-- Customer Avatar -->
                                        <img :src="getProfileImage(partner.user)"
                                            :alt="partner.user.first_name"
                                            class="w-8 h-8 rounded-full object-cover ring-2 ring-white" />

                                        <!-- Customer Name with Online Status -->
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-gray-900 truncate">
                                                    {{ partner.user.first_name }} {{ partner.user.last_name || '' }}
                                                </span>
                                                <div class="w-2 h-2 rounded-full"
                                                     :class="partner.user?.chat_status?.is_online ? 'bg-green-500' : 'bg-gray-400'">
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500">
                                                {{ formatLastSeen(partner.user?.chat_status) }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="text-xs text-gray-500">
                                            {{ formatTime(partner.last_message_at) }}
                                        </span>
                                        <span v-if="partner.unread_count > 0"
                                            class="bg-blue-600 text-white rounded-full min-w-[24px] h-6 flex items-center justify-center text-xs font-medium px-2">
                                            {{ partner.unread_count }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Vehicle Details -->
                                <div class="mb-2">
                                    <h4 class="font-medium text-gray-900 text-sm mb-1">
                                        {{ partner.vehicle?.name }}
                                    </h4>
                                    <div class="flex items-center gap-3 text-xs text-gray-600">
                                        <span>{{ partner.vehicle?.brand }} {{ partner.vehicle?.model }}</span>
                                        <span>•</span>
                                        <span>{{ partner.vehicle?.category }}</span>
                                    </div>
                                </div>

                                <!-- Booking Information -->
                                <div v-if="partner.booking" class="mb-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <!-- NEW: Recent Tab Indicator for Completed Bookings -->
                                            <span v-if="showRecentChats && partner.completed_bookings_count > 0"
                                                  class="text-xs px-2 py-1 rounded-full font-medium bg-gray-100 text-gray-700 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                {{ partner.completed_bookings_count }} completed
                                            </span>
                                            <span v-else :class="`text-xs px-2 py-1 rounded-full font-medium ${getBookingStatusColor(partner.booking.status)}`">
                                                {{ getBookingStatusIcon(partner.booking.status) }}
                                                {{ partner.booking.status?.charAt(0).toUpperCase() + partner.booking.status?.slice(1) }}
                                            </span>
                                            <!-- Chat Disabled Indicator -->
                                            <span v-if="partner.booking.chat_allowed === false" class="text-xs text-orange-600 flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                Chat disabled
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Booking Dates (for active bookings) -->
                                    <div v-if="partner.booking.status === 'pending' || partner.booking.status === 'confirmed'"
                                         class="flex items-center gap-2 mt-1 text-xs text-gray-600">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        <span>{{ partner.booking.pickup_date ? formatDate(partner.booking.pickup_date) : 'Date not available' }} - {{ partner.booking.return_date ? formatDate(partner.booking.return_date) : 'Date not available' }}</span>
                                    </div>
                                </div>

                                <!-- Last Message Preview -->
                                <div class="flex items-start justify-between">
                                    <p class="text-sm text-gray-600 truncate pr-2 flex-1" :title="partner.last_message_preview">
                                        {{ partner.last_message_preview || 'No messages yet' }}
                                    </p>
                                </div>

                                <!-- Booking Count Indicators -->
                                <div class="flex gap-2 mt-2">
                                    <span v-if="partner.active_bookings_count > 0"
                                        class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                                        {{ partner.active_bookings_count }} active
                                    </span>
                                    <span v-if="partner.completed_bookings_count > 0"
                                        class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full">
                                        {{ partner.completed_bookings_count }} completed
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
                    :bookingDetails="selectedPartner?.booking"
                    :allBookings="Array.isArray(selectedPartner?.bookings) ? selectedPartner.bookings : []"
                    :bookingRestrictions="selectedPartner?.booking?.chat_restrictions || { can_send_messages: true, reason: null, read_only: false }"
                    :showBackButton="isMobile"
                    @back="backToInbox"
                    @messageSent="messages.push($event)"
                    @messageReceived="handleMessageReceived"
                    @bookingChanged="handleBookingChange"
                    class="flex-grow"
                />
            </div>
        </div>

        <!-- Booking Selection Modal -->
        <BookingSelectionModal
            :isVisible="showBookingSelectionModal"
            :bookings="availableBookings"
            :selectedPartner="selectedPartner"
            @close="showBookingSelectionModal = false"
            @booking-selected="handleBookingSelection"
        />
    </div>
</template>

<style>
@media screen and (max-width:768px) {
.message-box{
    padding-bottom: 4rem;
}
}
</style>
