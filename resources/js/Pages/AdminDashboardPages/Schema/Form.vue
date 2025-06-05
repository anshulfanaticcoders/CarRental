<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { defineProps, computed } from 'vue';

const props = defineProps({
  schema: {
    type: Object,
    default: null,
  },
});

const isEditing = computed(() => !!props.schema);
const formTitle = computed(() => isEditing.value ? 'Edit Schema' : 'Create New Schema');

const form = useForm({
  _method: isEditing.value ? 'PUT' : 'POST',
  name: props.schema?.name || '',
  type: props.schema?.type || '',
  content: props.schema?.content || `<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "YourSchemaType",
  "name": "Your Name",
  "description": "Your Description"
}
<\/script>`, // Using template literal and escaped slash
  is_active: props.schema?.is_active === undefined ? true : !!props.schema?.is_active,
});

const schemaTypes = [
    'Article', 'BlogPosting', 'BreadcrumbList', 'Event', 'FAQPage', 'HowTo', 
    'ImageObject', 'JobPosting', 'LocalBusiness', 'Logo', 'Movie', 'Organization', 
    'Person', 'Product', 'Question', 'Recipe', 'Review', 'Service', 'VideoObject', 
    'WebPage', 'WebSite', 'Dataset', 'Course', 'Book', 'SoftwareApplication'
];

const submitForm = () => {
  if (isEditing.value) {
    form.post(route('admin.schemas.update', props.schema.id), {
      preserveScroll: true,
    });
  } else {
    form.post(route('admin.schemas.store'), {
      preserveScroll: true,
    });
  }
};
</script>

<template>
  <AdminDashboardLayout :title="formTitle">
    <div class="py-12">
      <div class="mx-auto sm:px-6 lg:px-8"> <!-- Matched SEO/Form.vue -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 sm:px-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200 mb-6">{{ formTitle }}</h2>

            <form @submit.prevent="submitForm">
              <div class="grid grid-cols-1 gap-6">
                
                <!-- Schema Name -->
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Schema Name</label>
                  <input type="text" id="name" v-model="form.name" 
                         class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md" 
                         placeholder="e.g., Homepage FAQ Schema" required />
                  <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <!-- Schema Type -->
                <div>
                  <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Schema Type</label>
                  <select id="type" v-model="form.type" 
                          class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md" 
                          required>
                    <option value="" disabled>Select a type</option>
                    <option v-for="sType in schemaTypes" :key="sType" :value="sType">{{ sType }}</option>
                  </select>
                  <p v-if="form.errors.type" class="mt-1 text-xs text-red-500">{{ form.errors.type }}</p>
                </div>

                <!-- Schema Content -->
                <div>
                  <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Schema Content (Full <code>&lt;script&gt;</code> Tag)</label>
                  <textarea id="content" v-model="form.content" rows="15" 
                            class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 rounded-md font-mono" 
                            placeholder="Paste your full schema.org script here..." required></textarea>
                  <p v-if="form.errors.content" class="mt-1 text-xs text-red-500">{{ form.errors.content }}</p>
                </div>

                <!-- Is Active -->
                <div>
                  <label class="flex items-center">
                    <input type="checkbox" v-model="form.is_active" 
                           class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:bg-gray-700 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" /> 
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Active</span>
                  </label>
                  <p v-if="form.errors.is_active" class="mt-1 text-xs text-red-500">{{ form.errors.is_active }}</p>
                </div>
                
              </div> <!-- End of grid -->

              <div class="mt-8 flex justify-end space-x-3">
                <Link :href="route('admin.schemas.index')" 
                      class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-600 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-500 active:bg-gray-400 dark:active:bg-gray-700 focus:outline-none focus:border-gray-900 dark:focus:border-gray-300 focus:ring focus:ring-gray-300 dark:focus:ring-gray-700 disabled:opacity-25 transition">
                  Cancel
                </Link>
                <button type="submit" :disabled="form.processing" 
                        class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-blue-500 active:bg-gray-900 dark:active:bg-blue-700 focus:outline-none focus:border-gray-900 dark:focus:border-blue-700 focus:ring focus:ring-gray-300 dark:focus:ring-blue-200 disabled:opacity-25 transition">
                  {{ isEditing ? 'Update' : 'Create' }} Schema
                </button>
              </div> <!-- End of actions div -->
            </form> <!-- End of form -->
          </div> <!-- End of p-6 -->
        </div> <!-- End of bg-white -->
      </div> <!-- End of mx-auto -->
    </div> <!-- End of py-12 -->
  </AdminDashboardLayout>
</template>
