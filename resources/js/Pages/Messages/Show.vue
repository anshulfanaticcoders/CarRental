<!-- resources/js/Pages/Messages/Show.vue -->
<template>
    <div class="container mx-auto p-4">
      <div class="flex items-center mb-6">
        <button 
          @click="router.get(route('messages.index'))" 
          class="mr-4 text-blue-600 hover:text-blue-800"
        >
          ← Back to Messages
        </button>
        <h1 class="text-2xl font-bold">Conversation</h1>
      </div>
  
      <div class="space-y-4 mb-6">
        <div v-for="message in messages" :key="message.id" 
             class="p-4 rounded-lg" 
             :class="[message.sender_id === $page.props.auth.user.id 
                     ? 'bg-blue-100 ml-auto max-w-[80%]' 
                     : 'bg-gray-100 mr-auto max-w-[80%]']">
          <div class="flex justify-between items-start">
            <p class="font-semibold">{{ message.sender.name }}</p>
            <small class="text-gray-500">
              {{ new Date(message.created_at).toLocaleString() }}
            </small>
          </div>
          <p class="mt-2">{{ message.message }}</p>
          <button 
            v-if="!message.read_at && message.receiver_id === $page.props.auth.user.id"
            @click="markAsRead(message)" 
            class="text-sm text-blue-600 hover:text-blue-800 mt-2"
          >
            Mark as Read
          </button>
        </div>
      </div>
      
      <form @submit.prevent="sendMessage" class="mt-6">
        <textarea 
          v-model="form.message" 
          required
          class="w-full p-2 border rounded-lg"
          placeholder="Type your message..."
          rows="3"
        ></textarea>
        <button 
          type="submit" 
          class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          :disabled="form.processing"
        >
          Send
        </button>
      </form>
    </div>
  </template>
  
  <script setup>
  import { useForm, router } from '@inertiajs/vue3';
  
  const props = defineProps({
    messages: {
      type: Array,
      required: true
    },
    receiverId: {
      type: Number,
      required: true
    }
  });
  
  const form = useForm({
    receiver_id: props.receiverId,
    message: '',
  });
  
  const sendMessage = () => {
    form.post(route('messages.store'), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };
  
  const markAsRead = async (message) => {
    try {
      await axios.post(route('messages.read', message.id));
      message.read_at = new Date().toISOString();
    } catch (error) {
      console.error('Error marking message as read:', error);
    }
  };
  </script>