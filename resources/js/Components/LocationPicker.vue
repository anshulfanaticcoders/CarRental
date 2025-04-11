<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Search, LocateFixed } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  onLocationSelect: Function,
});

const mapRef = ref(null);
const mapInstance = ref(null);
const markerRef = ref(null);
const searchResults = ref([]);
const location = ref('');
const city = ref('');
const state = ref('');
const country = ref('');
const latitude = ref(null);
const longitude = ref(null);
const loadingLocation = ref(false);

onMounted(() => {
  if (!mapInstance.value && mapRef.value) {
    mapInstance.value = L.map(mapRef.value, {
      scrollWheelZoom: true,
      zoomControl: true,
    }).setView([20.5937, 78.9629], 5); // Default center (India)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
    }).addTo(mapInstance.value);

    // Handle map clicks
    mapInstance.value.on('click', (e) => {
      const lat = e.latlng.lat;
      const lng = e.latlng.lng;
      latitude.value = lat.toFixed(6);
      longitude.value = lng.toFixed(6);
      updateMap(lat, lng);
      fetchAddressFromCoords(lat, lng);
    });
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

const handleLocationSelect = async (result) => {
  const { coordinates } = result.geometry;
  const lat = coordinates[1];
  const lng = coordinates[0];

  // Call fetchAddressFromCoords to get detailed address data
  await fetchAddressFromCoords(lat, lng);

  // Update map and trigger callback
  updateMap(lat, lng);
  triggerLocationSelect(lat, lng);

  searchResults.value = []; // Clear dropdown
};
const locateUser = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }

  loadingLocation.value = true;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;

      try {
        const apiKey = '28c203802893418f82ca9ac69726e565'; // Replace with your OpenCage API key
        const response = await fetch(
          `https://api.opencagedata.com/geocode/v1/json?q=${lat}+${lng}&key=${apiKey}&limit=1&no_annotations=1`
        );

        if (!response.ok) {
          throw new Error(`OpenCage HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        console.log('OpenCage user location response:', data); // Debug log

        const components = data.results[0]?.components || {};
        const cityValue =
          components.city ||
          components.town ||
          components.municipality ||
          components.village ||
          components.locality ||
          '';

        location.value = data.results[0]?.formatted || 'Your Location';
        city.value = cityValue;
        state.value = components.state || '';
        country.value = components.country || '';
        latitude.value = lat.toFixed(6);
        longitude.value = lng.toFixed(6);

        updateMap(lat, lng);
        triggerLocationSelect(lat, lng);
      } catch (error) {
        console.error('OpenCage geocoding error:', error);
        location.value = 'Your Location';
        city.value = '';
        state.value = '';
        country.value = '';
        latitude.value = lat.toFixed(6);
        longitude.value = lng.toFixed(6);
        updateMap(lat, lng);
        triggerLocationSelect(lat, lng);
      }

      loadingLocation.value = false;
    },
    (error) => {
      console.error('Geolocation error:', error);
      alert('Unable to retrieve location. Please check your browser settings.');
      loadingLocation.value = false;
    }
  );
};

const fetchAddressFromCoords = async (lat, lng) => {
  try {
    const apiKey = '28c203802893418f82ca9ac69726e565'; // Replace with your OpenCage API key
    const response = await fetch(
      `https://api.opencagedata.com/geocode/v1/json?q=${lat}+${lng}&key=${apiKey}&limit=1&no_annotations=1`
    );

    if (!response.ok) {
      throw new Error(`OpenCage HTTP error! Status: ${response.status}`);
    }

    const data = await response.json();
    console.log('OpenCage response:', data); // Debug to verify response

    const components = data.results[0]?.components || {};
    const cityValue =
      components.city ||
      components.town ||
      components.municipality ||
      components.village ||
      components.locality ||
      '';

    location.value = data.results[0]?.formatted || 'Unknown Location';
    city.value = cityValue;
    state.value = components.state || '';
    country.value = components.country || '';

    latitude.value = lat.toFixed(6);
    longitude.value = lng.toFixed(6);
  } catch (error) {
    console.error('OpenCage geocoding error:', error);
    location.value = 'Custom Location';
    city.value = '';
    state.value = '';
    country.value = '';
    latitude.value = lat.toFixed(6);
    longitude.value = lng.toFixed(6);
  }
};

const updateMap = (lat, lng) => {
  const latLng = [lat, lng];
  mapInstance.value.setView(latLng, 13);

  if (markerRef.value) {
    markerRef.value.setLatLng(latLng);
  } else {
    markerRef.value = L.marker(latLng).addTo(mapInstance.value);
  }
};

const triggerLocationSelect = (lat, lng) => {
  if (props.onLocationSelect) {
    const locationData = {
      address: location.value,
      latitude: lat,
      longitude: lng,
      city: city.value,
      state: state.value,
      country: country.value,
    };
    console.log('Location selected:', locationData); // Debug log
    props.onLocationSelect(locationData);
  }
};
</script>

<template>
  <div class="w-full p-4 border rounded-lg shadow-md">
    <div class="mb-4 space-y-4">
      <!-- Location Search -->
      <div class="relative">
        <label for="location" class="block text-sm font-medium text-gray-700">Search Location</label>
        <div class="relative flex items-center">
          <input
            id="location"
            type="text"
            v-model="location"
            @input="handleSearch(location)"
            placeholder="Search for a location..."
            class="w-full p-2 pl-10 border rounded-lg"
          />
          <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
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

      <!-- City, State, Country -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label for="city" class="block text-sm font-medium text-gray-700">City</label>
          <input
            id="city"
            type="text"
            v-model="city"
            placeholder="City"
            class="w-full p-2 border rounded-lg"
          />
        </div>
        <div>
          <label for="state" class="block text-sm font-medium text-gray-700">State</label>
          <input
            id="state"
            type="text"
            v-model="state"
            readonly
            placeholder="State"
            class="w-full p-2 border rounded-lg bg-gray-100"
          />
        </div>
        <div>
          <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
          <input
            id="country"
            type="text"
            v-model="country"
            readonly
            placeholder="Country"
            class="w-full p-2 border rounded-lg bg-gray-100"
          />
        </div>
      </div>

      <!-- Latitude, Longitude -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude</label>
          <input
            id="latitude"
            type="text"
            v-model="latitude"
            placeholder="Latitude"
            class="w-full p-2 border rounded-lg bg-gray-100"
            readonly
          />
        </div>
        <div>
          <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude</label>
          <input
            id="longitude"
            type="text"
            v-model="longitude"
            placeholder="Longitude"
            class="w-full p-2 border rounded-lg bg-gray-100"
            readonly
          />
        </div>
      </div>
    </div>

    <!-- Map -->
    <div ref="mapRef" class="w-full h-[400px] rounded-lg z-0" />
  </div>
</template>

<style scoped>
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