<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Search } from 'lucide-vue-next';
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

// Location detail fields
const address = ref('');
const city = ref('');
const state = ref('');
const country = ref('');
const latitude = ref('');
const longitude = ref('');

// Use your existing Stadia Maps API key
const STADIA_API_KEY = import.meta.env.VITE_STADIA_API_KEY;

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

const handleBlur = () => {
  window.setTimeout(() => {
    searchResults.value = [];
  }, 200);
};

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
  const lon = coordinates[0];
  
  await reverseGeocode(lat, lon, result.properties.label);
};

const reverseGeocode = async (lat, lng, fallbackAddress = null) => {
  try {
    const response = await fetch(`/api/geocoding/reverse?lat=${lat}&lon=${lng}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    const addressComponents = {
      fullAddress: data.features[0]?.properties?.formatted || fallbackAddress || 'Unknown Location',
      city: data.features[0]?.properties?.city || '',
      state: data.features[0]?.properties?.state || '',
      country: data.features[0]?.properties?.country || '',
    };
    
    updateMap(lat, lng, addressComponents);
  } catch (error) {
    console.error('Reverse geocoding error:', error);
    
    let cityPart = '', statePart = '', countryPart = '';
    
    if (fallbackAddress) {
      const parts = fallbackAddress.split(',').map(part => part.trim());
      if (parts.length >= 3) {
        cityPart = parts[0];
        statePart = parts[1];
        countryPart = parts[parts.length - 1];
      }
    }
    
    updateMap(lat, lng, { 
      fullAddress: fallbackAddress || 'Selected Location', 
      city: cityPart,
      state: statePart,
      country: countryPart
    });
  }
};

const updateMap = (lat, lon, addressComponents) => {
  const latLng = [lat, lon];
  mapInstance.value.setView(latLng, 13);

  if (markerRef.value) {
    markerRef.value.setLatLng(latLng);
  } else {
    markerRef.value = L.marker(latLng).addTo(mapInstance.value);
  }

  address.value = addressComponents.fullAddress;
  city.value = addressComponents.city;
  state.value = addressComponents.state;
  country.value = addressComponents.country;
  latitude.value = lat;
  longitude.value = lon;
  
  searchQuery.value = addressComponents.fullAddress;

  if (props.onLocationSelect) {
    props.onLocationSelect({ 
      address: addressComponents.fullAddress,
      city: addressComponents.city,
      state: addressComponents.state,
      country: addressComponents.country,
      latitude: lat,
      longitude: lon
    });
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
          @blur="handleBlur"
          placeholder="Search location..."
          class="w-full p-2 pl-10 pr-4 border rounded-lg"
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

    <div ref="mapRef" class="w-full h-64 rounded-lg z-0 mb-4" />

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
        <input
          type="text"
          v-model="address"
          class="w-full p-2 border rounded-lg"
          readonly
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
        <input
          type="text"
          v-model="city"
          readonly
          class="w-full p-2 border rounded-lg"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
        <input
          type="text"
          v-model="state"
          readonly
          class="w-full p-2 border rounded-lg"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
        <input
          type="text"
          v-model="country"
          readonly
          class="w-full p-2 border rounded-lg"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
        <input
          type="text"
          v-model="latitude"
          class="w-full p-2 border rounded-lg"
          readonly
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
        <input
          type="text"
          v-model="longitude"
          class="w-full p-2 border rounded-lg"
          readonly
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
</style>