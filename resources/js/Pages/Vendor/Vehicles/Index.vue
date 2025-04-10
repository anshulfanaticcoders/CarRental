<template>
  <MyProfileLayout>
    <div class="flex justify-between items-center">
      <h2 class="font-semibold text-xl text-gray-800">My Vehicles</h2>
      <Link :href="route('vehicles.create')"
        class="px-4 py-2 bg-customPrimaryColor border-customPrimaryColor border-[1px] text-white rounded-md hover:bg-white hover:text-customPrimaryColor">
      Add New Vehicle
      </Link>
    </div>

    <div class="py-12">
      <div class="mx-auto">
        <div class="rounded-[12px] mb-4">
          <input type="text" v-model="searchQuery" placeholder="Search vehicles..." class="px-4 py-2 border border-gray-300 rounded-md w-full" />
        </div>
        <div class="rounded-[12px]">
          <div v-if="filteredVehicles.length" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr class="">
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand &
                    Model</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Transmission</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fuel</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limited KM
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Cancellation Available
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price
                  </th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(vehicle, index) in filteredVehicles" :key="vehicle.id" class="border-b">
                  <td class="px-6 py-4 whitespace-nowrap text-[0.875rem]">{{ (pagination.current_page - 1) * pagination.per_page + index + 1 }}</td>
                  <Link :href="`/vehicle/${vehicle.id}`" class="w-full">
                  <td class="px-2 py-4 whitespace-nowrap">
                    <img :src="getPrimaryImage(vehicle)" alt="no image"
                      class="h-12 w-24 object-cover rounded">
                  </td>
                  </Link>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">{{ vehicle.brand }} {{ vehicle.model }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem] capitalize">{{ vehicle.transmission }}</td>
                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem] capitalize">{{ vehicle.fuel }}</td>
                  <td class="px-2 py-4 whitespace-wrap text-[0.875rem]">{{ vehicle.location }}</td>
                  <td class="px-2 py-4 whitespace-wrap text-[0.875rem]">
                    {{ vehicle.limited_km ? `€${vehicle.price_per_km}` : 'Unlimited' }}
                  </td>

                  <td class="px-2 py-4 whitespace-wrap text-[0.875rem]">
                    {{ vehicle.cancellation_available ? 'Available' : 'Unavailable' }}
                  </td>

                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem] text-customPrimaryColor font-bold">
                    {{ formatPricing(vehicle) }}
                  </td>

                  <td class="px-2 py-4 whitespace-nowrap text-[0.875rem]">
                    <span :class="getStatusBadgeClass(vehicle.status)" class="px-2 py-1 rounded text-xs capitalize">
                      {{ vehicle.status }}
                    </span>
                  </td>
                  <td class="px-2 py-4 whitespace-nowrap text-xs font-medium">
                    <Link :href="route('current-vendor-vehicles.edit', vehicle.id)"
                      class="px-3 mr-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700">
                    Edit
                    </Link>
                    <button @click="confirmDeletion(vehicle)"
                      class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">
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
      <div class="mt-[1rem] flex justify-end">
        <Pagination :current-page="pagination.current_page" :total-pages="pagination.last_page"
          @page-change="handlePageChange" />
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium">Delete Vehicle</h3>
        <p class="mt-2 text-gray-600">Are you sure you want to delete this vehicle? This action cannot be undone.</p>
        <div class="mt-4 flex justify-end space-x-3">
          <button @click="showDeleteModal = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
            Cancel
          </button>
          <button @click="deleteVehicle" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
            Delete
          </button>
        </div>
      </div>
    </Modal>
  </MyProfileLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'
import { router } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import MyProfileLayout from '@/Layouts/MyProfileLayout.vue'
import Pagination from './Pagination.vue'

const props = defineProps({
  vehicles: {
    type: Array,
    required: true
  },
  pagination: {
    type: Object,
    required: true
  }
})
const searchQuery = ref('');


watch(searchQuery, (newQuery) => {
  router.get(
    route('current-vendor-vehicles.index'),
    { search: newQuery },
    { preserveState: true, preserveScroll: true }
  );
});

const handlePageChange = (page) => {
  router.get(route('current-vendor-vehicles.index'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });

};
const showDeleteModal = ref(false)
const vehicleToDelete = ref(null)

const getPrimaryImage = (vehicle) => {
  const primaryImage = vehicle.images.find(img => img.image_type === 'primary')
  return primaryImage ? `${primaryImage.image_url}` : '/images/placeholder.jpg'
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
const formatPricing = (vehicle) => {
  if (!vehicle || !vehicle.vendor_profile || !vehicle.vendor_profile.currency) {
    return 'N/A'; // Fallback if data is missing
  }

  const currencySymbol = vehicle.vendor_profile.currency;
  const prices = [];

  if (vehicle.price_per_day) prices.push(`${currencySymbol}${vehicle.price_per_day}/day`);
  if (vehicle.price_per_week) prices.push(`${currencySymbol}${vehicle.price_per_week}/week`);
  if (vehicle.price_per_month) prices.push(`${currencySymbol}${vehicle.price_per_month}/month`);

  return prices.length ? prices.join(' | ') : 'N/A';
};

const filteredVehicles = computed(() => {
  return props.vehicles.filter(vehicle => {
    const query = searchQuery.value.toLowerCase();
    return (
      vehicle.brand.toLowerCase().includes(query) ||
      vehicle.model.toLowerCase().includes(query) ||
      vehicle.transmission.toLowerCase().includes(query) ||
      vehicle.fuel.toLowerCase().includes(query) ||
      vehicle.location.toLowerCase().includes(query) ||
      vehicle.status.toLowerCase().includes(query)
    );
  });
});

</script>

<style scoped>
@media screen and (max-width:768px) {
    
    th{
        font-size: 0.75rem;
    }
    td{
        font-size: 0.75rem;
        text-wrap-mode: nowrap;
    }
}
</style>