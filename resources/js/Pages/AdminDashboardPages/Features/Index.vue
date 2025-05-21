<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AdminDashboardLayout from '@/Layouts/AdminDashboardLayout.vue';
import { defineProps, ref } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

const props = defineProps({
  categories: Array,
});

const { flash } = usePage().props;

const activeTab = ref(props.categories && props.categories.length > 0 ? props.categories[0].id.toString() : '');

// Function to handle feature deletion
const deleteFeature = (featureId) => {
  if (confirm('Are you sure you want to delete this feature?')) {
    router.delete(route('admin.features.destroy', featureId), {
      preserveScroll: true,
    });
  }
};
</script>

<template>
  <Head title="Manage Vehicle Features" />

  <AdminDashboardLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Manage Vehicle Features</h2>
    </template>

    <div class="py-12">
      <div class="full-w-container">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">

            <div v-if="flash && flash.success" class="mb-4 p-4 bg-green-100 text-green-700 border border-green-300 rounded dark:bg-green-700/20 dark:text-green-300 dark:border-green-600">
              {{ flash.success }}
            </div>
            <div v-if="flash && flash.error" class="mb-4 p-4 bg-red-100 text-red-700 border border-red-300 rounded dark:bg-red-700/20 dark:text-red-300 dark:border-red-600">
              {{ flash.error }}
            </div>

            <div v-if="categories && categories.length > 0">
              <Tabs v-model="activeTab" class="w-full">
                <TabsList class="mb-4 border-b dark:border-gray-700">
                  <TabsTrigger v-for="category in categories" :key="category.id" :value="category.id.toString()" class="data-[state=active]:text-primary data-[state=active]:shadow-sm">
                    {{ category.name }}
                  </TabsTrigger>
                </TabsList>

                <TabsContent v-for="category in categories" :key="`content-${category.id}`" :value="category.id.toString()">
                  <div class="p-1">
                    <div class="flex justify-between items-center mb-4">
                      <h3 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">{{ category.name }}</h3>
                      <Link :href="route('admin.features.create', category.id)"
                            class="bg-customPrimaryColor text-white font-bold py-2 px-4 rounded">
                        Add Feature to {{ category.name }}
                      </Link>
                    </div>

                    <div v-if="category.features && category.features.length > 0">
                      <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                          <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Feature Name
                              </th>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Icon
                              </th>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Actions
                              </th>
                            </tr>
                          </thead>
                          <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="feature in category.features" :key="feature.id">
                              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ feature.feature_name }}
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                <img v-if="feature.icon_url" :src="feature.icon_url" :alt="feature.feature_name" class="h-10 w-10 object-cover rounded">
                                <span v-else>No Icon</span>
                              </td>
                              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Link :href="route('admin.features.edit', feature.id)"
                                      class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                  Edit
                                </Link>
                                <button @click="deleteFeature(feature.id)"
                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                  Delete
                                </button>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <div v-else class="text-gray-500 dark:text-gray-400 py-4">
                      No features added for this category yet.
                    </div>
                  </div>
                </TabsContent>
              </Tabs>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-8">
              <p>No vehicle categories found. Please add categories first.</p>
            </div>

          </div>
        </div>
      </div>
    </div>
  </AdminDashboardLayout>
</template>
