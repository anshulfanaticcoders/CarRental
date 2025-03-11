<template>
    <MyProfileLayout>
      <div class="container mx-auto py-6">
        <h1 class="text-2xl font-bold mb-6">Vendor Vehicle Plans</h1>
        
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vehicle</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Type</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="plan in plans" :key="plan.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ plan.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ plan.vehicle_id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ plan.plan_type }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span class="font-medium">{{ plan.price }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <Link :href="route('VendorPlanEdit', plan.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </MyProfileLayout>
  </template>
  
  <script setup>
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  import { Link } from '@inertiajs/inertia-vue3';
  
  // Define props
  const props = defineProps({
    plans: Array,
  });
  </script>