<template>
  <AdminDashboardLayout :title="formTitle">
    <div class="py-12">
      <div class="mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">{{ formTitle }}</h2>

            <form @submit.prevent="submitForm">
              <div class="grid grid-cols-1 gap-6">
                <!-- URL Slug -->
                <div>
                  <label for="url_slug" class="block text-sm font-medium text-gray-700">URL Slug</label>
                  <input type="text" id="url_slug" v-model="form.url_slug" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="custom-url-path">
                  <p v-if="form.errors.url_slug" class="mt-1 text-xs text-red-500">{{ form.errors.url_slug }}</p>
                  <p v-else class="mt-1 text-xs text-gray-500">Leave empty if not applicable. Must be unique. (e.g., about-us)</p>
                </div>

                <!-- SEO Title -->
                <div>
                  <label for="seo_title" class="block text-sm font-medium text-gray-700">SEO Title (0/60)</label>
                  <input type="text" id="seo_title" v-model="form.seo_title" maxlength="60" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="SEO title for the page">
                  <p v-if="form.errors.seo_title" class="mt-1 text-xs text-red-500">{{ form.errors.seo_title }}</p>
                </div>

                <!-- Meta Description -->
                <div>
                  <label for="meta_description" class="block text-sm font-medium text-gray-700">Meta Description (0/160)</label>
                  <textarea id="meta_description" v-model="form.meta_description" maxlength="160" rows="3" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Short and compelling description for search engines"></textarea>
                  <p v-if="form.errors.meta_description" class="mt-1 text-xs text-red-500">{{ form.errors.meta_description }}</p>
                </div>

                <!-- Keywords -->
                <div>
                  <label for="keywords" class="block text-sm font-medium text-gray-700">Keywords (comma-separated)</label>
                  <input type="text" id="keywords" v-model="form.keywords" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="keyword1, keyword2, keyword3...">
                  <p v-if="form.errors.keywords" class="mt-1 text-xs text-red-500">{{ form.errors.keywords }}</p>
                </div>

                <!-- Canonical URL -->
                <div>
                  <label for="canonical_url" class="block text-sm font-medium text-gray-700">Canonical URL</label>
                  <input type="url" id="canonical_url" v-model="form.canonical_url" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="https://yourdomain.com/preferred-url">
                  <p v-if="form.errors.canonical_url" class="mt-1 text-xs text-red-500">{{ form.errors.canonical_url }}</p>
                  <p v-else class="mt-1 text-xs text-gray-500">The preferred version of a URL if duplicate content exists.</p>
                </div>

                <!-- SEO Image URL -->
                <div>
                  <label for="seo_image_url" class="block text-sm font-medium text-gray-700">SEO Image URL</label>
                  <input type="url" id="seo_image_url" v-model="form.seo_image_url" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="https://yourdomain.com/path/to/image.jpg">
                  <p v-if="form.errors.seo_image_url" class="mt-1 text-xs text-red-500">{{ form.errors.seo_image_url }}</p>
                  <p v-else class="mt-1 text-xs text-gray-500">Image for social sharing (Open Graph image).</p>
                </div>
              </div>

              <div class="mt-8 flex justify-end space-x-3">
                <Link :href="route('admin.seo-meta.index')" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                  Cancel
                </Link>
                <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                  {{ isEditing ? 'Update' : 'Create' }} SEO Meta
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { defineProps, computed } from 'vue';

const props = defineProps({
  seoMeta: {
    type: Object,
    default: null, // Null for create, object for edit
  },
  // errors: Object, // Passed by Inertia on validation failure
});

const isEditing = computed(() => !!props.seoMeta);
const formTitle = computed(() => isEditing.value ? 'Edit SEO Meta' : 'Create New SEO Meta');

const form = useForm({
  _method: isEditing.value ? 'PUT' : 'POST', // Important for Laravel to handle PUT requests for updates
  url_slug: props.seoMeta?.url_slug || '',
  seo_title: props.seoMeta?.seo_title || '',
  meta_description: props.seoMeta?.meta_description || '',
  keywords: props.seoMeta?.keywords || '',
  canonical_url: props.seoMeta?.canonical_url || '',
  seo_image_url: props.seoMeta?.seo_image_url || '',
});

const submitForm = () => {
  if (isEditing.value) {
    form.post(route('admin.seo-meta.update', props.seoMeta.id), {
      preserveScroll: true,
      // onSuccess: () => form.reset(), // Or handle success message
    });
  } else {
    form.post(route('admin.seo-meta.store'), {
      preserveScroll: true,
      // onSuccess: () => form.reset(), // Or handle success message
    });
  }
};
</script>

<style scoped>
/* Add any page-specific styles here */
</style>
