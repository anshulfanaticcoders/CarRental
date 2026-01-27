<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import ChatComponent from '@/Components/ChatComponent.vue';
import BookingSelectionModal from '@/Components/BookingSelectionModal.vue';
import BookingInsightsPanel from '@/Components/BookingInsightsPanel.vue';
import axios from 'axios';
import { Link, usePage, Head } from '@inertiajs/vue3';
import arrowBackIcon from '../../../assets/arrowBack.svg'; // Assuming path relative to Pages/Messages

const props = defineProps({
    chatPartners: Array, // Changed from bookings
});

const searchQuery = ref("");
const selectedPartner = ref(null); // Changed from selectedBooking
const selectedBookingId = ref(null); // To store the booking_id for ChatComponent
const messages = ref([]);
const otherUser = ref(null); // This will be the user object of the chat partner (vendor)
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
        partner.user?.first_name?.toLowerCase().includes(query) || // Vendor's first name
        partner.user?.last_name?.toLowerCase().includes(query) ||  // Vendor's last name
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

    // Parse the date string and handle UTC properly
    const date = new Date(dateString);

    // Check if the date is valid
    if (isNaN(date.getTime())) {
        return '';
    }

    // Format using user's local timezone
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
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

const getProfileImage = (user) => { // User here is the vendor
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
    otherUser.value = partner.user; // This is the vendor user object

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

    // Note: markMessagesAsRead is now handled in ChatComponent to ensure proper WebSocket timing

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

// Handle messages read event from ChatComponent
const handleMessagesRead = (data) => {
    // Update unread count in chat partners list
    if (selectedPartner.value && selectedPartner.value.user) {
        selectedPartner.value.unread_count = 0;
    }
};

const backToInbox = () => {
    showChat.value = false;
    showBookingSelectionModal.value = false; // Also close modal if open
    selectedPartner.value = null;
    selectedBookingId.value = null;
    showRecentChats.value = false; // Reset to active chats when going back
};

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768; // Adjusted breakpoint
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

const normalizeBookingId = (bookingId) => {
    if (!bookingId) return null;
    const parsedId = Number(bookingId);
    return Number.isNaN(parsedId) ? bookingId : parsedId;
};

const findPartnerByBookingId = (bookingId) => {
    if (!bookingId || !props.chatPartners) return null;

    return props.chatPartners.find((partner) => {
        if (partner.active_booking_id == bookingId || partner.latest_completed_booking_id == bookingId || partner.latest_booking_id == bookingId) {
            return true;
        }

        if (Array.isArray(partner.bookings)) {
            return partner.bookings.some((booking) => booking.id == bookingId);
        }

        return false;
    });
};

onMounted(() => {
      checkIfMobile();
    window.addEventListener('resize', checkIfMobile);

    const page = usePage();
    const urlParams = new URLSearchParams(window.location.search);
    const bookingIdFromUrl = normalizeBookingId(urlParams.get('booking_id'));
    const vendorIdFromUrl = urlParams.get('vendor_id');

    if (bookingIdFromUrl && props.chatPartners) {
        const targetPartner = findPartnerByBookingId(bookingIdFromUrl);
        if (targetPartner) {
            if (targetPartner.latest_completed_booking_id == bookingIdFromUrl && targetPartner.active_booking_id != bookingIdFromUrl) {
                showRecentChats.value = true;
            }
            selectedPartner.value = targetPartner;
            otherUser.value = targetPartner.user;
            loadChatForBooking(bookingIdFromUrl, targetPartner);
            return;
        }
    }

    if (vendorIdFromUrl && props.chatPartners) {
        const targetPartner = props.chatPartners.find(partner => partner.user?.id.toString() === vendorIdFromUrl);
        if (targetPartner) {
            // If the target partner has only completed bookings, show recent chats
            if (targetPartner.active_bookings_count === 0 && targetPartner.completed_bookings_count > 0) {
                showRecentChats.value = true;
            }
            loadChat(targetPartner);
        } else if (activeChatPartners.value.length > 0 && !isMobile.value) {
            loadChat(activeChatPartners.value[0]); // Fallback to first active chat
        } else if (recentChatPartners.value.length > 0 && !isMobile.value) {
            showRecentChats.value = true;
            loadChat(recentChatPartners.value[0]); // Fallback to first recent chat
        }
    } else if (activeChatPartners.value.length > 0 && !isMobile.value) {
        loadChat(activeChatPartners.value[0]); // Default to first active chat
    } else if (recentChatPartners.value.length > 0 && !isMobile.value) {
        showRecentChats.value = true;
        loadChat(recentChatPartners.value[0]); // Default to first recent chat if no active ones
    }

    if (isMobile.value && !selectedPartner.value) { // Ensure chat doesn't auto-show on mobile unless a partner is selected
        showChat.value = false;
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
    <div class="concierge-shell">
        <div class="concierge-layout">
            <aside v-if="!showChat || !isMobile" class="concierge-sidebar">
                <div class="concierge-brand">
                    <span>Velora Rentals</span>
                    <h1>Concierge Chat</h1>
                </div>

                <div class="concierge-toggle">
                    <button @click="showRecentChats = false"
                            :class="{ active: !showRecentChats }">
                        Active ({{ activeChatPartners.length }})
                    </button>
                    <button @click="showRecentChats = true"
                            :class="{ active: showRecentChats }">
                        Recent ({{ recentChatPartners.length }})
                    </button>
                </div>

                <div class="concierge-search">
                    <input v-model="searchQuery" type="text" :placeholder="showRecentChats ? 'Search recent chats...' : 'Search active chats...'" />
                </div>

                <div class="concierge-list">
                    <div v-if="!filteredChatPartners || filteredChatPartners.length === 0 && !loadingChat" class="concierge-empty">
                        <p>{{ showRecentChats ? 'No recent conversations found.' : 'No active conversations found.' }}</p>
                        <p v-if="showRecentChats && activeChatPartners.length > 0">
                            You have {{ activeChatPartners.length }} active conversation{{ activeChatPartners.length !== 1 ? 's' : '' }}
                        </p>
                        <p v-else-if="!showRecentChats && recentChatPartners.length > 0">
                            You have {{ recentChatPartners.length }} recent conversation{{ recentChatPartners.length !== 1 ? 's' : '' }}
                        </p>
                    </div>
                    <div v-else v-for="partner in filteredChatPartners" :key="partner.user.id"
                        class="concierge-card"
                        :class="{ active: selectedPartner && partner.user.id === selectedPartner.user.id }"
                        @click="loadChat(partner)">
                        <div class="card-avatar">
                            <img :src="getProfileImage(partner.user)" :alt="partner.user.first_name" />
                            <span :class="partner.user?.chat_status?.is_online ? 'online' : 'offline'"></span>
                        </div>
                        <div class="card-content">
                            <div class="card-title">
                                <p>{{ partner.user.first_name }} {{ partner.user.last_name || '' }}</p>
                                <span>{{ formatTime(partner.last_message_at) }}</span>
                            </div>
                            <p class="card-preview" :title="partner.last_message_preview">
                                {{ partner.last_message_preview || 'No messages yet' }}
                            </p>
                        </div>
                        <span v-if="partner.unread_count > 0" class="card-badge">
                            {{ partner.unread_count }}
                        </span>
                    </div>
                </div>
            </aside>

            <section v-if="showChat || !isMobile" class="concierge-chat">
                <div v-if="loadingChat" class="concierge-loading">Loading chat...</div>
                <div v-else-if="!selectedPartner" class="concierge-loading">Select a conversation to start chatting.</div>
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
                    @messagesRead="handleMessagesRead"
                    class="flex-grow"
                />
            </section>

            <aside v-if="showChat || !isMobile" class="concierge-panel">
                <BookingInsightsPanel
                    :booking="selectedPartner?.booking"
                    :vehicle="selectedPartner?.vehicle"
                    :partner="selectedPartner?.user"
                />
            </aside>
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
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@300;400;500;600;700&display=swap');

.concierge-shell {
    font-family: "Manrope", "Segoe UI", sans-serif;
    background: radial-gradient(circle at top left, #e6f4fb 0%, #d9e9f1 42%, #cfe0ea 100%);
    min-height: 100vh;
    height: 100vh;
    overflow: hidden;
}

.concierge-layout {
    display: grid;
    grid-template-columns: 280px 1fr 320px;
    min-height: 100vh;
    height: 100vh;
    background: rgba(245, 251, 255, 0.88);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(21, 59, 79, 0.2);
    overflow: hidden;
}

.concierge-sidebar {
    background: linear-gradient(160deg, rgba(233, 246, 252, 0.95), rgba(210, 231, 242, 0.9));
    padding: 28px 20px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    border-right: 1px solid rgba(21, 59, 79, 0.14);
    min-height: 0;
}

.concierge-brand h1 {
    font-family: "Cormorant Garamond", serif;
    font-size: 26px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.concierge-brand span {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #5a6a71;
}

.concierge-toggle {
    display: flex;
    background: rgba(21, 59, 79, 0.12);
    border-radius: 999px;
    padding: 6px;
    gap: 6px;
}

.concierge-toggle button {
    flex: 1;
    border: none;
    padding: 8px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    background: transparent;
    color: #5a6a71;
    cursor: pointer;
    transition: 0.2s ease;
}

.concierge-toggle button.active {
    background: #153b4f;
    color: #fffaf2;
}

.concierge-search input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid transparent;
    background: rgba(255, 255, 255, 0.7);
    font-size: 13px;
    outline: none;
}

.concierge-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    overflow-y: auto;
    padding-right: 4px;
    flex: 1;
    min-height: 0;
}

.concierge-card {
    padding: 12px;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid transparent;
    cursor: pointer;
    transition: 0.25s ease;
    display: grid;
    grid-template-columns: 48px 1fr auto;
    gap: 10px;
    align-items: center;
}

.concierge-card.active {
    border-color: rgba(21, 59, 79, 0.45);
    background: rgba(255, 255, 255, 0.96);
}

.card-avatar {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    background: linear-gradient(135deg, #1f556f, #153b4f);
    display: grid;
    place-items: center;
}

.card-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-avatar span {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #fffaf2;
}

.card-avatar span.online {
    background: #4ad49f;
}

.card-avatar span.offline {
    background: #9ca3af;
}

.card-content {
    min-width: 0;
}

.card-title {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #153b4f;
}

.card-title span {
    font-size: 11px;
    color: #5a6a71;
}

.card-preview {
    font-size: 12px;
    color: #5a6a71;
    margin-top: 4px;
}

.card-badge {
    background: #2f6c5b;
    color: #fffaf2;
    border-radius: 999px;
    padding: 4px 8px;
    font-size: 11px;
    font-weight: 600;
}

.concierge-chat {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 100%;
    overflow: hidden;
}

.concierge-panel {
    display: none;
    min-height: 0;
    height: 100%;
    overflow: hidden;
}

.concierge-loading {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #5a6a71;
}

.concierge-empty {
    text-align: center;
    font-size: 13px;
    color: #5a6a71;
    padding: 20px 12px;
}

@media (min-width: 1024px) {
    .concierge-panel {
        display: block;
        height: 100%;
    }
}

@media (max-width: 1100px) {
    .concierge-layout {
        grid-template-columns: 240px 1fr;
    }
}

@media (max-width: 820px) {
    .concierge-layout {
        grid-template-columns: 1fr;
    }

    .concierge-sidebar {
        display: none;
    }
}
</style>
