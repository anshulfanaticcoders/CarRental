<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    contactPage: { // Main model data: id, hero_image_url, phone_number, email, address, contact_point_icons
        type: Object,
        default: () => ({
            id: null,
            hero_image_url: '',
            phone_number: '',
            email: '',
            address: '',
            contact_point_icons: [] // Array of icon URLs/strings
        })
    },
    translation: { // Translated content: hero_title, hero_description, intro_text, contact_points (textual parts)
        type: Object,
        default: () => ({
            hero_title: '',
            hero_description: '',
            intro_text: '',
            contact_points: [] // Expected to be [{ title: '...' }, { title: '...' }, ...]
        })
    }
});
</script>

<template>
  <AdminDashboardLayout>
    <Head title="Contact Us Page" />
    
    <div class="flex flex-col gap-4 w-[95%] ml-[1.5rem]">
      <div class="mt-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold mb-6">Contact Us Page Details</h1>
          <a 
            :href="route('admin.contact-us.edit')" 
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
          >
            Edit Page Content
          </a>
        </div>
      
      <div v-if="contactPage.id" class="bg-white shadow-md rounded p-6">
        <div class="mb-4">
          <h2 class="text-xl font-semibold">Hero Section</h2>
          <p><strong>Title:</strong> {{ translation?.hero_title || 'N/A' }}</p>
          <p><strong>Description:</strong> {{ translation?.hero_description || 'N/A' }}</p>
          
          <div v-if="contactPage.hero_image_url" class="mt-4">
            <img 
              :src="contactPage.hero_image_url" 
              alt="Hero Image" 
              class="w-full max-w-2xl h-auto object-cover rounded"
            />
          </div>
           <p v-else class="text-gray-500">No hero image uploaded.</p>
        </div>

        <div class="mb-4">
          <h2 class="text-xl font-semibold">Contact Points</h2>
          <ul v-if="translation?.contact_points && translation.contact_points.length">
            <li 
              v-for="(textPoint, index) in translation.contact_points" 
              :key="index"
              class="flex items-center mb-2 p-2 border rounded"
            >
              <!-- Get icon from the main contactPage model using the same index -->
              <img 
                v-if="contactPage.contact_point_icons && contactPage.contact_point_icons[index]"
                :src="contactPage.contact_point_icons[index]" 
                alt="Contact Point Icon"
                class="w-6 h-6 mr-3 object-contain"
              />
              <span v-else class="w-6 h-6 mr-3 text-gray-400">[No Icon]</span>
              <span>{{ textPoint.title || (textPoint || 'N/A') }}</span> 
              <!-- Adjust if textPoint is just a string or an object with more fields -->
            </li>
          </ul>
          <p v-else class="text-gray-500">No contact points available for this language, or no icons defined.</p>
        </div>

        <div class="mb-4">
          <h2 class="text-xl font-semibold">Company Intro</h2>
          <p>{{ translation?.intro_text || 'N/A' }}</p>
        </div>

        <div class="mb-4">
          <h2 class="text-xl font-semibold">Contact Information</h2>
          <p><strong>Phone:</strong> {{ contactPage.phone_number }}</p>
          <p><strong>Email:</strong> {{ contactPage.email }}</p>
          <p><strong>Address:</strong> {{ contactPage.address }}</p>
        </div>
      </div>

      <div v-else class="bg-white shadow-md rounded p-6">
        <p class="text-gray-600">No contact page content has been created yet.</p>
        <a 
          :href="route('admin.contact-us.edit')" 
          class="mt-4 inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
        >
          Create Contact Page
        </a>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
