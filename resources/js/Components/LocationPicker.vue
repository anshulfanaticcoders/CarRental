<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Search } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  onLocationSelect: Function, // Define prop properly
});

const mapRef = ref(null);
const mapInstance = ref(null);
const markerRef = ref(null);
const searchResults = ref([]);
const searchQuery = ref('');

onMounted(() => {
  if (!mapInstance.value && mapRef.value) {
    mapInstance.value = L.map(mapRef.value, {
      scrollWheelZoom: true,
      zoomControl: true,
    }).setView([20.5937, 78.9629], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
    }).addTo(mapInstance.value);
  }
});

onUnmounted(() => {
  if (mapInstance.value) {
    mapInstance.value.remove();
    mapInstance.value = null;
  }
});

const handleSearch = async (query) => {
  if (query.length < 3) {
    searchResults.value = [];
    return;
  }

  try {
    const response = await fetch(`/api/geocoding/autocomplete?text=${encodeURIComponent(query)}`);
    const data = await response.json();
    searchResults.value = data.features || [];
  } catch (error) {
    console.error('Search error:', error);
    searchResults.value = [];
  }
};

const handleLocationSelect = (result) => {
  const { coordinates } = result.geometry;
  const location = {
    address: result.properties.label,
    latitude: coordinates[1],
    longitude: coordinates[0],
  };

  const latLng = [location.latitude, location.longitude];
  mapInstance.value.setView(latLng, 13);

  if (markerRef.value) {
    markerRef.value.setLatLng(latLng);
  } else {
    markerRef.value = L.marker(latLng).addTo(mapInstance.value);
  }

  searchResults.value = [];
  searchQuery.value = location.address;

  // Use `props.onLocationSelect` instead of `defineProps().onLocationSelect`
  if (props.onLocationSelect) {
    props.onLocationSelect(location);
  }
};
</script>

<template>
  <div class="w-full p-4 border rounded-lg shadow-md">
    <div class="relative mb-4">
      <div class="relative">
        <input
          type="text"
          v-model="searchQuery"
          @input="handleSearch(searchQuery)"
          placeholder="Search location..."
          class="w-full p-2 pl-10 border rounded-lg"
        />
        <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
      </div>

      <div v-if="searchResults.length" class="absolute z-50 w-full mt-1 bg-white border rounded-lg shadow-lg">
        <div
          v-for="result in searchResults"
          :key="result.properties.id"
          @click="handleLocationSelect(result)"
          class="p-2 hover:bg-gray-100 cursor-pointer"
        >
          {{ result.properties.label }}
        </div>
      </div>
    </div>

    <div ref="mapRef" class="w-full h-[400px] rounded-lg z-0" />
  </div>
</template>
