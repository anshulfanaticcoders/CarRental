<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3'; // Added router
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue'; // Assuming an AdminDashboardLayout exists
import { defineProps } from 'vue';

const props = defineProps({
  categories: Array,
});

const { flash } = usePage().props;

// Function to handle feature deletion
const deleteFeature = (featureId) => {
  if (confirm('Are you sure you want to delete this feature?')) {
    router.delete(route('admin.features.destroy', featureId), {
      preserveScroll: true,
      // onSuccess and onError callbacks can be added here if needed
    });
  }
};
</script>

<template>
  <Head title="Manage Vehicle Features" />

  <AdminDashboardLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Vehicle Features</h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- User provided classes: "column overflow-y-auto w-[50%] h-full flex justify-center pb-[4rem] max-[768px]:w-full max-[768px]:h-auto bg-white" -->
        <!-- Applying to a central content block, adjusting width to be more standard for a page like this -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">

            <div v-if="flash && flash.success" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded">
              {{ flash.success }}
            </div>
            <div v-if="flash && flash.error" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded">
              {{ flash.error }}
            </div>

            <div v-for="category in categories" :key="category.id" class="mb-8 p-4 border rounded-lg">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-semibold">{{ category.name }}</h3>
                <Link :href="route('admin.features.create', category.id)"
                      class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-150 ease-in-out">
                  Add Feature to {{ category.name }}
                </Link>
              </div>

              <div v-if="category.features && category.features.length > 0">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Feature Name
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Icon
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="feature in category.features" :key="feature.id">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ feature.feature_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <img v-if="feature.icon_url" :src="feature.icon_url" :alt="feature.feature_name" class="h-10 w-10 object-cover rounded">
                        <span v-else>No Icon</span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <Link :href="route('admin.features.edit', feature.id)"
                              class="text-indigo-600 hover:text-indigo-900 mr-3">
                          Edit
                        </Link>
                        <button @click="deleteFeature(feature.id)"
                                class="text-red-600 hover:text-red-900">
                          Delete
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div v-else class="text-gray-500">
                No features added for this category yet.
              </div>
            </div>

            <div v-if="!categories || categories.length === 0" class="text-center text-gray-500 py-8">
              <p>No vehicle categories found. Please add categories first.</p>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
