<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'; // Changed to AdminDashboardLayout
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { defineProps } from 'vue';
// Assuming you might want icons, adjust if not using lucide-vue-next or if SEO index uses different icons
// import { PlusIcon, PencilIcon, TrashIcon } from 'lucide-vue-next'; 

const props = defineProps({
  schemas: Object, // Contains data and links for pagination
});

const formatDate = (dateString) => {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleDateString();
};

const confirmDelete = (id) => {
  if (confirm('Are you sure you want to delete this schema?')) {
    router.delete(route('admin.schemas.destroy', id), {
      preserveScroll: true,
      // onSuccess: () => { /* Handle success if needed */ },
      // onError: (errors) => { console.error('Error deleting schema:', errors); alert('Error deleting schema.'); }
    });
  }
};
</script>

<template>
  <Head title="Manage Schemas" />

  <AdminDashboardLayout title="Schema Management">
    <div class="py-12">
      <div class="mx-auto sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
          {{ $page.props.flash.error }}
        </div>

        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 sm:px-20 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">Schema Scripts</h2>
              <Link :href="route('admin.schemas.create')" 
                class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-blue-500 active:bg-gray-900 dark:active:bg-blue-700 focus:outline-none focus:border-gray-900 dark:focus:border-blue-700 focus:ring focus:ring-gray-300 dark:focus:ring-blue-200 disabled:opacity-25 transition">
                Add New Schema
              </Link>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Name
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Type
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Status
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Last Updated
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                      <span class="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-if="!props.schemas || props.schemas.data.length === 0">
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                      No schemas found.
                    </td>
                  </tr>
                  <tr v-for="schemaItem in props.schemas.data" :key="schemaItem.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                      {{ schemaItem.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ schemaItem.type }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                      <span :class="schemaItem.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300'"
                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ schemaItem.is_active ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ formatDate(schemaItem.updated_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <Link :href="route('admin.schemas.edit', schemaItem.id)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">Edit</Link>
                      <button @click="confirmDelete(schemaItem.id)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="props.schemas && props.schemas.links.length > 3" class="mt-6 flex justify-center">
              <div class="flex flex-wrap -mb-1">
                <template v-for="(link, key) in props.schemas.links" :key="key">
                  <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded dark:border-gray-600 dark:text-gray-500" v-html="link.label" />
                  <Link v-else 
                        class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded dark:border-gray-600 hover:bg-white dark:hover:bg-gray-700 focus:border-indigo-500 dark:focus:border-indigo-300 focus:text-indigo-500 dark:focus:text-indigo-300" 
                        :class="{ 'bg-blue-700 text-white dark:bg-blue-600 dark:text-white': link.active }" 
                        :href="link.url" 
                        v-html="link.label" />
                </template>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
