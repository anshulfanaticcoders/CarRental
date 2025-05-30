<template>
  <AdminDashboardLayout title="Header/Footer Script Management">
    <div class="py-12">
      <div class=" mx-auto sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        <div v-if="$page.props.flash.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
          {{ $page.props.flash.error }}
        </div>

        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6 sm:px-20 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-2xl font-semibold text-gray-700">Header/Footer Scripts</h2>
              <Link v-if="!existingScript" :href="route('admin.header-footer-scripts.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                Create Script
              </Link>
               <Link v-else :href="route('admin.header-footer-scripts.edit', existingScript.id)" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 active:bg-blue-700 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition">
                Edit Script
              </Link>
            </div>
            
            <div v-if="$page.props.flash.info" class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
                {{ $page.props.flash.info }}
            </div>


            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      ID
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Header Script (Excerpt)
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Footer Script (Excerpt)
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Last Updated
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                      <span class="sr-only">Actions</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-if="headerFooterScripts.data.length === 0">
                    <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                      No header/footer scripts found.
                    </td>
                  </tr>
                  <tr v-for="scriptEntry in headerFooterScripts.data" :key="scriptEntry.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ scriptEntry.id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <pre class="max-w-xs overflow-x-auto whitespace-pre-wrap break-all">{{ scriptEntry.header_script ? scriptEntry.header_script.substring(0, 100) + (scriptEntry.header_script.length > 100 ? '...' : '') : '-' }}</pre>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      <pre class="max-w-xs overflow-x-auto whitespace-pre-wrap break-all">{{ scriptEntry.footer_script ? scriptEntry.footer_script.substring(0, 100) + (scriptEntry.footer_script.length > 100 ? '...' : '') : '-' }}</pre>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ new Date(scriptEntry.updated_at).toLocaleDateString() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <Link :href="route('admin.header-footer-scripts.edit', scriptEntry.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                      <button @click="confirmDelete(scriptEntry.id)" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="headerFooterScripts.links.length > 3" class="mt-6 flex justify-center">
              <div class="flex flex-wrap -mb-1">
                <template v-for="(link, key) in headerFooterScripts.links" :key="key">
                  <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                  <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-blue-700 text-white': link.active }" :href="link.url" v-html="link.label" />
                </template>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { defineProps } from 'vue';

const props = defineProps({
  headerFooterScripts: Object, // Contains data and links for pagination
  existingScript: Object, // The single existing script entry, or null
});

const confirmDelete = (id) => {
  if (confirm('Are you sure you want to delete this script entry?')) {
    router.delete(route('admin.header-footer-scripts.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        // Optionally, refresh data or rely on Inertia's automatic reload
      },
      onError: (errors) => {
        console.error('Error deleting script entry:', errors);
        alert('Error deleting script entry. Check console for details.');
      }
    });
  }
};
</script>

<style scoped>
/* Add any page-specific styles here */
pre {
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.875rem; /* text-sm */
  background-color: #f9fafb; /* bg-gray-50 */
  padding: 0.5rem;
  border-radius: 0.25rem; /* rounded */
  border: 1px solid #e5e7eb; /* border-gray-200 */
}
</style>
