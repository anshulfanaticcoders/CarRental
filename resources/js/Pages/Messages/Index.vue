<!-- resources/js/Pages/Messages/Index.vue -->
<template>
  <div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Messages</h1>
    
    <!-- Message List -->
    <div class="space-y-4">
      <div v-for="message in uniqueConversations" :key="message.id" 
           class="p-4 border rounded-lg hover:bg-gray-50 cursor-pointer"
           @click="goToConversation(message)">
        <div class="flex justify-between items-start">
          <p class="font-semibold">
            {{ message.sender_id === $page.props.auth.user.id 
               ? message.receiver.name 
               : message.sender.name }}
          </p>
          <small class="text-gray-500">
            {{ new Date(message.created_at).toLocaleString() }}
          </small>
        </div>
        <p class="mt-2 text-gray-600 truncate">{{ message.message }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  messages: {
    type: Array,
    required: true
  }
});

// Get unique conversations by combining sender and receiver
const uniqueConversations = computed(() => {
  const conversations = new Map();
  
  props.messages.forEach(message => {
    const otherUserId = message.sender_id === $page.props.auth.user.id 
      ? message.receiver_id 
      : message.sender_id;
      
    if (!conversations.has(otherUserId) || 
        conversations.get(otherUserId).created_at < message.created_at) {
      conversations.set(otherUserId, message);
    }
  });
  
  return Array.from(conversations.values());
});

const goToConversation = (message) => {
  const otherUserId = message.sender_id === $page.props.auth.user.id 
    ? message.receiver_id 
    : message.sender_id;
    
  router.get(route('messages.show', otherUserId));
};
</script>