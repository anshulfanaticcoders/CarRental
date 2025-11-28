<template>
  <div class="container mx-auto px-4 py-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Messages</h1>
      <p class="text-gray-600">Chat with vendors about your bookings</p>
    </div>

    <!-- Search Bar -->
    <div class="mb-6">
      <div class="relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search conversations..."
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          @input="handleSearch"
        >
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
          <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Chat List -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      <p class="mt-4 text-gray-600">Loading conversations...</p>
    </div>

    <div v-else-if="chats.data.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
      <h3 class="mt-4 text-lg font-medium text-gray-900">No conversations yet</h3>
      <p class="mt-2 text-gray-600">Start a conversation when you make a booking</p>
    </div>

    <div v-else class="space-y-4">
      <!-- Unread Count Badge -->
      <div v-if="unreadCount > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6z" />
              <path d="M10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm font-medium text-blue-800">
              You have {{ unreadCount }} unread message{{ unreadCount > 1 ? 's' : '' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Chat Items -->
      <div
        v-for="chat in chats.data"
        :key="chat.id"
        class="bg-white border border-gray-200 rounded-lg hover:shadow-md transition-shadow cursor-pointer"
        @click="openChat(chat)"
      >
        <div class="p-4">
          <div class="flex items-center">
            <!-- Avatar -->
            <div class="flex-shrink-0">
              <img
                class="h-12 w-12 rounded-full object-cover"
                :src="getOtherUser(chat).profile_image || '/images/default-avatar.png'"
                :alt="getOtherUser(chat).first_name"
              >
            </div>

            <!-- Chat Info -->
            <div class="ml-4 flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 truncate">
                  {{ getOtherUser(chat).first_name }} {{ getOtherUser(chat).last_name }}
                </h3>
                <span class="text-sm text-gray-500">
                  {{ formatTime(chat.last_message_at) }}
                </span>
              </div>

              <div class="mt-1 flex items-center justify-between">
                <p class="text-sm text-gray-600 truncate">
                  {{ chat.last_message_preview }}
                </p>
                <div class="flex items-center space-x-2">
                  <!-- Unread Count -->
                  <span
                    v-if="getUnreadCountForChat(chat) > 0"
                    class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full"
                  >
                    {{ getUnreadCountForChat(chat) }}
                  </span>
                  <!-- Muted Indicator -->
                  <svg
                    v-if="isChatMuted(chat)"
                    class="h-4 w-4 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    title="Muted"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                  </svg>
                </div>
              </div>

              <!-- Booking Info -->
              <div class="mt-2 text-xs text-gray-500">
                {{ chat.booking.vehicle.brand }} {{ chat.booking.vehicle.model }} •
                {{ chat.booking.pickup_date }} to {{ chat.booking.return_date }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="chats.links && chats.links.length > 3" class="mt-6">
        <nav class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              :disabled="!chats.prev_page_url"
              @click="loadPage(chats.prev_page_url)"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              :disabled="!chats.next_page_url"
              @click="loadPage(chats.next_page_url)"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ chats.from || 1 }}</span>
                to
                <span class="font-medium">{{ chats.to || chats.data.length }}</span>
                of
                <span class="font-medium">{{ chats.total }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button
                  v-for="(link, index) in chats.links"
                  :key="index"
                  :disabled="!link.url || link.active"
                  @click="link.url && loadPage(link.url)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    link.active
                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                    index === 0 ? 'rounded-l-md' : '',
                    index === chats.links.length - 1 ? 'rounded-r-md' : '',
                    !link.url ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
                  ]"
                  v-html="link.label"
                />
              </nav>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'

const props = defineProps({
  auth: {
    type: Object,
    required: true
  }
})

const chats = ref({ data: [], links: [] })
const loading = ref(true)
const searchQuery = ref('')
const unreadCount = ref(0)

const loadChats = async (url = null) => {
  loading.value = true
  try {
    const response = await fetch(url || '/api/booking-chats', {
      headers: {
        'Authorization': `Bearer ${props.auth.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()
    chats.value = data.data
  } catch (error) {
    console.error('Failed to load chats:', error)
  } finally {
    loading.value = false
  }
}

const loadUnreadCount = async () => {
  try {
    const response = await fetch('/api/booking-chats/unread-count', {
      headers: {
        'Authorization': `Bearer ${props.auth.user.token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })
    const data = await response.json()
    unreadCount.value = data.data.unread_count
  } catch (error) {
    console.error('Failed to load unread count:', error)
  }
}

const handleSearch = debounce(async () => {
  if (searchQuery.value.trim()) {
    try {
      const response = await fetch(`/api/booking-chats/search?query=${encodeURIComponent(searchQuery.value)}`, {
        headers: {
          'Authorization': `Bearer ${props.auth.user.token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      const data = await response.json()
      chats.value = data.data
    } catch (error) {
      console.error('Failed to search chats:', error)
    }
  } else {
    await loadChats()
  }
}, 300)

const loadPage = async (url) => {
  await loadChats(url)
}

const openChat = (chat) => {
  router.visit(`/booking-chats/${chat.id}`)
}

const getOtherUser = (chat) => {
  return chat.customer_id === props.auth.user.id ? chat.vendor : chat.customer
}

const getUnreadCountForChat = (chat) => {
  return chat.customer_id === props.auth.user.id ? chat.customer_unread_count : chat.vendor_unread_count
}

const isChatMuted = (chat) => {
  return chat.customer_id === props.auth.user.id ? chat.customer_muted : chat.vendor_muted
}

const formatTime = (dateString) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))

  if (diffInMinutes < 1) {
    return 'Just now'
  } else if (diffInMinutes < 60) {
    return `${diffInMinutes}m ago`
  } else if (diffInMinutes < 1440) {
    return `${Math.floor(diffInMinutes / 60)}h ago`
  } else {
    return date.toLocaleDateString()
  }
}

onMounted(async () => {
  await Promise.all([loadChats(), loadUnreadCount()])
})
</script>