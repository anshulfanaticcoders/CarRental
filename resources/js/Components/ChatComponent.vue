<script setup>
import { ref, computed, onMounted, nextTick, onUnmounted, watch } from 'vue';
import axios from 'axios';
import sendIcon from '../../assets/sendMessageIcon.svg';
import attachmentIcon from '../../assets/attachmentIcon.svg'; // New import for attachment icon
import { usePage } from '@inertiajs/vue3';
import searchIcon from '../../assets/MagnifyingGlass.svg';
import arrowBackIcon from '../../assets/arrowBack.svg'; // Make sure to add this icon to your assets

const props = defineProps({
    bookingId: [String, Number], // Changed from booking: Object
    messages: Array,
    otherUser: Object, // This user object should have chat_status loaded
    showBackButton: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['back', 'messageReceived']);

const messageList = ref(props.messages || []);
const newMessage = ref('');
const selectedFile = ref(null); // New ref for selected file
const fileInput = ref(null); // New ref for file input element
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
        (msg.message && msg.message.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
        (msg.file_name && msg.file_name.toLowerCase().includes(searchQuery.value.toLowerCase()))
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
    if (!newMessage.value.trim() && !selectedFile.value) return; // Ensure either message or file is present
    isLoading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('booking_id', props.bookingId);
    formData.append('receiver_id', props.otherUser.id);
    formData.append('message', newMessage.value);
    if (selectedFile.value) {
        formData.append('file', selectedFile.value);
    }

    try {
        const response = await axios.post(route('messages.store', { locale: usePage().props.locale }), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        // Append the sent message to the local messageList immediately
        messageList.value.push(response.data.message); // Backend now returns full message object with file data
        newMessage.value = '';
        selectedFile.value = null; // Clear selected file
        if (fileInput.value) {
            fileInput.value.value = ''; // Clear file input
        }
        scrollToBottom();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to send message.';
    } finally {
        isLoading.value = false;
    }
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        // Basic client-side validation (optional, backend also validates)
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'];
        const maxSize = 10 * 1024 * 1024; // 10MB

        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Allowed types: images, PDF, Word, Excel, TXT.');
            selectedFile.value = null;
            if (fileInput.value) fileInput.value.value = '';
            return;
        }
        if (file.size > maxSize) {
            alert('File size exceeds 10MB limit.');
            selectedFile.value = null;
            if (fileInput.value) fileInput.value.value = '';
            return;
        }
        selectedFile.value = file;
    } else {
        selectedFile.value = null;
    }
};

