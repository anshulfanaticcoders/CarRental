<template>
  <AdminDashboardLayout :title="formTitle">
    <div class="py-12">
      <div class="mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">{{ formTitle }}</h2>

            <form @submit.prevent="submitForm">
              <div class="grid grid-cols-1 gap-6">
                <!-- Header Script -->
                <div>
                  <label for="header_script" class="block text-sm font-medium text-gray-700">Header Script</label>
                  <textarea id="header_script" v-model="form.header_script" rows="10" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Enter script or style tags here..."></textarea>
                  <p v-if="form.errors.header_script" class="mt-1 text-xs text-red-500">{{ form.errors.header_script }}</p>
                  <p v-else class="mt-1 text-xs text-gray-500">Scripts or styles to be injected into the 'head' section of the website.</p>
                </div>

                <!-- Footer Script -->
                <div>
                  <label for="footer_script" class="block text-sm font-medium text-gray-700">Footer Script</label>
                  <textarea id="footer_script" v-model="form.footer_script" rows="10" class="mt-1 p-2 border-2 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Enter script tags here..."></textarea>
                  <p v-if="form.errors.footer_script" class="mt-1 text-xs text-red-500">{{ form.errors.footer_script }}</p>
                  <p v-else class="mt-1 text-xs text-gray-500">Scripts to be injected before the closing 'body' tag.</p>
                </div>
              </div>

              <div class="mt-8 flex justify-end space-x-3">
                <Link :href="route('admin.header-footer-scripts.index')" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                  Cancel
                </Link>
                <button type="submit" :disabled="form.processing" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                  {{ isEditing ? 'Update' : 'Create' }} Script
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
  headerFooterScript: {
    type: Object,
    default: null, // Null for create, object for edit
  },
});

const isEditing = computed(() => !!props.headerFooterScript);
const formTitle = computed(() => isEditing.value ? 'Edit Header/Footer Script' : 'Create New Header/Footer Script');

const form = useForm({
  _method: isEditing.value ? 'PUT' : 'POST',
  header_script: props.headerFooterScript?.header_script || '',
  footer_script: props.headerFooterScript?.footer_script || '',
});

const submitForm = () => {
  if (isEditing.value) {
    form.post(route('admin.header-footer-scripts.update', props.headerFooterScript.id), {
      preserveScroll: true,
    });
  } else {
    form.post(route('admin.header-footer-scripts.store'), {
      preserveScroll: true,
    });
  }
};
</script>

<style scoped>
/* Add any page-specific styles here */
</style>
