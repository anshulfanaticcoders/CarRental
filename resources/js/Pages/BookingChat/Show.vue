<template>
  <div class="container mx-auto px-4 py-8">
    <!-- Header -->
    <div class="mb-6 flex items-center justify-between">
      <div class="flex items-center">
        <button
          @click="$inertia.visit('/booking-chats')"
          class="mr-4 text-gray-600 hover:text-gray-900"
        >
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <div class="flex items-center">
          <img
            class="h-10 w-10 rounded-full object-cover"
            :src="otherUser.profile_image || '/images/default-avatar.png'"
            :alt="otherUser.first_name"
          >
          <div class="ml-3">
            <h2 class="text-lg font-medium text-gray-900">
              {{ otherUser.first_name }} {{ otherUser.last_name }}
            </h2>
            <p class="text-sm text-gray-500">{{ otherUser.role === 'vendor' ? 'Vendor' : 'Customer' }}</p>
          </div>
        </div>
      </div>
      <div class="flex items-center space-x-2">
        <!-- Online Status -->
        <span
          :class="[
            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
            isOnline ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
          ]"
        >
          <span
            :class="[
              'w-2 h-2 mr-1 rounded-full',
              isOnline ? 'bg-green-400' : 'bg-gray-400'
            ]"
          ></span>
          {{ isOnline ? 'Online' : 'Offline' }}
        </span>
        <!-- More Actions -->
        <div class="relative">
          <button
            @click="showActionsMenu = !showActionsMenu"
            class="p-1 text-gray-600 hover:text-gray-900"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zM12 13a1 1 0 110-2 1 1 0 010 2zM12 20a1 1 0 110-2 1 1 0 010 2z" />
            </svg>
          </button>
          <div
            v-if="showActionsMenu"
            v-click-outside="() => showActionsMenu = false"
            class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10"
          >
            <div class="py-1">
              <button
                @click="toggleMute"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                {{ chat.customer_id === auth.user.id ? (chat.customer_muted ? 'Unmute' : 'Mute') : (chat.vendor_muted ? 'Unmute' : 'Mute') }}
              </button>
              <button
                @click="archiveChat"
                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
              >
                Archive
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Booking Info -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="font-medium text-blue-900">Booking Reference: {{ chat.booking.booking_number }}</h3>
          <p class="text-sm text-blue-700">
            {{ chat.booking.vehicle.brand }} {{ chat.booking.vehicle.model }} •
            {{ chat.booking.pickup_date }} to {{ chat.booking.return_date }}
          </p>
        </div>
        <a
          :href="`/bookings/${chat.booking.id}`"
          class="text-blue-600 hover:text-blue-800 text-sm font-medium"
        >
          View Booking
        </a>
      </div>
    </div>

    <!-- Messages Container -->
    <div class="bg-white border border-gray-200 rounded-lg" style="height: 500px;">
      <div
        ref="messagesContainer"
        class="p-4 overflow-y-auto"
        style="height: calc(100% - 80px);"
      >
        <div v-if="loadingMessages" class="text-center py-8">
          <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="message in messages"
            :key="message.id"
            :class="[
              'flex',
              message.sender_id === auth.user.id ? 'justify-end' : 'justify-start'
            ]"
          >
            <div
              :class="[
                'max-w-xs lg:max-w-md',
                message.sender_id === auth.user.id
                  ? 'bg-blue-600 text-white rounded-l-lg rounded-tr-lg'
                  : 'bg-gray-100 text-gray-900 rounded-r-lg rounded-tl-lg'
              ]"
            >
              <!-- Message Content -->
              <div class="p-3">
                <!-- Text Message -->
                <p v-if="message.message_type === 'text'" class="text-sm">
                  {{ message.message }}
                </p>

                <!-- Emoji Message -->
                <p v-else-if="message.message_type === 'emoji'" class="text-2xl">
                  {{ message.message }}
                </p>

                <!-- File Attachment -->
                <div v-else-if="message.chat_attachment" class="space-y-2">
                  <div class="flex items-center space-x-2">
                    <span class="text-2xl">{{ message.chat_attachment.getMimeIcon() }}</span>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium truncate">{{ message.chat_attachment.original_name }}</p>
                      <p class="text-xs opacity-75">{{ message.chat_attachment.file_size }}</p>
                    </div>
                    <a
                      :href="message.chat_attachment.getUrl()"
                      download
                      class="text-white hover:text-blue-100"
                    >
                      <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                      </svg>
                    </a>
                  </div>
                  <!-- Thumbnail for images -->
                  <img
                    v-if="message.chat_attachment.isImage() && message.chat_attachment.thumbnail_url"
                    :src="message.chat_attachment.getThumbnailUrl()"
                    :alt="message.chat_attachment.original_name"
                    class="rounded cursor-pointer hover:opacity-75"
                    @click="previewImage(message.chat_attachment)"
                  >
                </div>

                <!-- Location Message -->
                <div v-else-if="message.chat_location" class="space-y-2">
                  <div class="flex items-center space-x-2">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-sm font-medium">Location shared</span>
                  </div>
                  <p class="text-xs">
                    {{ message.chat_location.full_address }}
                  </p>
                  <a
                    v-if="message.chat_location.static_map_url"
                    :href="message.chat_location.getGoogleMapsUrl()"
                    target="_blank"
                    class="block"
                  >
                    <img
                      :src="message.chat_location.getStaticMapUrl()"
                      alt="Location map"
                      class="rounded cursor-pointer hover:opacity-75 w-full"
                    >
                  </a>
                </div>

                <!-- Edited indicator -->
                <div v-if="message.edited_at" class="flex items-center space-x-1 mt-1">
                  <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                  </svg>
                  <span class="text-xs opacity-75">edited</span>
                </div>
              </div>

              <!-- Timestamp and Actions -->
              <div
                :class="[
                  'flex items-center justify-between px-2 pb-1',
                  message.sender_id === auth.user.id ? 'text-blue-100' : 'text-gray-500'
                ]"
              >
                <span class="text-xs">{{ formatMessageTime(message.created_at) }}</span>
                <div v-if="message.sender_id === auth.user.id" class="flex items-center space-x-1">
                  <!-- Undo button (only if within deadline) -->
                  <button
                    v-if="canUndoMessage(message)"
                    @click="undoMessage(message)"
                    class="hover:opacity-75"
                    title="Undo"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                    </svg>
                  </button>
                  <!-- Edit button -->
                  <button
                    v-if="canEditMessage(message)"
                    @click="editMessage(message)"
                    class="hover:opacity-75"
                    title="Edit"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                  <!-- Delete button -->
                  <button
                    @click="deleteMessage(message)"
                    class="hover:opacity-75"
                    title="Delete"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="border-t border-gray-200 p-4">
        <div class="flex items-end space-x-2">
          <!-- Attach File Button -->
          <input
            ref="fileInput"
            type="file"
            class="hidden"
            @change="handleFileUpload"
            accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt"
          >
          <button
            @click="$refs.fileInput.click()"
            class="p-2 text-gray-600 hover:text-gray-900"
            title="Attach file"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
            </svg>
          </button>

          <!-- Location Button -->
          <button
            @click="shareLocation"
            class="p-2 text-gray-600 hover:text-gray-900"
            title="Share location"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </button>

          <!-- Message Input -->
          <div class="flex-1">
            <textarea
              v-model="newMessage"
              @keydown.enter.prevent="sendMessage"
              @keydown="handleTyping"
              placeholder="Type a message..."
              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              rows="1"
            ></textarea>
          </div>

          <!-- Emoji Button -->
          <button
            @click="showEmojiPicker = !showEmojiPicker"
            class="p-2 text-gray-600 hover:text-gray-900"
            title="Add emoji"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </button>

          <!-- Send Button -->
          <button
            @click="sendMessage"
            :disabled="!newMessage.trim() || sending"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="!sending" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
            </svg>
            <svg v-else class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </div>

        <!-- Emoji Picker (simple implementation) -->
        <div v-if="showEmojiPicker" class="mt-2 p-2 bg-white border border-gray-200 rounded-lg shadow-lg">
          <div class="grid grid-cols-8 gap-2">
            <button
              v-for="emoji in popularEmojis"
              :key="emoji.emoji"
              @click="addEmoji(emoji.emoji)"
              class="text-xl hover:bg-gray-100 p-1 rounded"
            >
              {{ emoji.emoji }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Typing Indicator -->
    <div v-if="typingUsers.length > 0" class="mt-2 text-sm text-gray-600">
      <div class="flex items-center space-x-2">
        <div class="flex space-x-1">
          <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
          <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
          <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
        </div>
        <span>{{ getTypingUsersText() }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import Echo from 'laravel-echo'

const page = usePage()
const props = computed(() => page.props)
const auth = computed(() => props.value.auth)

const chat = ref(null)
const messages = ref([])
const loadingMessages = ref(true)
const newMessage = ref('')
const sending = ref(false)
const showEmojiPicker = ref(false)
const showActionsMenu = ref(false)
const isOnline = ref(false)
const typingUsers = ref([])
const typingTimeout = ref(null)

const messagesContainer = ref(null)

const popularEmojis = [
  { emoji: '😊', name: 'smile' },
  { emoji: '❤️', name: 'heart' },
  { emoji: '👍', name: 'thumbs_up' },
  { emoji: '😂', name: 'laugh' },
  { emoji: '🎉', name: 'party' },
  { emoji: '😮', name: 'wow' },
  { emoji: '😢', name: 'sad' },
  { emoji: '🔥', name: 'fire' }
]

const otherUser = computed(() => {
  if (!chat.value) return null
  return chat.value.customer_id === auth.value.user.id ? chat.value.vendor : chat.value.customer
})

const loadChat = async () => {
  try {
    const response = await fetch(`/api/booking-chats/${chat.value.id}`, {
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()
    chat.value = data.data
    messages.value = data.data.messages.data || []

    // Scroll to bottom
    nextTick(() => {
      scrollToBottom()
    })
  } catch (error) {
    console.error('Failed to load chat:', error)
  } finally {
    loadingMessages.value = false
  }
}

const subscribeToChat = () => {
  if (!chat.value) return

  // Subscribe to chat channel
  Echo.private(`booking-chat.${chat.value.id}`)
    .listen('.message.new', (e) => {
      messages.value.push(e.data)
      scrollToBottom()
      playNotificationSound()
    })
    .listen('.message.edited', (e) => {
      const messageIndex = messages.value.findIndex(m => m.id === e.message_id)
      if (messageIndex !== -1) {
        messages.value[messageIndex].message = e.message
        messages.value[messageIndex].edited_at = e.edited_at
      }
    })
    .listen('.message.undo', (e) => {
      const messageIndex = messages.value.findIndex(m => m.id === e.message_id)
      if (messageIndex !== -1) {
        messages.value.splice(messageIndex, 1)
      }
    })
}

const sendMessage = async () => {
  if (!newMessage.value.trim() || sending.value) return

  sending.value = true
  try {
    const response = await fetch(`/api/booking-chats/${chat.value.id}/messages`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        booking_chat_id: chat.value.id,
        message: newMessage.value.trim(),
        message_type: 'text'
      })
    })

    const data = await response.json()
    if (data.success) {
      newMessage.value = ''
      // Message will be added via Pusher event
    }
  } catch (error) {
    console.error('Failed to send message:', error)
  } finally {
    sending.value = false
  }
}

const canEditMessage = (message) => {
  return message.sender_id === auth.value.user.id &&
         !message.edited_at &&
         new Date(message.created_at).getTime() > Date.now() - (15 * 60 * 1000)
}

const canUndoMessage = (message) => {
  return message.sender_id === auth.value.user.id &&
         message.undo_deadline &&
         new Date(message.undo_deadline).getTime() > Date.now()
}

const editMessage = (message) => {
  const newText = prompt('Edit message:', message.message)
  if (newText && newText !== message.message) {
    // TODO: Implement edit message API call
    console.log('Edit message:', message.id, newText)
  }
}

const deleteMessage = (message) => {
  if (confirm('Are you sure you want to delete this message?')) {
    // TODO: Implement delete message API call
    console.log('Delete message:', message.id)
  }
}

const undoMessage = async (message) => {
  try {
    const response = await fetch(`/api/chat-messages/${message.id}/undo`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    const data = await response.json()
    if (data.success) {
      // Message will be removed via Pusher event
    }
  } catch (error) {
    console.error('Failed to undo message:', error)
  }
}

const handleFileUpload = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  // TODO: Implement file upload API call
  console.log('Upload file:', file)
}

const shareLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        // TODO: Implement location sharing API call
        console.log('Location:', position.coords.latitude, position.coords.longitude)
      },
      (error) => {
        console.error('Location error:', error)
      }
    )
  } else {
    alert('Geolocation is not supported by your browser')
  }
}

