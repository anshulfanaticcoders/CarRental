<script setup>
import { ref, computed, onMounted, nextTick, onUnmounted, watch } from 'vue';
import axios from 'axios';
import sendIcon from '../../assets/sendMessageIcon.svg';
import { usePage } from '@inertiajs/vue3';
import searchIcon from '../../assets/MagnifyingGlass.svg';
import arrowBackIcon from '../../assets/arrowBack.svg'; // Make sure to add this icon to your assets

const props = defineProps({
    booking: Object,
    messages: Array,
    otherUser: Object,
    showBackButton: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['back']);

const messageList = ref(props.messages || []);
const newMessage = ref('');
const searchQuery = ref('');
const messageContainer = ref(null);
const isLoading = ref(false);
const error = ref(null);
const showOptions = ref(null);
const isSearchVisible = ref(false);

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

const filteredMessages = computed(() => {
    if (!searchQuery.value.trim()) return messageList.value;
    return messageList.value.filter(msg =>
        msg.message.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    isLoading.value = true;
    error.value = null;

    const messageData = {
        booking_id: props.booking.id,
        receiver_id: props.otherUser.id,
        message: newMessage.value
    };

    try {
        const response = await axios.post('/messages', messageData);
        // Append the sent message to the local messageList immediately
        messageList.value.push({
            id: response.data.message.id,
            sender_id: page.props.auth.user.id,
            receiver_id: props.otherUser.id,
            booking_id: props.booking.id,
            message: newMessage.value,
            created_at: new Date().toISOString(),
            read_at: null
        });
        newMessage.value = '';
        scrollToBottom();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to send message.';
    } finally {
        isLoading.value = false;
    }
};

const deleteMessage = async (messageId) => {
    if (!confirm("Are you sure you want to delete this message?")) return;
    try {
        await axios.delete(`/messages/${messageId}`);
        messageList.value = messageList.value.filter(msg => msg.id !== messageId);
    } catch (err) {
        alert("Failed to delete message.");
    }
};

const toggleOptions = (messageId) => {
    showOptions.value = showOptions.value === messageId ? null : messageId;
};

const toggleSearch = () => {
    isSearchVisible.value = !isSearchVisible.value;
    if (!isSearchVisible.value) {
        searchQuery.value = '';
    }
};

const getProfileImage = (user) => {
    return user.profile?.avatar ? `${user.profile.avatar}` : '/storage/avatars/default-avatar.svg';
};

const goBack = () => {
    emit('back');
};

const lastSeen = computed(() => {
    if (!props.otherUser.last_login_at) return 'Last seen: Unknown';
    const now = new Date();
    const lastLoginAt = new Date(props.otherUser.last_login_at);
    const diffMs = now - lastLoginAt;
    const diffMins = Math.floor(diffMs / 60000); // Minutes
    const diffHrs = Math.floor(diffMins / 60);   // Hours

    if (diffMins < 1) return 'Online now';
    if (diffMins < 60) return `Last seen: ${diffMins}m ago`;
    if (diffHrs < 24) return `Last seen: ${diffHrs}h ago`;
    return `Last seen: ${formatDate(props.otherUser.last_login_at)}`;
});

// Watch for changes in props.messages to update local messages
watch(() => props.messages, (newMessages) => {
    if (newMessages) {
        messageList.value = newMessages;
        scrollToBottom();
    }
}, { deep: true });

onMounted(() => {
    scrollToBottom();
    window.Echo.private(`chat.${props.booking.id}`)
        .listen('NewMessage', (e) => {
            // Only append if the message isn't already in the list (prevents duplicates)
            if (!messageList.value.some(msg => msg.id === e.message.id)) {
                messageList.value.push(e.message);
                scrollToBottom();
            }
        });
});

onUnmounted(() => {
    window.Echo.leave(`chat.${props.booking.id}`);
});
</script>

<template>
    <div class="flex flex-col h-full bg-gray-100 rounded-xl shadow-lg overflow-hidden">
        <!-- Header - Fixed -->
        <div class="p-3 bg-white border-b flex items-center gap-3 shadow-sm">
            <button v-if="showBackButton" @click="goBack" class="p-1 rounded-full hover:bg-gray-100">
                <img :src="arrowBackIcon" alt="Back" class="w-6 h-6" />
            </button>

            <img :src="userRole === 'customer' ? booking.vehicle?.vendor_profile?.avatar : getProfileImage(booking.customer?.user)"
                alt="User Avatar" class="w-10 h-10 rounded-full object-cover" />

            <div class="flex-grow">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ userRole === 'customer' ? booking.vehicle?.vendor?.first_name : booking.customer?.first_name }}
                </h2>
                <p class="text-xs text-gray-500">{{ lastSeen }}</p>
            </div>

            <button @click="toggleSearch" class="p-2 rounded-full hover:bg-gray-100">
                <img :src="searchIcon" alt="Search" class="w-5 h-5" />
            </button>
        </div>

        <!-- Search Bar - Conditional -->
        <div v-if="isSearchVisible" class="p-2 bg-white border-b">
            <div class="relative">
                <input v-model="searchQuery" type="text" placeholder="Search messages..."
                    class="w-full pl-10 pr-4 py-2 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
                    autofocus />
                <img :src="searchIcon" alt="Search"
                    class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5" />
            </div>
        </div>

        <!-- Messages - Scrollable -->
        <div ref="messageContainer" class="flex-1 overflow-y-auto p-3 space-y-3">
            <div v-if="filteredMessages.length === 0" class="text-center text-gray-500 py-8">
                No messages found.
            </div>
            <div v-else v-for="message in filteredMessages" :key="message.id" class="group relative">
                <div :class="[
                    'p-3 rounded-lg max-w-[45%] break-words',
                    message.sender_id === $page.props.auth.user.id
                        ? 'ml-auto bg-[#153b4f] text-white self-end '
                        : 'mr-0 bg-white text-gray-900 border self-start '
                ]">
                    <div class="flex justify-between items-start gap-2">
                        <span class="font-medium text-xs">
                            {{ message.sender_id === $page.props.auth.user.id ? 'Me' : otherUser.first_name }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs opacity-75">{{ formatTime(message.created_at) }}</span>
                            <button v-if="message.sender_id === page.props.auth.user.id"
                                @click="toggleOptions(message.id)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity text-gray-300 hover:text-white">
                                ⋮
                            </button>
                        </div>
                    </div>
                    <p class="mt-1 text-sm">{{ message.message }}</p>
                    <div v-if="message.sender_id === page.props.auth.user.id" class="text-right mt-1">
                        <span v-if="!message.read_at" class="text-gray-300 text-xs">✓</span>
                        <span v-else class="text-blue-300 text-xs">✓✓</span>
                    </div>
                </div>

                <!-- Dropdown -->
                <div v-if="showOptions === message.id"
                    class="absolute right-0 mt-2 bg-white shadow-lg rounded-md py-2 w-32 z-20 border">
                    <button @click="deleteMessage(message.id)"
                        class="w-full text-left px-4 py-1 text-red-500 hover:bg-red-100">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="px-4 py-2 text-red-500 text-xs bg-red-50">{{ error }}</div>

        <!-- Input - Fixed -->
        <div class="p-3 bg-white border-t flex items-center gap-2 shadow-inner">
            <textarea v-model="newMessage" placeholder="Type your message..."
                class="flex-1 p-2 rounded-full border focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none h-10 text-sm"
                @keyup.enter="sendMessage" />
            <button @click="sendMessage" :disabled="isLoading || !newMessage.trim()"
                class="p-2 rounded-full bg-blue-500 hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:bg-blue-300">
                <img :src="sendIcon" alt="Send" class="w-5 h-5" v-if="!isLoading" />
                <span v-else class="text-white text-xs">...</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
/* Mobile optimization */
@media (max-width: 640px) {
    .rounded-xl {
        border-radius: 0;
    }

    textarea.h-10 {
        height: 40px;
    }
}
</style>