const clearSelectedFile = () => {
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const isImage = (fileType) => {
    return fileType && fileType.startsWith('image/');
};

const getFileIcon = (fileType) => {
    if (fileType.includes('pdf')) return '/images/icons/pdf-icon.svg'; // Assuming you have these icons
    if (fileType.includes('word') || fileType.includes('doc')) return '/images/icons/doc-icon.svg';
    if (fileType.includes('excel') || fileType.includes('xls')) return '/images/icons/xls-icon.svg';
    if (fileType.includes('text')) return '/images/icons/txt-icon.svg';
    return '/images/icons/file-icon.svg'; // Generic file icon
};

const recentlyDeleted = ref(null); // To store { messageId, timerId } for undo
const UNDO_TIMEOUT = 10000; // 10 seconds for undo

const deleteMessage = async (messageId) => {
    // Optimistically update UI or wait for API response
    const messageIndex = messageList.value.findIndex(msg => msg.id === messageId);
    if (messageIndex === -1) return;

    // Store original message content in case of immediate undo without API roundtrip (optional)
    // const originalContent = messageList.value[messageIndex].message;

    try {
        const response = await axios.delete(route('messages.destroy', { locale: usePage().props.locale, id: messageId }));
        if (response.data.success && response.data.message) {
            // Update the local message to reflect soft deletion
            messageList.value[messageIndex].deleted_at = response.data.message.deleted_at;
            messageList.value[messageIndex].message_original_content_temp = messageList.value[messageIndex].message; // Store for undo
            messageList.value[messageIndex].message = "This message was deleted."; // Placeholder

            // Set up undo option
            if (recentlyDeleted.value && recentlyDeleted.value.timerId) {
                clearTimeout(recentlyDeleted.value.timerId); // Clear previous undo timer
            }
            const timerId = setTimeout(() => {
                if (recentlyDeleted.value && recentlyDeleted.value.messageId === messageId) {
                    recentlyDeleted.value = null; // Clear undo option after timeout
                }
            }, UNDO_TIMEOUT);
            recentlyDeleted.value = { messageId, timerId };
            
            showOptions.value = null; // Close options menu
        } else {
            alert("Failed to delete message: " + (response.data.error || "Unknown error"));
        }
    } catch (err) {
        alert("Error deleting message: " + (err.response?.data?.message || err.message));
        // Optionally revert optimistic UI update here if one was made
    }
};

const undoDeleteMessage = async (messageId) => {
    if (!recentlyDeleted.value || recentlyDeleted.value.messageId !== messageId) return;

    try {
        const response = await axios.post(route('messages.restore', { locale: usePage().props.locale, id: messageId }));
        if (response.data.success && response.data.message) {
            const messageIndex = messageList.value.findIndex(msg => msg.id === messageId);
            if (messageIndex !== -1) {
                messageList.value[messageIndex].deleted_at = null;
                messageList.value[messageIndex].message = messageList.value[messageIndex].message_original_content_temp || response.data.message.message;
                delete messageList.value[messageIndex].message_original_content_temp;
            }
            clearTimeout(recentlyDeleted.value.timerId);
            recentlyDeleted.value = null;
        } else {
            alert("Failed to undo delete: " + (response.data.error || "Unknown error"));
        }
    } catch (err) {
        alert("Error undoing delete: " + (err.response?.data?.message || err.message));
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
    return user?.profile?.avatar ? `${user.profile.avatar}` : '/storage/avatars/default-avatar.svg';
};

const goBack = () => {
    emit('back');
};

const lastSeenText = computed(() => {
    const chatStatus = props.otherUser?.chat_status;
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
    if (diffDays < 7) return `Last seen ${diffDays}d ago`;
    return `Last seen on ${formatDate(chatStatus.last_logout_at)}`;
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
    if (props.bookingId) {
        const channel = window.Echo.private(`chat.${props.bookingId}`);
        
        channel.listen('NewMessage', (e) => {
            if (e.message && e.message.booking_id == props.bookingId) {
                if (!messageList.value.some(msg => msg.id === e.message.id)) {
                    // Ensure the message object has the file_url accessor if a file is present
                    // The backend should already send this, but as a fallback or for clarity:
                    if (e.message.file_path && !e.message.file_url) {
                        // This might not be strictly necessary if backend always sends file_url
                        // but ensures robustness if the accessor isn't automatically serialized.
                        // For now, assuming backend sends it.
                    }
                    messageList.value.push(e.message);
                    emit('messageReceived', e.message);
                    scrollToBottom();
                }
            }
        });

        channel.listen('.messages.read', (e) => {
            // e.bookingId, e.readerId, e.readAtTimestamp
            // Update messages sent by the current user that were read by the other user
            if (e.bookingId == props.bookingId && e.readerId == props.otherUser.id) {
                messageList.value.forEach(message => {
                    if (message.sender_id === page.props.auth.user.id && message.receiver_id === e.readerId && !message.read_at) {
                        message.read_at = e.readAtTimestamp;
                    }
                });
            }
            // Also, if the current user is the one who read the messages (e.g., action initiated from another tab/device, or by `show()` method)
            // ensure their view reflects that messages they received are read.
            // This part might be redundant if `markMessagesAsRead` in `index.vue` and `show()` in controller already handle local UI updates well.
            // However, it ensures consistency if the event is the source of truth.
            if (e.bookingId == props.bookingId && e.readerId == page.props.auth.user.id) {
                 messageList.value.forEach(message => {
                    if (message.receiver_id === page.props.auth.user.id && message.sender_id === props.otherUser.id && !message.read_at) {
                        // This condition might be too broad if we only want to update based on the *other* user reading.
                        // For now, let's assume the primary goal is to show ticks when the *other* user reads.
                        // The `mark-as-read` API call in `index.vue` should handle the badge,
                        // and the `show()` method in controller handles marking as read when chat is opened.
                        // This listener is mainly for the *other* party's ticks.
                    }
                });
            }
        });

        channel.listen('.message.deleted', (e) => {
            const messageIndex = messageList.value.findIndex(msg => msg.id === e.message_id);
            if (messageIndex !== -1) {
                messageList.value[messageIndex].deleted_at = e.deleted_at;
                // Ensure message content is updated if not already placeholder
                if(messageList.value[messageIndex].message !== "This message was deleted.") {
                    messageList.value[messageIndex].message_original_content_temp = messageList.value[messageIndex].message;
                    messageList.value[messageIndex].message = "This message was deleted.";
                }
            }
        });

        channel.listen('.message.restored', (e) => {
            const messageIndex = messageList.value.findIndex(msg => msg.id === e.message.id);
            if (messageIndex !== -1) {
                messageList.value[messageIndex] = e.message; // Replace with full restored message data
                // Or more granularly:
                // messageList.value[messageIndex].deleted_at = null;
                // messageList.value[messageIndex].message = e.message.message;
                // delete messageList.value[messageIndex].message_original_content_temp;
            }
        });
    }
});

onUnmounted(() => {
    if (props.bookingId) {
        window.Echo.leave(`chat.${props.bookingId}`); // Changed from props.booking.id
    }
});
</script>

<template>
    <div class="flex flex-col h-full bg-gray-100 rounded-xl shadow-lg overflow-hidden"> <!-- Added h-full -->
        <!-- Header - Fixed -->
        <div class="p-3 bg-white border-b flex items-center gap-3 shadow-sm flex-shrink-0">
            <button v-if="showBackButton" @click="goBack" class="p-1 rounded-full hover:bg-gray-100">
                <img :src="arrowBackIcon" alt="Back" class="w-6 h-6" />
            </button>

            <!-- Use otherUser directly for profile image -->
            <img :src="getProfileImage(otherUser)"
                alt="User Avatar" class="w-10 h-10 rounded-full object-cover" />

            <div class="flex-grow">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ otherUser?.first_name || 'Chat Partner' }} {{ otherUser?.last_name || '' }}
                </h2>
                <p class="text-xs text-gray-500">{{ lastSeenText }}</p> <!-- Changed to lastSeenText -->
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
                ]" style="word-break: break-word;">
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
                    <div class="mt-1 text-sm">
                        <template v-if="message.deleted_at">
                            This message was deleted.
                        </template>
                        <template v-else-if="message.file_path">
                            <div v-if="isImage(message.file_type)">
                                <a :href="message.file_url" target="_blank">
                                    <img :src="message.file_url" :alt="message.file_name" class="max-w-full h-auto rounded-lg" />
                                </a>
                            </div>
                            <div v-else class="flex items-center gap-2">
                                <img :src="getFileIcon(message.file_type)" alt="File Icon" class="w-6 h-6" />
                                <a :href="message.file_url" target="_blank" class="text-blue-400 hover:underline">
                                    {{ message.file_name }} ({{ (message.file_size / 1024 / 1024).toFixed(2) }} MB)
                                </a>
                            </div>
                            <p v-if="message.message" class="mt-1">{{ message.message }}</p>
                        </template>
                        <template v-else>
                            {{ message.message }}
                        </template>
                    </div>
                    <div v-if="message.sender_id === page.props.auth.user.id && !message.deleted_at" class="text-right mt-1">
                        <span v-if="!message.read_at" class="text-gray-300 text-xs">✓</span> <!-- Delivered -->
                        <span v-else class="text-blue-300 text-xs">✓✓</span> <!-- Read -->
                    </div>
                     <!-- Undo Button -->
                    <div v-if="recentlyDeleted && recentlyDeleted.messageId === message.id && message.sender_id === page.props.auth.user.id" class="mt-2 text-right">
                        <button @click="undoDeleteMessage(message.id)" class="text-xs text-blue-500 hover:underline">
                            Undo
                        </button>
                    </div>
                </div>

                <!-- Dropdown for delete -->
                <div v-if="showOptions === message.id && message.sender_id === page.props.auth.user.id && !message.deleted_at"
                    class="absolute right-0 top-[-3rem] mt-2 bg-white shadow-lg rounded-md py-1 w-28 z-20 border">
                    <button @click="deleteMessage(message.id)"
                        class="w-full text-left px-3 py-1 text-sm text-red-600 hover:bg-red-50">
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="px-3 py-1.5 text-red-600 text-xs bg-red-100">{{ error }}</div>

        <!-- File Preview -->
        <div v-if="selectedFile" class="bg-white px-3 py-2 border-t flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
                <span class="text-gray-700">Selected file: {{ selectedFile.name }}</span>
                <span class="text-gray-500 text-xs">({{ (selectedFile.size / 1024 / 1024).toFixed(2) }} MB)</span>
            </div>
            <button @click="clearSelectedFile" class="text-red-500 hover:text-red-700 text-sm">
                Clear
            </button>
        </div>

        <!-- Input - Fixed -->
        <div class="chat-input-bar bg-white border-t flex items-center gap-2 p-2 flex-shrink-0">
            <input type="file" ref="fileInput" @change="handleFileChange" class="hidden" accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain" />
            <button @click="fileInput.click()" class="p-2 rounded-full hover:bg-gray-100">
                <img :src="attachmentIcon" alt="Attach File" class="w-5 h-5" />
            </button>
            <textarea v-model="newMessage" placeholder="Type your message..."
                class="flex-1 p-2 rounded-full border focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none h-10 text-sm"
                @keyup.enter="sendMessage" />
            <button @click="sendMessage" :disabled="isLoading || (!newMessage.trim() && !selectedFile)"
                class="p-2 rounded-full bg-blue-300 hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:bg-blue-300">
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

    .chat-input-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        width: 100%;
        z-index: 10; /* Ensure it's above other content */
        box-shadow: 0 -2px 5px rgba(0,0,0,0.1); /* Optional: add shadow for better separation */
    }

    .flex-1.overflow-y-auto {
        padding-bottom: 70px; /* Adjust this value based on the actual height of your chat-input-bar */
    }
}
</style>
