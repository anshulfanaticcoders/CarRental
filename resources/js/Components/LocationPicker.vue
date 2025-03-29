<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Search, LocateFixed } from 'lucide-vue-next'; // Added Locate icon
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  onLocationSelect: Function,
});

const mapRef = ref(null);
const mapInstance = ref(null);
const markerRef = ref(null);
const searchResults = ref([]);
const searchQuery = ref('');
const loadingLocation = ref(false); // Track location loading state

onMounted(() => {
  if (!mapInstance.value && mapRef.value) {
    mapInstance.value = L.map(mapRef.value, {
      scrollWheelZoom: true,
      zoomControl: true,
    }).setView([20.5937, 78.9629], 5); // Default center (India)

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

  updateMap(location.latitude, location.longitude, location.address);
};

const locateUser = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }

  loadingLocation.value = true;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords;
      
      // Fetch address from Stadia Maps Reverse Geocoding
      try {
        const response = await fetch(
          `https://api.stadiamaps.com/geocoding/v1/reverse?9ea15dfe-b025-47ac-9a0e-cc35cc26891f&point.lat=${latitude}&point.lon=${longitude}`
        );
        const data = await response.json();
        const address = data.features?.[0]?.properties?.label || 'Unknown Location';

        updateMap(latitude, longitude, address);
      } catch (error) {
        console.error('Reverse geocoding error:', error);
        updateMap(latitude, longitude, 'Your Location');
      }

      loadingLocation.value = false;
    },
    (error) => {
      console.error('Geolocation error:', error);
      alert('Unable to retrieve location.');
      loadingLocation.value = false;
    }
  );
};

const updateMap = (lat, lon, address) => {
  const latLng = [lat, lon];
  mapInstance.value.setView(latLng, 13);

  if (markerRef.value) {
    markerRef.value.setLatLng(latLng);
  } else {
    markerRef.value = L.marker(latLng).addTo(mapInstance.value);
  }

  searchQuery.value = address;

  if (props.onLocationSelect) {
    props.onLocationSelect({ address, latitude: lat, longitude: lon });
  }
};
</script>

<template>
  <div class="w-full p-4 border rounded-lg shadow-md">
    <div class="relative mb-4">
      <div class="relative flex items-center">
        <input
          type="text"
          v-model="searchQuery"
          @input="handleSearch(searchQuery)"
          placeholder="Search location..."
          class="w-full p-2 pl-10 pr-12 border rounded-lg"
        />
        <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />

        <!-- Locate Me Button -->
        <button
          @click="locateUser"
          :disabled="loadingLocation"
          class="absolute right-3 top-2.5 h-6 w-6 text-gray-500 hover:text-black"
        >
          <LocateFixed v-if="!loadingLocation" />
          <span v-else class="loader" />
        </button>
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

<style scoped>
/* Simple loading animation */
.loader {
  display: inline-block;
  width: 20px;
  height: 20px;
  border: 2px solid #ccc;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
