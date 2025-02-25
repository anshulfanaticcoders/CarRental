<script setup>
import { ref, onMounted, nextTick, computed, onBeforeUnmount } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';

const props = defineProps({
  booking: Object,
  messages: Array,
  otherUser: Object
});

// Ensure props are available before using them
const booking = computed(() => props.booking || {});
const messages = computed(() => props.messages || []);
const otherUser = computed(() => props.otherUser || {});

const messageList = ref(messages.value);
const newMessage = ref('');
const messageContainer = ref(null);
const isLoading = ref(false);
const error = ref(null);

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
      booking_id: booking.value.id,
      receiver_id: otherUser.value.id,
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

onMounted(() => {
  scrollToBottom();
});

const chatTitle = computed(() => {
  return `Chat with ${otherUser.value.name} - Booking #${booking.value.booking_number}`;
});
</script>

<template>
  <Head :title="chatTitle" />

  <MyProfileLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          {{ chatTitle }}
        </h2>
        <Link
          href="/messages"
          class="px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring ring-gray-300 disabled:opacity-25 transition"
        >
          Back to Messages
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Booking Information -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
              <div class="flex flex-col md:flex-row justify-between mb-2">
                <h3 class="text-lg font-semibold">
                  {{ booking.vehicle.brand }} {{ booking.vehicle.model }}
                </h3>
                <div>
                  <span
                    :class="[booking.booking_status, 'px-3 py-1 rounded-full text-xs font-medium']"
                  >
                    {{ booking.booking_status.charAt(0).toUpperCase() + booking.booking_status.slice(1) }}
                  </span>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Pickup:</span> 
                    {{ formatDate(booking.pickup_date) }} at {{ booking.pickup_time }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Location:</span> 
                    {{ booking.pickup_location }}
                  </p>
                </div>
                <div>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Return:</span> 
                    {{ formatDate(booking.return_date) }} at {{ booking.return_time }}
                  </p>
                  <p class="text-sm text-gray-600">
                    <span class="font-medium">Location:</span> 
                    {{ booking.return_location }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Messages Section -->
            <div 
              ref="messageContainer"
              class="h-96 overflow-y-auto p-4 border rounded-lg mb-4"
            >
              <div v-if="messageList.length === 0" class="flex items-center justify-center h-full">
                <p class="text-gray-500">No messages yet. Start the conversation!</p>
              </div>

              <div v-else>
                <div 
                  v-for="(message, index) in messageList" 
                  :key="message.id"
                  class="mb-4"
                >
                  <div 
                    :class="[
                      'max-w-3/4 rounded-lg p-3',
                      message.sender_id === $page.props.auth.user.id 
                        ? 'ml-auto bg-blue-100 text-blue-900' 
                        : 'mr-auto bg-gray-100 text-gray-900'
                    ]"
                    style="max-width: 75%;"
                  >
                    <div class="flex justify-between items-start mb-1">
                      <span class="font-medium text-sm">
                        {{ message.sender_id === $page.props.auth.user.id ? 'Me' : otherUser.name }}
                      </span>
                      <span class="text-xs text-gray-500">
                        {{ formatTime(message.created_at) }}
                      </span>
                    </div>
                    <p>{{ message.message }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="text-red-500 text-sm mb-2">
              {{ error }}
            </div>

            <!-- Message Input -->
            <div class="flex">
              <input
                v-model="newMessage"
                type="text"
                placeholder="Type your message..."
                class="flex-1 px-4 py-2 border rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                @keyup.enter="sendMessage"
              />
              <button
                @click="sendMessage"
                :disabled="isLoading || !newMessage.trim()"
                class="px-4 py-2 bg-blue-600 text-white rounded-r-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
              >
                <span v-if="isLoading">Sending...</span>
                <span v-else>Send</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>