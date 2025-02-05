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
            <div v-if="vehicles.length" class="overflow-x-auto bg-[#153B4F0D]">
              <table class="w-full border-collapse rounded-[12px] border border-gray-200">
                <thead>
                  <tr class="bg-gray-100">
                    <th class="border p-3 text-left">Image</th>
                    <th class="border p-3 text-left">Brand & Model</th>
                    <th class="border p-3 text-left">Transmission</th>
                    <th class="border p-3 text-left">Fuel</th>
                    <th class="border p-3 text-left">Location</th>
                    <th class="border p-3 text-left">Price/Day</th>
                    <th class="border p-3 text-left">Status</th>
                    <th class="border p-3 text-left">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="vehicle in vehicles" :key="vehicle.id" class="border-b">
                    <td class="border p-3">
                      <img 
                        :src="getPrimaryImage(vehicle)" 
                        :alt="`${vehicle.brand} ${vehicle.model}`" 
                        class="h-16 w-24 object-cover rounded"
                      >
                    </td>
                    <td class="border p-3">{{ vehicle.brand }} {{ vehicle.model }}</td>
                    <td class="border p-3">{{ vehicle.transmission }}</td>
                    <td class="border p-3">{{ vehicle.fuel }}</td>
                    <td class="border p-3">{{ vehicle.location }}</td>
                    <td class="border p-3 text-customPrimaryColor font-bold">${{ vehicle.price_per_day }}/day</td>
                    <td class="border p-3">
                      <span :class="getStatusBadgeClass(vehicle.status)" class="px-2 py-1 rounded text-sm">
                        {{ vehicle.status }}
                      </span>
                    </td>
                    <td class="h-[5rem] p-3 flex items-center justify-center space-x-2">
                      <Link 
                        :href="route('vendor-vehicles.edit', vehicle.id)"
                        class="px-3 py-1 bg-gray-600 text-white rounded hover:bg-gray-700"
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
    router.delete(route('vendor-vehicles.destroy', vehicleToDelete.value.id), {
      onSuccess: () => {
        showDeleteModal.value = false
        vehicleToDelete.value = null
      }
    })
  }
  </script>
  