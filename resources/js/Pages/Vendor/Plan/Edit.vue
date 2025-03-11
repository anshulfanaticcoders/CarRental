<template>
    <MyProfileLayout>
      <div class="container mx-auto py-6">
        <div class="max-w-lg mx-auto">
          <h1 class="text-2xl font-bold mb-6">Edit Plan Price</h1>
          
          <div class="bg-white shadow-md rounded-lg p-6">
            <form @submit.prevent="submit">
              <div class="mb-4">
                <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price</label>
                <input 
                  type="number" 
                  step="0.01" 
                  id="price" 
                  v-model="form.plan_value" 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  required
                >
                <div v-if="errors.plan_value" class="text-red-500 text-sm mt-1">{{ errors.plan_value }}</div>
              </div>
              
              <div class="flex justify-end mt-6">
                <Link :href="route('VendorPlanIndex')" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3">
                  Cancel
                </Link>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  Save
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </MyProfileLayout>
  </template>
  
  <script setup>
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
  import { Link, useForm } from '@inertiajs/inertia-vue3';
  
  // Define props
  const props = defineProps({
    plan: Object,
    errors: Object,
  });
  
  // Create form with initial data
  const form = useForm({
    plan_value: props.plan.price,
  });
  
  // Submit function
  function submit() {
    form.put(route('VendorPlanUpdate', props.plan.id));
  }
  </script>