const addEmoji = (emoji) => {
  newMessage.value += emoji
  showEmojiPicker.value = false
}

const handleTyping = () => {
  // TODO: Implement typing indicator
  clearTimeout(typingTimeout.value)
  typingTimeout.value = setTimeout(() => {
    // Send stop typing event
  }, 1000)
}

const getTypingUsersText = () => {
  if (typingUsers.value.length === 1) {
    return `${typingUsers.value[0].name} is typing...`
  } else {
    const names = typingUsers.value.map(u => u.name).join(', ')
    return `${names} are typing...`
  }
}

const scrollToBottom = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

const toggleMute = async () => {
  const action = chat.value.customer_id === auth.value.user.id
    ? (chat.value.customer_muted ? 'unmute' : 'mute')
    : (chat.value.vendor_muted ? 'unmute' : 'mute')

  try {
    const response = await fetch(`/api/booking-chats/${chat.value.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ action })
    })

    const data = await response.json()
    if (data.success) {
      chat.value = data.data
      showActionsMenu.value = false
    }
  } catch (error) {
    console.error('Failed to toggle mute:', error)
  }
}

const archiveChat = async () => {
  try {
    const response = await fetch(`/api/booking-chats/${chat.value.id}`, {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ action: 'archive' })
    })

    const data = await response.json()
    if (data.success) {
      router.visit('/booking-chats')
    }
  } catch (error) {
    console.error('Failed to archive chat:', error)
  }
}

const playNotificationSound = () => {
  // TODO: Implement notification sound
}

const formatMessageTime = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const previewImage = (attachment) => {
  // TODO: Implement image preview modal
  console.log('Preview image:', attachment)
}

onMounted(async () => {
  const chatId = window.location.pathname.split('/').pop()

  // Load chat data
  try {
    const response = await fetch(`/api/booking-chats/${chatId}`, {
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()
    chat.value = data.data
    messages.value = data.data.messages.data || []

    // Subscribe to real-time updates
    subscribeToChat()

    // Mark messages as read
    await fetch(`/api/booking-chats/${chatId}/mark-as-read`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${auth.value.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    // Scroll to bottom
    nextTick(() => {
      scrollToBottom()
    })

  } catch (error) {
    console.error('Failed to load chat:', error)
  } finally {
    loadingMessages.value = false
  }
})
</script>