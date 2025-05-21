<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'; // Assuming an AdminDashboardLayout exists
import { defineProps, ref } from 'vue'; // Added ref
import MediaLibraryModal from '@/Components/MediaLibraryModal.vue'; // Import the modal

const props = defineProps({
  feature: Object, // Contains feature data, including its category
  errors: Object, // For displaying validation errors
});

const { flash } = usePage().props;

const form = useForm({
  _method: 'PUT', // Important for Laravel to recognize it as an update
  feature_name: props.feature.feature_name,
  icon_url: props.feature.icon_url || '', // Ensure it's an empty string if null
});

const showMediaModal = ref(false);

const openMediaModal = () => {
  showMediaModal.value = true;
};

const handleMediaSelected = (url) => {
  form.icon_url = url;
  showMediaModal.value = false; // Close modal after selection
};

const submit = () => {
  form.post(route('admin.features.update', props.feature.id), {
    preserveScroll: true,
    onSuccess: () => {
      // Flash message will be shown from Index page upon redirect
    },
    // onError will automatically populate props.errors
  });
};
</script>

<template>
  <Head :title="'Edit Feature: ' + feature.feature_name" />

  <AdminDashboardLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Feature: <span class="font-bold">{{ feature.feature_name }}</span>
        (Category: {{ feature.category.name }})
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">

            <div v-if="flash && flash.success" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded">
              {{ flash.success }}
            </div>
            <div v-if="flash && flash.error" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded">
              {{ flash.error }}
            </div>

            <form @submit.prevent="submit">
              <div class="mb-4">
                <label for="feature_name" class="block text-sm font-medium text-gray-700">Feature Name</label>
                <input type="text" v-model="form.feature_name" id="feature_name"
                       class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md"
                       required />
                <div v-if="errors && errors.feature_name" class="text-red-500 text-xs mt-1">{{ errors.feature_name }}</div>
              </div>

              <div class="mb-4">
                <label for="icon_url" class="block text-sm font-medium text-gray-700">Icon URL (Optional)</label>
                <div class="mt-1 flex rounded-md shadow-sm">
                  <input type="url" v-model="form.icon_url" id="icon_url"
                         class="focus:ring-indigo-500 focus:border-indigo-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" />
                  <button type="button" @click="openMediaModal"
                          class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm hover:bg-gray-100">
                    Choose from Library
                  </button>
                </div>
                <div v-if="form.icon_url" class="mt-2">
                    <img :src="form.icon_url" alt="Icon Preview" class="h-16 w-16 object-cover rounded" />
                </div>
                <div v-if="errors && errors.icon_url" class="text-red-500 text-xs mt-1">{{ errors.icon_url }}</div>
              </div>

              <div class="flex items-center justify-end mt-6">
                <Link :href="route('admin.features.index')"
                      class="text-sm text-gray-600 hover:text-gray-900 mr-4">
                  Cancel
                </Link>
                <button type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-700 disabled:bg-green-300">
                  Update Feature
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <MediaLibraryModal 
      :show="showMediaModal" 
      @close="showMediaModal = false"
      @media-selected="handleMediaSelected" 
    />
  </AdminDashboardLayout>
</template>
