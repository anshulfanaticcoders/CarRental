<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';
import sendIcon from '../../assets/sendMessageIcon.svg';
import { usePage } from '@inertiajs/vue3';
import searchIcon from '../../assets/MagnifyingGlass.svg'
import dotIcon from '../../assets/dots.svg'

const props = defineProps({
    booking: Object,
    messages: Array,
    otherUser: Object
});

const messageList = ref(props.messages || []);
const newMessage = ref('');
const searchQuery = ref('');
const messageContainer = ref(null);
const isLoading = ref(false);
const error = ref(null);
const showOptions = ref({}); // Track which message options are open

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

// Computed property to filter messages
const filteredMessages = computed(() => {
    if (!searchQuery.value.trim()) {
        return messageList.value;
    }
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

    try {
        const response = await axios.post('/messages', {
            booking_id: props.booking.id,
            receiver_id: props.otherUser.id,
            message: newMessage.value
        });

        messageList.value.push(response.data.message);
        newMessage.value = '';
        scrollToBottom();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to send message. Please try again.';
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
        alert("Failed to delete message. Please try again.");
    }
};


// Toggle message options (3-dot menu)
const toggleOptions = (messageId) => {
    showOptions.value = showOptions.value === messageId ? null : messageId;
};

const getProfileImage = (customer) => {
    return customer.user.profile && customer.user.profile.avatar
        ? `${customer.user.profile.avatar}`
        : '/storage/avatars/default-avatar.svg';
};

onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <div class="h-full flex flex-col">
        <!-- Booking Information -->
        <div class="p-4 bg-gray-50 rounded-lg mb-4 flex gap-4" v-if="userRole === 'customer'">
            <img :src="booking.vehicle?.vendor_profile?.avatar
                ? `${booking.vehicle.vendor_profile.avatar}`
                : '/storage/avatars/default-avatar.svg'" alt="User Avatar"
                class="rounded-full object-cover h-[50px] w-[50px]" />
            <div>
                <span class="text-customDarkBlackColor font-medium text-[1.1rem]">
                    {{ booking.vehicle?.vendor?.first_name || "N/A" }}
                </span>
                <p class="text-customLightGrayColor">Last Seen 7h ago</p>
            </div>
        </div>

        <div class="p-4 bg-gray-50 rounded-lg mb-4 flex gap-4" v-else-if="userRole === 'vendor'">
            <img :src="getProfileImage(booking.customer)" alt="Customer Avatar"
                class="w-10 h-10 rounded-full object-cover mr-4" />
            <div>
                <span class="text-customDarkBlackColor font-medium text-[1.1rem]">
                    {{ booking.customer?.first_name || "N/A" }}
                </span>
                <p class="text-customLightGrayColor">Last Seen 7h ago</p>
            </div>
        </div>

        <!-- Search Messages -->
        <div class="relative mb-4">
            <input v-model="searchQuery" type="text" placeholder="Search messages..."
                class="w-full px-4 py-2 pl-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-customPrimaryColor" />
            <img :src=searchIcon alt="" class="absolute left-3 top-2">
        </div>

        <!-- Messages List -->
        <div ref="messageContainer" class="flex-1 overflow-y-auto p-4 border rounded-lg mb-4">
            <div v-if="filteredMessages.length === 0" class="flex items-center justify-center h-full">
                <p class="text-gray-500">No messages found.</p>
            </div>

            <div v-else>
                <div v-for="(message, index) in filteredMessages" :key="message.id"
                    class="mb-4 relative group hover:bg-gray-100 p-2 rounded-lg transition">
                    <div :class="[
                        'max-w-3/4 p-3',
                        message.sender_id === $page.props.auth.user.id
                            ? 'ml-auto bg-customPrimaryColor text-white rounded-[16px]'
                            : 'mr-auto bg-white text-gray-900 rounded-lg'
                    ]" style="max-width: 75%;">
                        <div class="flex justify-between items-start">
                            <span class="font-medium text-sm">
                                {{ message.sender_id === $page.props.auth.user.id ? 'Me' : otherUser.first_name }}
                            </span>
                            <div class="flex gap-2">
                                <span class="text-xs text-gray-500">
                                    {{ formatTime(message.created_at) }}
                                </span>
                                <!-- 3-dot menu (only for sender) -->
                                <div v-if="message.sender_id === page.props.auth.user.id"
                                    class="relative mt-[-5px] opacity-0 group-hover:opacity-100 transition-opacity">
                                    <button @click="toggleOptions(message.id)"
                                        class="">
                                        ⋮
                                    </button>

                                    <!-- Dropdown options -->
                                    <div v-if="showOptions === message.id"
                                        class="absolute right-0  mt-1 bg-white shadow-lg rounded-md py-2 w-28 z-20 border">
                                        <button @click="deleteMessage(message.id)"
                                            class="block w-full text-center text-red-500 hover:bg-red-100">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <p class="relative">{{ message.message }}
                        <div v-if="message.sender_id === page.props.auth.user.id" class="absolute bottom-1 right-2 z-10">
                            <span v-if="!message.read_at" class="text-gray-400 text-[0.65rem]">✓</span>
                            <span v-else class="text-blue-400 text-[0.65rem]">✓✓</span>
                        </div>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="text-red-500 text-sm mb-2">
            {{ error }}
        </div>

        <!-- Message Input -->
        <div class="flex gap-5 mt-auto relative">
            <textarea v-model="newMessage" type="text" placeholder="Type your message..."
                class="flex-1 pl-4 pr-[3rem] pt-4 rounded-[99px] focus:outline-none focus:ring-2 focus:ring-customPrimaryColor"
                @keyup.enter="sendMessage" />
            <button @click="sendMessage" :disabled="isLoading || !newMessage.trim()"
                class="cursor-pointer absolute right-4 top-1 translate-x-[0%] translate-y-[50%]">
                <span v-if="isLoading">Sending...</span>
                <span v-else>
                    <img :src="sendIcon" alt="" class="w-[24px] h-[24px]">
                </span>
            </button>
        </div>
    </div>
</template>

<style></style>
