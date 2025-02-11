<template>
    <MyProfileLayout>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800">My Vehicles</h2>
        <Link 
          :href="route('vehicles.create')"
          class="px-4 py-2 bg-customPrimaryColor border-customPrimaryColor border-[1px] text-white rounded-md hover:bg-white hover:text-customPrimaryColor"
        >
          Add New Vehicle
        </Link>
      </div>
  
      <div class="py-12">
        <div class=" mx-auto">
          <div class="rounded-[12px]">
            <div v-if="vehicles.length" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr class="">
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand & Model</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transmission</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fuel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price/Day</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(vehicle, index) in vehicles" :key="vehicle.id" class="border-b">
                    <td class="px-6 py-4 whitespace-nowrap">{{ index + 1 }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <img 
                        :src="getPrimaryImage(vehicle)" 
                        :alt="`${vehicle.brand} ${vehicle.model}`" 
                        class="h-16 w-24 object-cover rounded"
                      >
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ vehicle.brand }} {{ vehicle.model }}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">{{ vehicle.transmission }}</td>
                    <td class="px-6 py-4 whitespace-nowrap capitalize">{{ vehicle.fuel }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ vehicle.location }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-customPrimaryColor font-bold">${{ vehicle.price_per_day }}/day</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusBadgeClass(vehicle.status)" class="px-2 py-1 rounded text-sm capitalize">
                        {{ vehicle.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <Link 
                        :href="route('current-vendor-vehicles.edit', vehicle.id)"
                        class="px-3 mr-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700"
                      >
                        Edit
                      </Link>
                      <button 
                        @click="confirmDeletion(vehicle)"
                        class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700"
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-12">
              <p class="text-gray-500">No vehicles found. Start by adding a new vehicle.</p>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Delete Confirmation Modal -->
      <Modal :show="showDeleteModal" @close="showDeleteModal = false">
        <div class="p-6">
          <h3 class="text-lg font-medium">Delete Vehicle</h3>
          <p class="mt-2 text-gray-600">Are you sure you want to delete this vehicle? This action cannot be undone.</p>
          <div class="mt-4 flex justify-end space-x-3">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300"
            >
              Cancel
            </button>
            <button
              @click="deleteVehicle"
              class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
            >
              Delete
            </button>
          </div>
        </div>
      </Modal>
    </MyProfileLayout>
  </template>
  
  <script setup>
  import { ref } from 'vue'
  import { Link } from '@inertiajs/vue3'
  import { router } from '@inertiajs/vue3'
  import Modal from '@/Components/Modal.vue'
  import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
  
  const props = defineProps({
    vehicles: {
      type: Array,
      required: true
    }
  })
  
  const showDeleteModal = ref(false)
  const vehicleToDelete = ref(null)
  
  const getPrimaryImage = (vehicle) => {
    const primaryImage = vehicle.images.find(img => img.image_type === 'primary')
    return primaryImage ? `/storage/${primaryImage.image_path}` : '/images/placeholder.jpg'
  }
  
  const getStatusBadgeClass = (status) => {
    const classes = {
      available: 'bg-green-500 text-white',
      rented: 'bg-blue-500 text-white',
      maintenance: 'bg-yellow-500 text-white'
    }
    return classes[status] || 'bg-gray-500 text-white'
  }
  
  const confirmDeletion = (vehicle) => {
    vehicleToDelete.value = vehicle
    showDeleteModal.value = true
  }
  
  const deleteVehicle = () => {
    router.delete(route('current-vendor-vehicles.destroy', vehicleToDelete.value.id), {
      onSuccess: () => {
        showDeleteModal.value = false
        vehicleToDelete.value = null
      }
    })
  }
  </script>
  