// ChatComponent.vue
<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import axios from 'axios';
import sendIcon from '../../assets/sendMessageIcon.svg';

const props = defineProps({
    booking: Object,
    messages: Array,
    otherUser: Object
});

const messageList = ref(props.messages || []);
const newMessage = ref('');
const messageContainer = ref(null);
const isLoading = ref(false);
const error = ref(null);
import { usePage } from '@inertiajs/vue3';

const page = usePage();

const userRole = computed(() => page.props.auth.user.role);



const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatTime = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
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
const { bookings } = usePage().props;

const getProfileImage = (customer) => {
    return customer.user.profile && customer.user.profile.avatar ? `/storage/${customer.user.profile.avatar}` : '/storage/avatars/default-avatar.svg';
};
onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <!-- Messages Section -->
    <div class="h-full flex flex-col">
        <!-- Booking Information -->
        <div class="p-4 bg-gray-50 rounded-lg mb-4 flex gap-4" v-if="userRole === 'customer'">
            <img :src="booking.vehicle?.vendor_profile?.avatar
                ? `/storage/${booking.vehicle.vendor_profile.avatar}`
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

        <!-- Messages List -->
        <div ref="messageContainer" class="flex-1 overflow-y-auto p-4 border rounded-lg mb-4">
            <div v-if="messageList.length === 0" class="flex items-center justify-center h-full">
                <p class="text-gray-500">No messages yet. Start the conversation!</p>
            </div>

            <div v-else>
                <div v-for="(message, index) in messageList" :key="message.id" class="mb-4">
                    <div :class="[
                        'max-w-3/4  p-3',
                        message.sender_id === $page.props.auth.user.id
                            ? 'ml-auto bg-customPrimaryColor text-white rounded-[16px]'
                            : 'mr-auto bg-white text-gray-900 rounded-lg'
                    ]" style="max-width: 75%;">
                        <div class="flex justify-between items-start mb-1">
                            <span class="font-medium text-sm">
                                {{ message.sender_id === $page.props.auth.user.id ? 'Me' : otherUser.first_name }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ formatTime(message.created_at) }}
                            </span>
                        </div>
                        <p class="relative">{{ message.message }}

                            <!-- Tick Marks -->
                        <div v-if="message.sender_id === page.props.auth.user.id" class="absolute bottom-1 right-2">
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
            <input v-model="newMessage" type="text" placeholder="Type your message..."
                class="flex-1 px-4 py-4 rounded-[99px] focus:outline-none focus:ring-2 focus:ring-customPrimaryColor"
                @keyup.enter="sendMessage" />
            <button @click="sendMessage" :disabled="isLoading || !newMessage.trim()"
                class=" cursor-pointer absolute right-4 top-1 translate-x-[0%] translate-y-[50%]">
                <span v-if="isLoading">Sending...</span>
                <span v-else>
                    <img :src=sendIcon alt="" class="w-[24px] h-[24px]">
                </span>
            </button>
        </div>
    </div>
</template>

<style></style>