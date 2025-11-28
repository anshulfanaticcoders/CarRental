<script setup>
import { ref, computed, onMounted, nextTick, onUnmounted, watch } from 'vue';
import axios from 'axios';
import sendIcon from '../../assets/sendMessageIcon.svg';
import attachmentIcon from '../../assets/attachmentIcon.svg';
import microphoneIcon from '../../assets/microphoneIcon.svg';
import sendVoiceNoteIcon from '../../assets/sendVoiceNoteIcon.svg';
import { usePage } from '@inertiajs/vue3';
import searchIcon from '../../assets/MagnifyingGlass.svg';
import recordingStartSound from '../../assets/sounds/recording_start.mp3';
import arrowBackIcon from '../../assets/arrowBack.svg';
import doubleTickIcon from '../../assets/double-tick.svg';

const props = defineProps({
    bookingId: [String, Number],
    messages: Array,
    otherUser: Object,
    showBackButton: {
        type: Boolean,
        default: false
    },
    bookingDetails: {
        type: Object,
        default: null
    },
    allBookings: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['back', 'messageReceived', 'bookingChanged']);

const messageList = ref(props.messages || []);
const newMessage = ref('');
const selectedFile = ref(null);
const fileInput = ref(null);
const searchQuery = ref('');
const messageContainer = ref(null);
const isLoading = ref(false);
const error = ref(null);
const showOptions = ref(null);
const isSearchVisible = ref(false);
const showBookingDetails = ref(false);
const showBookingDropdown = ref(false);

// Voice Note State
const isRecording = ref(false);
const mediaRecorder = ref(null);
const audioChunks = ref([]);
const audioBlob = ref(null);
const audioUrl = ref(null);
const recordingTime = ref(0);
const fileExtension = ref('webm');
const recordingPulse = ref(false);
let recordingInterval = null;
let recordingStartAudio = null;

// Typing Indicator State
const isTyping = ref(false);
const typingUsers = ref([]);
let typingTimeout = null;
const isAnyoneTyping = computed(() => typingUsers.value.length > 0);

// Enhanced Read Receipts State
const readReceipts = ref({});
const enhancedReadStatus = ref({});

const page = usePage();
const userRole = computed(() => page.props.auth.user.role);

const filteredMessages = computed(() => {
    if (!searchQuery.value.trim()) return messageList.value;
    return messageList.value.filter(msg =>
        (msg.message && msg.message.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
        (msg.file_name && msg.file_name.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
        (msg.voice_note_path)
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

const formatRecordingTime = (seconds) => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const scrollToBottom = () => {
    nextTick(() => {
        if (messageContainer.value) {
            messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
        }
    });
};

const sendMessage = async () => {
    if (!newMessage.value.trim() && !selectedFile.value && !audioBlob.value) return;
    isLoading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('booking_id', props.bookingId);
    formData.append('receiver_id', props.otherUser.id);
    formData.append('message', newMessage.value);
    if (selectedFile.value) {
        formData.append('file', selectedFile.value);
    }
    if (audioBlob.value) {
        formData.append('voice_note', audioBlob.value, `voice_note_${Date.now()}.${fileExtension.value}`);
    }

    try {
        const response = await axios.post(route('messages.store', { locale: usePage().props.locale }), formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        messageList.value.push(response.data.message);
        newMessage.value = '';
        selectedFile.value = null;
        if (fileInput.value) {
            fileInput.value.value = '';
        }
        cancelRecording();
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
    if (fileType.includes('pdf')) return '/images/icons/pdf-icon.svg';
    if (fileType.includes('word') || fileType.includes('doc')) return '/images/icons/doc-icon.svg';
    if (fileType.includes('excel') || fileType.includes('xls')) return '/images/icons/xls-icon.svg';
    if (fileType.includes('text')) return '/images/icons/txt-icon.svg';
    return '/images/icons/file-icon.svg';
};

const getFilePreview = (file) => {
    if (isImage(file.type)) {
        return URL.createObjectURL(file);
    }
    return null;
};

const startRecording = async () => {
    try {
        if (recordingStartAudio) {
            recordingStartAudio.play();
        }

        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        let mimeType = 'audio/webm';
        fileExtension.value = 'webm';

        if (MediaRecorder.isTypeSupported('audio/mpeg')) {
            mimeType = 'audio/mpeg';
            fileExtension.value = 'mp3';
        } else if (MediaRecorder.isTypeSupported('audio/ogg')) {
            mimeType = 'audio/ogg';
            fileExtension.value = 'ogg';
        } else if (MediaRecorder.isTypeSupported('audio/wav')) {
            mimeType = 'audio/wav';
            fileExtension.value = 'wav';
        }

        mediaRecorder.value = new MediaRecorder(stream, { mimeType });
        audioChunks.value = [];
        audioBlob.value = null;
        audioUrl.value = null;
        recordingTime.value = 0;

        mediaRecorder.value.ondataavailable = (event) => {
            audioChunks.value.push(event.data);
        };

        mediaRecorder.value.onstop = () => {
            audioBlob.value = new Blob(audioChunks.value, { type: mimeType });
            audioUrl.value = URL.createObjectURL(audioBlob.value);
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.value.start();
        isRecording.value = true;
        recordingPulse.value = true;
        recordingInterval = setInterval(() => {
            recordingTime.value++;
        }, 1000);
    } catch (err) {
        console.error('Error accessing microphone:', err);
        alert('Could not access microphone. Please ensure it is connected and permissions are granted.');
        isRecording.value = false;
        recordingPulse.value = false;
        clearInterval(recordingInterval);
    }
};

const stopRecording = () => {
    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.stop();
        isRecording.value = false;
        recordingPulse.value = false;
        clearInterval(recordingInterval);
    }
};

const sendVoiceNote = () => {
    if (audioBlob.value) {
        sendMessage();
    }
};

const cancelRecording = () => {
    if (mediaRecorder.value && mediaRecorder.value.state !== 'inactive') {
        mediaRecorder.value.stop();
    }
    audioChunks.value = [];
    audioBlob.value = null;
    if (audioUrl.value) {
        URL.revokeObjectURL(audioUrl.value);
        audioUrl.value = null;
    }
    isRecording.value = false;
    recordingPulse.value = false;
    recordingTime.value = 0;
    clearInterval(recordingInterval);
};

const recentlyDeleted = ref(null);
const UNDO_TIMEOUT = 10000;

const deleteMessage = async (messageId) => {
    const messageIndex = messageList.value.findIndex(msg => msg.id === messageId);
    if (messageIndex === -1) return;

    try {
        const response = await axios.delete(route('messages.destroy', { locale: usePage().props.locale, id: messageId }));
        if (response.data.success && response.data.message) {
            messageList.value[messageIndex].deleted_at = response.data.message.deleted_at;
            messageList.value[messageIndex].message_original_content_temp = messageList.value[messageIndex].message;
            messageList.value[messageIndex].message = "This message was deleted.";

            if (recentlyDeleted.value && recentlyDeleted.value.timerId) {
                clearTimeout(recentlyDeleted.value.timerId);
            }
            const timerId = setTimeout(() => {
                if (recentlyDeleted.value && recentlyDeleted.value.messageId === messageId) {
                    recentlyDeleted.value = null;
                }
            }, UNDO_TIMEOUT);
            recentlyDeleted.value = { messageId, timerId };
            
            showOptions.value = null;
        } else {
            alert("Failed to delete message: " + (response.data.error || "Unknown error"));
        }
    } catch (err) {
        alert("Error deleting message: " + (err.response?.data?.message || err.message));
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

const toggleBookingDetails = () => {
    showBookingDetails.value = !showBookingDetails.value;
};

const toggleBookingDropdown = () => {
    showBookingDropdown.value = !showBookingDropdown.value;
};

const switchBooking = (booking) => {
    if (booking.id !== props.bookingId) {
        emit('bookingChanged', booking);
        showBookingDropdown.value = false;
    }
};

const closeBookingDropdown = () => {
    showBookingDropdown.value = false;
};

// Function to setup Echo channel listeners
let currentChannel = null;

const setupChannelListeners = () => {
    if (!props.bookingId) return;

    // Leave existing channel if any
    if (currentChannel) {
        window.Echo.leave(`chat.${currentChannel}`);
    }

    currentChannel = props.bookingId;
    const channel = window.Echo.private(`chat.${props.bookingId}`);

    channel.listen('NewMessage', (e) => {
        if (e.message && e.message.booking_id == props.bookingId) {
            if (!messageList.value.some(msg => msg.id === e.message.id)) {
                messageList.value.push(e.message);
                emit('messageReceived', e.message);
                scrollToBottom();
            }
        }
    });

    channel.listen('.messages.read', (e) => {
        if (e.bookingId == props.bookingId && e.readerId == props.otherUser.id) {
            messageList.value.forEach(message => {
                if (message.sender_id === page.props.auth.user.id && message.receiver_id === e.readerId && !message.read_at) {
                    message.read_at = e.readAtTimestamp;
                }
            });
        }
    });

    channel.listen('.message.deleted', (e) => {
        const messageIndex = messageList.value.findIndex(msg => msg.id === e.message_id);
        if (messageIndex !== -1) {
            messageList.value[messageIndex].deleted_at = e.deleted_at;
            if (messageList.value[messageIndex].message !== "This message was deleted.") {
                messageList.value[messageIndex].message_original_content_temp = messageList.value[messageIndex].message;
                messageList.value[messageIndex].message = "This message was deleted.";
            }
        }
    });

    channel.listen('.message.restored', (e) => {
        const messageIndex = messageList.value.findIndex(msg => msg.id === e.message.id);
        if (messageIndex !== -1) {
            messageList.value[messageIndex] = e.message;
        }
    });

    // Listen for typing indicators
    channel.listen('typing.indicator', (e) => {
        if (e.user_id !== page.props.auth.user.id && e.booking_id == props.bookingId) {
            if (e.is_typing) {
                // Add user to typing list
                if (!typingUsers.value.some(user => user.user_id === e.user_id)) {
                    typingUsers.value.push({
                        user_id: e.user_id,
                        user_name: e.user_name,
                        timestamp: new Date(e.timestamp)
                    });
                }
            } else {
                // Remove user from typing list
                typingUsers.value = typingUsers.value.filter(user => user.user_id !== e.user_id);
            }

            // Auto-remove typing indicators after 10 seconds
            setTimeout(() => {
                typingUsers.value = typingUsers.value.filter(user =>
                    user.user_id !== e.user_id ||
                    new Date() - new Date(user.timestamp) < 10000
                );
            }, 10000);
        }
    });

    // Listen for enhanced read receipts
    channel.listen('message.read', (e) => {
        if (e.user_id !== page.props.auth.user.id && e.booking_id == props.bookingId) {
            // Update message read status
            const messageIndex = messageList.value.findIndex(msg => msg.id === e.message_id);
            if (messageIndex !== -1) {
                messageList.value[messageIndex].read_at = e.read_at;
            }

            // Update enhanced read receipts
            readReceipts.value[e.message_id] = {
                ...readReceipts.value[e.message_id],
                [e.user_id]: {
                    read_at: e.read_at,
                    user_name: e.user_name
                }
            };
        }
    });
};

// Watch for bookingId changes
watch(() => props.bookingId, (newBookingId, oldBookingId) => {
    if (newBookingId !== oldBookingId) {
        setupChannelListeners();
    }
}, { immediate: false });

const formatBookingDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getBookingStatusColor = (status) => {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'confirmed': return 'bg-green-100 text-green-800';
        case 'completed': return 'bg-blue-100 text-blue-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
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

const getProfileImage = (user) => {
    return user?.profile?.avatar ? `${user.profile.avatar}` : '/storage/avatars/default-avatar.svg';
};

const goBack = () => {
    emit('back');
};

// Typing Indicator Functions
const startTyping = async () => {
    if (!props.bookingId || isTyping.value) return;

    try {
        isTyping.value = true;
        await axios.post(route('messages.typing.start', { locale: usePage().props.locale }), {
            booking_id: props.bookingId
        });
    } catch (error) {
        // Silently fail - typing indicator is not critical functionality
    }
};

const stopTyping = async () => {
    if (!props.bookingId || !isTyping.value) return;

    try {
        isTyping.value = false;
        await axios.post(route('messages.typing.stop', { locale: usePage().props.locale }), {
            booking_id: props.bookingId
        });
    } catch (error) {
        // Silently fail - typing indicator is not critical functionality
    }
};

const handleTyping = () => {
    if (!newMessage.value.trim()) return;

    startTyping();

    // Clear existing timeout
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }

    // Set new timeout to stop typing after 3 seconds of inactivity
    typingTimeout = setTimeout(() => {
        stopTyping();
    }, 3000);
};

// Enhanced Read Receipts Functions
const getEnhancedReadStatus = (message) => {
    if (!message || message.sender_id !== page.props.auth.user.id) return null;

    return {
        isDelivered: true, // If message exists, it's delivered
        isRead: message.read_at !== null,
        readAt: message.read_at,
        readByAll: false // This would be calculated based on read receipts
    };
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

watch(() => props.messages, (newMessages) => {
    if (newMessages) {
        messageList.value = newMessages;
        scrollToBottom();
    }
}, { deep: true });

onMounted(() => {
    scrollToBottom();
    recordingStartAudio = new Audio(recordingStartSound);
    recordingStartAudio.load();

    // Add global click handler to close booking dropdown
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.booking-dropdown')) {
            showBookingDropdown.value = false;
        }
    });

    // Setup channel listeners
    setupChannelListeners();
});

onUnmounted(() => {
    // Clean up current channel
    if (currentChannel) {
        window.Echo.leave(`chat.${currentChannel}`);
        currentChannel = null;
    }

    // Stop typing when component unmounts
    stopTyping();

    // Clear typing timeout
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }

    // Remove global click handler
    document.removeEventListener('click', closeBookingDropdown);
});
</script>

<template>
    <div class="flex flex-col h-full bg-gradient-to-b from-gray-50 to-gray-100 rounded-xl shadow-lg overflow-hidden">
        <!-- Header - Fixed -->
        <div class="p-4 bg-white border-b border-gray-200 flex items-center gap-3 shadow-sm flex-shrink-0">
            <button v-if="showBackButton" @click="goBack"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200">
                <img :src="arrowBackIcon" alt="Back" class="w-5 h-5" />
            </button>

            <div class="relative">
                <img :src="getProfileImage(otherUser)"
                    alt="User Avatar" class="w-12 h-12 rounded-full object-cover ring-2 ring-blue-100" />
                <div v-if="otherUser?.chat_status?.is_online"
                    class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
            </div>

            <div class="flex-grow">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ otherUser?.first_name || 'Chat Partner' }} {{ otherUser?.last_name || '' }}
                        </h2>
                        <p class="text-sm text-gray-500">{{ lastSeenText }}</p>
                    </div>

                    <!-- Booking Context Dropdown (if multiple bookings) -->
                    <div v-if="allBookings.length > 1" class="booking-dropdown relative">
                        <button @click="toggleBookingDropdown"
                            class="flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200 text-sm"
                            title="Switch booking">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                            <span class="text-blue-800 font-medium">{{ allBookings.length }} Bookings</span>
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <div v-if="showBookingDropdown"
                             class="absolute right-0 top-full mt-1 w-80 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                            <div class="p-3 border-b border-gray-100">
                                <h3 class="text-sm font-semibold text-gray-800">Switch Booking</h3>
                                <p class="text-xs text-gray-500">Select a different booking to chat about</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div v-for="booking in allBookings" :key="booking.id"
                                     @click="switchBooking(booking)"
                                     :class="[
                                         'p-3 border-b border-gray-50 last:border-b-0 cursor-pointer transition-colors duration-150',
                                         'hover:bg-gray-50',
                                         booking.id === bookingId ? 'bg-blue-50' : 'bg-white'
                                     ]">
                                    <div class="flex items-center gap-3">
                                        <!-- Vehicle Image -->
                                        <div class="flex-shrink-0">
                                            <img v-if="booking.vehicle?.image"
                                                 :src="booking.vehicle.image"
                                                 :alt="booking.vehicle.name"
                                                 class="w-12 h-12 object-cover rounded-lg">
                                            <div v-else
                                                 class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Booking Info -->
                                        <div class="flex-grow min-w-0">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-semibold text-sm text-gray-900 truncate">{{ booking.vehicle?.name }}</h4>
                                                <span v-if="booking.id === bookingId"
                                                      class="text-xs bg-blue-600 text-white px-2 py-0.5 rounded-full">Current</span>
                                            </div>
                                            <p class="text-xs text-gray-600">{{ booking.vehicle?.brand }} {{ booking.vehicle?.model }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span :class="`text-xs px-2 py-0.5 rounded-full ${getBookingStatusColor(booking.booking_status)}`">
                                                    {{ getBookingStatusIcon(booking.booking_status) }} {{ booking.booking_status?.charAt(0).toUpperCase() + booking.booking_status?.slice(1) }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ formatDate(booking.pickup_date) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Single Booking Indicator -->
                    <div v-else-if="bookingDetails" class="flex items-center gap-2 px-3 py-2 bg-green-50 rounded-lg">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-green-800 font-medium text-sm">1 Booking</span>
                    </div>
                </div>
            </div>

            <button @click="toggleSearch"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200">
                <img :src="searchIcon" alt="Search" class="w-5 h-5" />
            </button>

            <button v-if="bookingDetails" @click="toggleBookingDetails"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200"
                title="View Booking Details">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </button>
        </div>

        <!-- Search Bar - Conditional -->
        <div v-if="isSearchVisible" class="p-3 bg-white border-b border-gray-200">
            <div class="relative">
                <input v-model="searchQuery" type="text" placeholder="Search messages..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-50 transition-all duration-200"
                    autofocus />
                <img :src="searchIcon" alt="Search"
                    class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
            </div>
        </div>

        <!-- Booking Details Panel -->
        <div v-if="showBookingDetails && bookingDetails" class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 p-4">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg font-semibold text-gray-800">Booking Details</span>
                    <span :class="`px-2 py-1 rounded-full text-xs font-medium ${getBookingStatusColor(bookingDetails.status)}`">
                        {{ getBookingStatusIcon(bookingDetails.status) }} {{ bookingDetails.status.charAt(0).toUpperCase() + bookingDetails.status.slice(1) }}
                    </span>
                </div>
                <button @click="toggleBookingDetails" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Vehicle Information -->
            <div v-if="bookingDetails.vehicle" class="bg-white rounded-lg p-3 mb-3 shadow-sm">
                <div class="flex items-center gap-3">
                    <div v-if="bookingDetails.vehicle.image" class="flex-shrink-0">
                        <img :src="bookingDetails.vehicle.image" :alt="bookingDetails.vehicle.name"
                            class="w-16 h-16 object-cover rounded-lg" />
                    </div>
                    <div class="flex-grow">
                        <h4 class="font-semibold text-gray-800">{{ bookingDetails.vehicle.name }}</h4>
                        <p class="text-sm text-gray-600">
                            {{ bookingDetails.vehicle.brand }} {{ bookingDetails.vehicle.model }}
                        </p>
                        <p v-if="bookingDetails.vehicle.category" class="text-xs text-gray-500">
                            {{ bookingDetails.vehicle.category }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Booking Dates and Times -->
            <div class="bg-white rounded-lg p-3 shadow-sm">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Pickup</p>
                        <p class="text-sm font-medium text-gray-800">{{ formatBookingDate(bookingDetails.pickup_date) }}</p>
                        <p v-if="bookingDetails.pickup_time" class="text-xs text-gray-600">{{ bookingDetails.pickup_time }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Return</p>
                        <p class="text-sm font-medium text-gray-800">{{ formatBookingDate(bookingDetails.return_date) }}</p>
                        <p v-if="bookingDetails.return_time" class="text-xs text-gray-600">{{ bookingDetails.return_time }}</p>
                    </div>
                </div>

                <!-- Pricing Information -->
                <div v-if="bookingDetails.total_amount" class="mt-3 pt-3 border-t border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Amount:</span>
                        <span class="text-lg font-semibold text-gray-800">${{ bookingDetails.total_amount }}</span>
                    </div>
                    <div v-if="bookingDetails.amount_paid" class="flex justify-between items-center mt-1">
                        <span class="text-xs text-gray-500">Amount Paid:</span>
                        <span class="text-sm text-gray-600">${{ bookingDetails.amount_paid }}</span>
                    </div>
                </div>

              </div>
        </div>

        <!-- Messages - Scrollable -->
        <div ref="messageContainer" class="flex-1 overflow-y-auto p-4 space-y-4 scrollbar-thin scrollbar-thumb-gray-300 scrollbar-track-transparent">
            <div v-if="filteredMessages.length === 0" class="text-center text-gray-500 py-12">
                <div class="text-4xl mb-2">💬</div>
                <p class="text-lg">No messages found</p>
            </div>
            <div v-else v-for="message in filteredMessages" :key="message.id" class="group relative">
                <div :class="[
                    'p-4 rounded-2xl max-w-[75%] break-words shadow-sm transition-all duration-200 hover:shadow-md w-fit',
                    message.sender_id === $page.props.auth.user.id
                        ? 'ml-auto bg-customPrimaryColor text-white rounded-br-lg'
                        : 'mr-auto bg-white text-gray-900 border border-gray-200 rounded-bl-lg'
                ]">
                    <div class="flex justify-between items-start gap-3 mb-2">
                        <span class="font-medium text-sm opacity-90">
                            {{ message.sender_id === $page.props.auth.user.id ? 'You' : otherUser.first_name }}
                        </span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs opacity-70">{{ formatTime(message.created_at) }}</span>
                            <button v-if="message.sender_id === page.props.auth.user.id"
                                @click="toggleOptions(message.id)"
                                class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 p-1 rounded hover:bg-white/20 mobile-show-options">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div class="text-sm leading-relaxed">
                        <template v-if="message.deleted_at">
                            <div class="flex items-center gap-2 text-gray-400 italic">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v4a1 1 0 102 0V5z" clip-rule="evenodd"></path>
                                </svg>
                                This message was deleted
                            </div>
                        </template>
                        <template v-else-if="message.file_path">
                            <div v-if="isImage(message.file_type)" class="mb-2">
                                <a :href="message.file_url" target="_blank" class="block">
                                    <img :src="message.file_url" :alt="message.file_name" 
                                        class="w-60 h-48 rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200" />
                                </a>
                            </div>
                            <div v-else class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border">
                                <img :src="getFileIcon(message.file_type)" alt="File Icon" class="w-8 h-8" />
                                <div class="flex-grow min-w-0">
                                    <a :href="message.file_url" target="_blank" 
                                        class="text-blue-600 hover:text-blue-800 font-medium block truncate">
                                        {{ message.file_name }}
                                    </a>
                                    <p class="text-xs text-gray-500">{{ formatFileSize(message.file_size) }}</p>
                                </div>
                            </div>
                            <p v-if="message.message" class="mt-2">{{ message.message }}</p>
                        </template>
                        <template v-else-if="message.voice_note_path">
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <audio controls :src="message.voice_note_url" class="flex-grow h-8"></audio>
                            </div>
                            <p v-if="message.message" class="mt-2">{{ message.message }}</p>
                        </template>
                        <template v-else>
                            {{ message.message }}
                        </template>
                    </div>

                    <!-- Enhanced Read Status -->
                    <div v-if="message.sender_id === page.props.auth.user.id && !message.deleted_at"
                        class="text-right mt-2">
                        <div class="flex items-center justify-end gap-1">
                            <!-- Single checkmark for delivered (shown when not read) -->
                            <span v-if="!message.read_at" class="text-xs text-gray-400">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                            </span>

                            <!-- Double checkmark for read (shown only when read) -->
                            <span v-else class="text-xs text-blue-500">
                                <img :src="doubleTickIcon" alt="Read" class="w-4 h-4" />
                            </span>

                            <!-- Read timestamp (shown on hover for better UX) -->
                            <span v-if="message.read_at" class="text-xs text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity duration-200" :title="`Read at ${formatTime(message.read_at)}`">
                                {{ formatTime(message.read_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Undo Delete Button -->
                    <div v-if="recentlyDeleted && recentlyDeleted.messageId === message.id && message.sender_id === page.props.auth.user.id" 
                        class="mt-3 text-right">
                        <button @click="undoDeleteMessage(message.id)" 
                            class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded-full transition-colors duration-200">
                            Undo Delete
                        </button>
                    </div>
                </div>

                <!-- Options Menu -->
                <div v-if="showOptions === message.id && message.sender_id === page.props.auth.user.id && !message.deleted_at"
                    class="absolute right-10 top-2 mt-2 bg-white shadow-lg rounded-lg py-2 w-32 z-20 border border-gray-200 delete-message-popup-mobile">
                    <button @click="deleteMessage(message.id)"
                        class="w-full text-left px-4 py-2 text-[0.75rem] text-red-600 hover:bg-red-50 transition-colors duration-200">
                        Delete Message
                    </button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div v-if="isAnyoneTyping" class="px-4 py-2 bg-white border-t border-gray-100">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <div class="flex gap-1">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                </div>
                <span v-if="typingUsers.length === 1">{{ typingUsers[0].user_name }} is typing...</span>
                <span v-else>{{ typingUsers.map(u => u.user_name).join(', ') }} are typing...</span>
            </div>
        </div>

        <!-- File Preview -->
        <div v-if="selectedFile" class="bg-white border-t border-gray-200 p-4 preview">
            <div class="flex items-start gap-3 bg-blue-50 rounded-lg p-3">
                <div v-if="getFilePreview(selectedFile)" class="flex-shrink-0">
                    <img :src="getFilePreview(selectedFile)" :alt="selectedFile.name" 
                        class="w-16 h-16 object-cover rounded-lg" />
                </div>
                <div v-else class="flex-shrink-0">
                    <img :src="getFileIcon(selectedFile.type)" alt="File Icon" class="w-12 h-12" />
                </div>
                <div class="flex-grow min-w-0">
                    <span class="font-medium text-gray-900 truncate">{{ selectedFile.name }}</span>
                    <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs text-blue-600 bg-blue-100 px-2 py-1 rounded-full">Ready to send</span>
                        <button @click="clearSelectedFile" 
                            class="text-xs text-red-600 hover:text-red-800 transition-colors duration-200">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Voice Note Preview -->
        <div v-if="audioUrl" class="bg-white border-t border-gray-200 p-4 preview">
            <div class="flex items-center gap-3 bg-green-50 rounded-lg p-3">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-grow">
                    <span class="font-medium text-gray-900">Voice Message</span>
                    <p class="text-sm text-gray-500">{{ formatRecordingTime(recordingTime) }}</p>
                    <audio controls :src="audioUrl" class="mt-2 w-full h-8"></audio>
                </div>
                <button @click="cancelRecording" 
                    class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-full transition-colors duration-200">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Recording Status -->
        <div v-if="isRecording" class="bg-red-50 border-t border-red-200 p-4 preview">
            <div class="flex items-center justify-center gap-3">
                <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                <span class="text-red-700 font-medium">Recording: {{ formatRecordingTime(recordingTime) }}</span>
                <button @click="stopRecording" 
                    class="ml-4 px-4 py-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-colors duration-200">
                    Stop Recording
                </button>
            </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="bg-red-50 border-t border-red-200 p-3">
            <div class="flex items-center gap-2 text-red-700">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">{{ error }}</span>
            </div>
        </div>

        <!-- Input Area -->
        <div class="bg-white border-t border-gray-200 p-2 flex-shrink-0 chat-input-area">
            <div class="flex items-center gap-3">
                <!-- Hidden File Input -->
                <input type="file" ref="fileInput" @change="handleFileChange" class="hidden" 
                    accept="image/*,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/plain" />
                
                <!-- Attachment Button -->
                <button @click="fileInput.click()" 
                    :disabled="isRecording || audioUrl" 
                    class="p-3 rounded-full bg-gray-100 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <img :src="attachmentIcon" alt="Attach File" class="w-5 h-5" />
                </button>

                <!-- Voice Recording Button -->
                <button v-if="!isRecording && !audioUrl" @click="startRecording" 
                    :disabled="selectedFile || newMessage.trim().length > 0" 
                    class="p-3 rounded-full bg-gray-100 hover:bg-gray-100 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <img :src="microphoneIcon" alt="Record Voice" class="w-5 h-5" />
                </button>

                <!-- Message Input -->
                <div class="flex-1 flex">
                    <textarea v-model="newMessage" placeholder="Type your message..."
                        class="w-full p-3 rounded-2xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none min-h-[44px] max-h-32 transition-all duration-200 bg-gray-50"
                        :disabled="isRecording || audioUrl"
                        @keydown.enter.exact.prevent="sendMessage"
                        @input="handleTyping"
                        @blur="stopTyping"
                        rows="1" />
                </div>
                
                <!-- Send Voice Note Button -->
                <button v-if="audioUrl" @click="sendVoiceNote" 
                    :disabled="isLoading"
                    class="p-3 rounded-full bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <img :src="sendVoiceNoteIcon" alt="Send Voice Note" class="w-5 h-5" v-if="!isLoading" />
                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
                
                <!-- Send Message Button -->
                <button v-else @click="sendMessage"
                    :disabled="isLoading || (!newMessage.trim() && !selectedFile)"
                    class="p-3 rounded-full bg-customPrimaryColor hover:bg-blue-700 text-white transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                    <img :src="sendIcon" alt="Send" class="w-5 h-5" v-if="!isLoading" />
                    <svg v-else class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom scrollbar */
.scrollbar-thin::-webkit-scrollbar {
    width: 6px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background-color: #d1d5db;
    border-radius: 3px;
}

.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background-color: #9ca3af;
}

/* Auto-resize textarea */
textarea {
    transition: height 0.2s ease;
}

/* Mobile optimization */
@media (max-width: 640px) {
    .rounded-xl {
        border-radius: 0;
    }

    .bg-white {
        position: relative;
        z-index: 10;
    }

    .flex-1.overflow-y-auto {
        padding-bottom: 20px;
    }

    .max-w-\[75\%\] {
        max-width: 85%;
    }
}

@media (max-width: 768px) {
    .chat-input-area {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 10;
        width: 100%;
        max-width: 100%;
    }

    .flex-1.overflow-y-auto {
        padding-bottom: 80px; /* Adjust padding to account for fixed input bar height */
    }

    .delete-message-popup-mobile {
        right: 8px; /* Equivalent to Tailwind's right-2 (2 * 4px) */
        width: fit-content;
        max-width: calc(100vw - 16px); /* 100vw minus 8px padding on each side */
        left: auto;
        top: 100%;
        margin-top: 4px; /* Equivalent to Tailwind's mt-1 */
    }
    .preview{
        margin-bottom: 67px;
    }

    .mobile-show-options {
        opacity: 1 !important;
    }
}

/* Animation for recording pulse */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
