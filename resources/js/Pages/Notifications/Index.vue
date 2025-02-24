<template>
  <div>
    <h1>Notifications</h1>
    <div v-for="notification in notifications" :key="notification.id">
      <p>{{ notification.title }}: {{ notification.message }}</p>
      <button @click="markAsRead(notification)" v-if="!notification.read_at">Mark as Read</button>
    </div>
  </div>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
  notifications: Array,
});

const markAsRead = (notification) => {
  axios.post(route('notifications.read', notification.id));
};
</script>