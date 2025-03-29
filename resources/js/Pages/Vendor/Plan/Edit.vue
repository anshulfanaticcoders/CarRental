<template>
  <MyProfileLayout>
    <div class="container mx-auto py-6">
      <div class="max-w-lg mx-auto">
        <h1 class="text-2xl font-bold mb-6">Edit Plan Details</h1>
        
        <div class="bg-white shadow-md rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="mb-4">
              <label for="plan_type" class="block text-sm font-medium text-gray-700 mb-2">Plan Type</label>
              <input 
                type="text" 
                id="plan_type" 
                v-model="form.plan_type" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                required
              >
              <div v-if="errors.plan_type" class="text-red-500 text-sm mt-1">{{ errors.plan_type }}</div>
            </div>
            
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
            
            <div class="mb-4">
              <label for="plan_description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea 
                id="plan_description" 
                v-model="form.plan_description" 
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              ></textarea>
              <div v-if="errors.plan_description" class="text-red-500 text-sm mt-1">{{ errors.plan_description }}</div>
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Features</label>
              <div v-for="(feature, index) in form.features" :key="index" class="flex mb-2">
                <input 
                  type="text" 
                  v-model="form.features[index]" 
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  placeholder="Feature description"
                >
                <button 
                  type="button" 
                  @click="removeFeature(index)" 
                  class="ml-2 inline-flex items-center p-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <button 
                type="button" 
                @click="addFeature" 
                class="mt-2 inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Add Feature
              </button>
            </div>
            
            <div class="flex justify-end mt-6">
              <Link :href="route('VendorPlanIndex')" class="bg-gray-200 py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3">
                Cancel
              </Link>
              <Button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save
              </Button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </MyProfileLayout>
</template>

<script setup>
import { Button } from '@/Components/ui/button';
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { Link, useForm } from '@inertiajs/inertia-vue3';

// Define props
const props = defineProps({
  plan: Object,
  errors: Object,
});

// Parse features if they're stored as JSON string
let features = [];
try {
  features = props.plan.features ? JSON.parse(props.plan.features) : [];
} catch (e) {
  features = Array.isArray(props.plan.features) ? props.plan.features : [];
}

// Create form with initial data
const form = useForm({
  plan_type: props.plan.plan_type || '',
  plan_value: props.plan.price,
  plan_description: props.plan.plan_description || '',
  features: features
});

// Add a new empty feature
function addFeature() {
  form.features.push('');
}

// Remove a feature by index
function removeFeature(index) {
  form.features.splice(index, 1);
}

// Submit function
function submit() {
  form.put(route('VendorPlanUpdate', props.plan.id));
}
</script>