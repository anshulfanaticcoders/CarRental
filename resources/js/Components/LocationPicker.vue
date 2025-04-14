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
const searchQuery = ref('');
const loadingLocation = ref(false);

// New location detail fields
const address = ref('');
const city = ref('');
const state = ref('');
const country = ref('');
const latitude = ref('');
const longitude = ref('');

onMounted(() => {
  if (!mapInstance.value && mapRef.value) {
    mapInstance.value = L.map(mapRef.value, {
      scrollWheelZoom: true,
      zoomControl: true,
    }).setView([20.5937, 78.9629], 5); // Default center (India)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap contributors',
    }).addTo(mapInstance.value);

    // Add click event to map for location selection
    mapInstance.value.on('click', async (e) => {
      const { lat, lng } = e.latlng;
      await reverseGeocode(lat, lng);
    });
  }
});

onUnmounted(() => {
  if (mapInstance.value) {
    mapInstance.value.remove();
    mapInstance.value = null;
  }
});

// Function to handle blur event on search input
const handleBlur = () => {
  // Use window.setTimeout to avoid context issues
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
  
  // Use Nominatim reverse geocoding
  await reverseGeocode(lat, lon, result.properties.label);
};

const reverseGeocode = async (lat, lng, fallbackAddress = null) => {
  try {
    // Using Nominatim instead of Stadia Maps
    const response = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`,
      {
        headers: {
          'Accept-Language': 'en',
          'User-Agent': 'VueLocationPicker/1.0'
        }
      }
    );
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    // Extract address components from Nominatim response
    const addressComponents = {
      fullAddress: data.display_name || fallbackAddress || 'Unknown Location',
      city: data.address.city || data.address.town || data.address.village || '',
      state: data.address.state || data.address.province || '',
      country: data.address.country || '',
    };
    
    // Update the map and form fields
    updateMap(lat, lng, addressComponents);
  } catch (error) {
    console.error('Reverse geocoding error:', error);
    
    // Fallback to simple parsing from the search result if available
    let cityPart = '', statePart = '', countryPart = '';
    
    if (fallbackAddress) {
      // Try to parse the fallbackAddress (often in format "City, State, Country")
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

const locateUser = () => {
  if (!navigator.geolocation) {
    alert('Geolocation is not supported by your browser.');
    return;
  }

  loadingLocation.value = true;

  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude: lat, longitude: lon } = position.coords;
      await reverseGeocode(lat, lon);
      loadingLocation.value = false;
    },
    (error) => {
      console.error('Geolocation error:', error);
      alert('Unable to retrieve location.');
      loadingLocation.value = false;
    }
  );
};

const updateMap = (lat, lon, addressComponents) => {
  const latLng = [lat, lon];
  mapInstance.value.setView(latLng, 13);

  if (markerRef.value) {
    markerRef.value.setLatLng(latLng);
  } else {
    markerRef.value = L.marker(latLng).addTo(mapInstance.value);
  }

  // Update all location fields
  address.value = addressComponents.fullAddress;
  city.value = addressComponents.city;
  state.value = addressComponents.state;
  country.value = addressComponents.country;
  latitude.value = lat;
  longitude.value = lon;
  
  // Update the search box with full address
  searchQuery.value = addressComponents.fullAddress;

  // Call the parent component's handler if provided
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
          class="w-full p-2 pl-10 pr-12 border rounded-lg"
        />
        <Search class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />

        <!-- Locate Me Button -->
        <button
          @click="locateUser"
          :disabled="loadingLocation"
          class="absolute right-3 top-2.5 h-6 w-6 text-gray-500 hover:text-black"
          title="Use my current location"
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

    <div ref="mapRef" class="w-full h-64 rounded-lg z-0 mb-4" />

    <!-- Location Details Form -->
    <div class="hidden grid-cols-1 md:grid-cols-2 gap-4 mt-4">
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
          class="w-full p-2 border rounded-lg"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
        <input
          type="text"
          v-model="state"
          class="w-full p-2 border rounded-lg"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
        <input
          type="text"
          v-model="country"
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
    
    <div class="mt-4 text-sm text-gray-500">
      Click directly on the map to select a location
    </div>
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