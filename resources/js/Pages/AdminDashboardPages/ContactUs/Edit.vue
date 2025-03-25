<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';

const props = defineProps({
  contactPage: {
    type: Object,
    default: () => ({
      hero_title: '',
      hero_description: '',
      hero_image_url: '',
      contact_points: [],
      intro_text: '',
      phone_number: '',
      email: '',
      address: ''
    })
  }
});

const form = useForm({
  hero_title: props.contactPage.hero_title || '',
  hero_description: props.contactPage.hero_description || '',
  hero_image: null,
  contact_points: props.contactPage.contact_points || [],
  intro_text: props.contactPage.intro_text || '',
  phone_number: props.contactPage.phone_number || '',
  email: props.contactPage.email || '',
  address: props.contactPage.address || ''
});

const heroImagePreview = ref(props.contactPage.hero_image_url);

const handleHeroImageUpload = (event) => {
  const file = event.target.files[0];
  form.hero_image = file;
  
  // Create preview
  const reader = new FileReader();
  reader.onload = (e) => {
    heroImagePreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const addContactPoint = () => {
  form.contact_points.push({
    icon: '',
    title: ''
  });
};

const removeContactPoint = (index) => {
  form.contact_points.splice(index, 1);
};

const submit = () => {
  form.post(route('admin.contact-us.update'), {
    preserveScroll: true
  });
};
</script>

<template>
  <AdminDashboardLayout>
    <Head title="Edit Contact Us Page" />
    
    <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem] mt-5">
      <h1 class="text-2xl font-bold mb-6">Edit Contact Us Page</h1>
      
      <form @submit.prevent="submit" class="bg-white shadow-md rounded p-6">
        <!-- Hero Section -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Title</label>
          <input 
            v-model="form.hero_title" 
            type="text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Description</label>
          <textarea 
            v-model="form.hero_description" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
            rows="4"
          ></textarea>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Hero Image</label>
          <input 
            type="file" 
            @change="handleHeroImageUpload"
            accept="image/*"
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
          
          <div v-if="heroImagePreview" class="mt-4">
            <img 
              :src="heroImagePreview" 
              alt="Hero Image Preview" 
              class="w-[50rem] h-[30rem] object-cover"
            />
          </div>
        </div>

        <!-- Contact Points -->
        <div class="mb-4">
          <h2 class="text-xl font-semibold mb-2">Contact Points</h2>
          <div 
            v-for="(point, index) in form.contact_points" 
            :key="index" 
            class="flex items-center mb-2"
          >
            <input 
              v-model="point.icon" 
              type="text" 
              placeholder="Icon URL" 
              class="mr-2 shadow appearance-none border rounded py-2 px-3 text-gray-700"
            />
            <input 
              v-model="point.title" 
              type="text" 
              placeholder="Point Title" 
              class="mr-2 shadow appearance-none border rounded py-2 px-3 text-gray-700"
            />
            <button 
              type="button" 
              @click="removeContactPoint(index)"
              class="bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600"
            >
              Remove
            </button>
          </div>
          <button 
            type="button" 
            @click="addContactPoint"
            class="mt-2 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
          >
            Add Contact Point
          </button>
        </div>

        <!-- Company Intro -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Company Intro</label>
          <textarea 
            v-model="form.intro_text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
            rows="4"
          ></textarea>
        </div>

        <!-- Contact Information -->
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Phone Number</label>
          <input 
            v-model="form.phone_number" 
            type="text" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
          />
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Address</label>
          <textarea 
            v-model="form.address" 
            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"
            rows="3"
          ></textarea>
        </div>

        <div class="flex items-center justify-between">
          <button 
            type="submit" 
            :disabled="form.processing"
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
          <a 
            :href="route('admin.contact-us.index')"
            class="text-gray-600 hover:text-gray-800"
          >
            Cancel
          </a>
        </div>
      </form>
    </div>
  </AdminDashboardLayout>
</template>