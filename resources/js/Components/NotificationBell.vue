<template>
    <div class="relative">
      <button @click="toggleDropdown" class="relative">
        <svg
          class="w-6 h-6 text-gray-500 hover:text-gray-700"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.437L4 17h5m2 0v2a2 2 0 11-4 0v-2m4 0H9"
          ></path>
        </svg>
        <span
          v-if="unreadCount > 0"
          class="absolute bottom-0 right-[-50%] inline-flex items-center justify-center px-[7px] py-[5px] text-[0.65rem] font-bold leading-none text-red-100 bg-red-600 rounded-full"
        >
          {{ unreadCount }}
        </span>
      </button>
      <div
        v-if="dropdownOpen"
        class="absolute right-0 z-50 mt-2 w-64 bg-white border border-gray-200 rounded-md shadow-lg"
      >
        <div class="py-2 px-4 text-gray-700 font-semibold border-b">Notifications</div>
        <div v-if="!notifications || notifications.length === 0" class="py-2 px-4 text-gray-500">No notifications</div>
        <div v-else>
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="py-2 px-4 hover:bg-gray-100 cursor-pointer border-b"
          >
            <a :href="`/messages/${notification.user_id}`" class="block w-full h-full">
              <div class="font-semibold">{{ notification.title }}</div>
              <div class="text-sm text-gray-600">{{ notification.message }}</div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted } from "vue";
  import axios from "axios";
 
  const dropdownOpen = ref(false);
  const unreadCount = ref(0);
  const notifications = ref([]);
  
  const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
  };
  
  const fetchNotifications = async () => {
    try {
      const response = await axios.get("/notifications");
      if (response.status === 200) {
        notifications.value = response.data.notifications || [];
        unreadCount.value = response.data.unread_count || 0;
      } else {
        console.error("Failed to fetch notifications:", response.data.message);
      }
    } catch (error) {
      console.error("Error fetching notifications:", error);
    }
  };
  
  onMounted(fetchNotifications);
  </script>
  
  <style scoped>
  .relative {
    position: relative;
  }
  </style>