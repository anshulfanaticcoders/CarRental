<script setup>
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue';
import { onMounted } from 'vue'
  
const props = defineProps({
  vehicles: {
    type: Array,
    required: true
  }
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount);
};
</script>

<template>
    <MyProfileLayout>
        <div class="p-6 bg-white rounded shadow-md">
            <h1 class="text-xl font-semibold mb-4">Vehicle Information</h1>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-6">
                <span class="text-gray-500">Loading vehicle information...</span>
            </div>

            <!-- Error State -->
            <div v-if="error" class="text-center py-6 text-red-600">
                {{ error }}
            </div>

            <!-- Vehicles Table -->
            <div v-if="vehicles.length" class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-8">
                                <input type="checkbox" class="rounded border-gray-300">
                            </th>
                            <th class="px-4 py-2 text-left">Car ID</th>
                            <th class="px-4 py-2 text-left">Car Type</th>
                            <th class="px-4 py-2 text-left">Car Name/Brand</th>
                            <th class="px-4 py-2 text-left">Registration Number</th>
                            <th class="px-4 py-2 text-left">Car Price/day</th>
                            <th class="px-4 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vehicle in vehicles" 
                            :key="vehicle.id" 
                            class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <input type="checkbox" class="rounded border-gray-300">
                            </td>
                            <td class="px-4 py-2">#{{ vehicle.id }}</td>
                            <td class="px-4 py-2">{{ vehicle.category?.name }}</td>
                            <td class="px-4 py-2">{{ vehicle.brand }} {{ vehicle.model }}</td>
                            <td class="px-4 py-2">{{ vehicle.specifications?.registration_number }}</td>
                            <td class="px-4 py-2">{{ formatCurrency(vehicle.price_per_day) }}/day</td>
                            <td class="px-4 py-2 text-right space-x-2">
                                <button class="text-blue-600 hover:text-blue-800">
                                    <span class="sr-only">Edit</span>
                                    <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button class="text-red-600 hover:text-red-800">
                                    <span class="sr-only">Delete</span>
                                    <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                                <button class="text-gray-600 hover:text-gray-800">
                                    <span class="sr-only">More</span>
                                    <svg class="w-5 h-5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- No Vehicles Data -->
            <div v-if="!loading && !error && !vehicles.length" class="text-center py-6">
                <span class="text-gray-500">No vehicles available.</span>
            </div>
        </div>
    </MyProfileLayout>
</template>

<style scoped>
/* Your existing styles remain the same */
</style>