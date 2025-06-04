<template>
  <AdminDashboardLayout title="SEO Meta Management">
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
              <h2 class="text-2xl font-semibold text-gray-700">SEO Meta Tags</h2>
              <Link :href="route('admin.seo-meta.create')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition">
                Create New SEO Meta
              </Link>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      URL Slug
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      SEO Title
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
                  <tr v-if="!seoMetas || !seoMetas.data || seoMetas.data.length === 0">
                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                      No SEO meta tags found.
                    </td>
                  </tr>
                  <tr v-for="meta in seoMetas.data" :key="meta.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ meta.url_slug || '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ meta.seo_title }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ new Date(meta.updated_at).toLocaleDateString() }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <Link :href="route('admin.seo-meta.edit', meta.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                      <button @click="confirmDelete(meta.id)" class="text-red-600 hover:text-red-900">Delete</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="seoMetas && seoMetas.last_page && seoMetas.last_page > 1" class="mt-6 flex justify-end">
              <Pagination
                :currentPage="seoMetas.current_page"
                :totalPages="seoMetas.last_page"
                @page-change="handlePageChange"
              />
            </div>

          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>

<script setup>
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3'; // Assuming Vue 3, use '@inertiajs/inertia-vue3' for Vue 2
import { defineProps } from 'vue';
import Pagination from '@/Components/ReusableComponents/Pagination.vue';

const props = defineProps({
  seoMetas: Object, // Contains data and links for pagination
});

const confirmDelete = (id) => {
  if (confirm('Are you sure you want to delete this SEO Meta entry?')) {
    router.delete(route('admin.seo-meta.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        // Optionally, refresh data or rely on Inertia's automatic reload
      },
      onError: (errors) => {
        // Handle error, maybe show a notification
        console.error('Error deleting SEO Meta:', errors);
        alert('Error deleting SEO Meta. Check console for details.');
      }
    });
  }
};

const handlePageChange = (page) => {
  router.get(route('admin.seo-meta.index', { page: page }), {}, {
    preserveState: true,
    preserveScroll: true, 
    replace: true, 
  });
};
</script>

<style scoped>
/* Add any page-specific styles here */
</style>
