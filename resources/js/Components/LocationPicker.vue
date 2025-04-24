<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Search, MapPin, Check } from 'lucide-vue-next';
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
const isLocating = ref(false);
const isUnknownLocation = ref(false);
const showManualAddressDialog = ref(false);
const addressError = ref('');


// Location detail fields
const address = ref('');
const city = ref('');
const state = ref('');
const country = ref('');
const latitude = ref('');
const longitude = ref('');

// Temp fields for manual entry
const tempAddress = ref('');
const tempCity = ref('');
const tempState = ref('');
const tempCountry = ref('');

const STADIA_API_KEY = import.meta.env.VITE_STADIA_API_KEY;

onMounted(() => {
  if (!mapInstance.value && mapRef.value) {
    mapInstance.value = L.map(mapRef.value, {
      scrollWheelZoom: true,
      zoomControl: true,
    }).setView([20.5937, 78.9629], 5);

    L.tileLayer(`https://tiles.stadiamaps.com/tiles/outdoors/{z}/{x}/{y}{r}.png`, {
      attribution: '© <a href="https://stadiamaps.com/">Stadia Maps</a>, © <a href="https://openmaptiles.org/">OpenMapTiles</a> © <a href="http://openstreetmap.org">OpenStreetMap</a> contributors',
      maxZoom: 20
    }).addTo(mapInstance.value);

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
  searchResults.value = [];
};

const reverseGeocode = async (lat, lng, fallbackAddress = null) => {
  try {
    const response = await fetch(`/api/geocoding/reverse?lat=${lat}&lon=${lng}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    const feature = data.features && data.features[0];
    
    if (!feature) {
      throw new Error('No results found');
    }
    
    const props = feature.properties;
    
    const addressComponents = {
      fullAddress: props.formatted || fallbackAddress || '',
      city: props.locality || props.county || '',
      state: props.region || '',
      country: props.country || '',
    };
    
    // If we don't have a formatted address, show the manual entry dialog
    if (!props.formatted && !fallbackAddress) {
      isUnknownLocation.value = true;
      latitude.value = lat;
      longitude.value = lng;
      showManualAddressDialog.value = true;
      
      // Set temp fields with whatever partial data we have
      tempAddress.value = '';
      tempCity.value = props.locality || props.county || '';
      tempState.value = props.region || '';
      tempCountry.value = props.country || '';
      
      // Update map marker but don't update the main address fields
      updateMapMarker(lat, lng);
      return;
    }
    
    isUnknownLocation.value = false;
    updateMap(lat, lng, addressComponents);
  } catch (error) {
    console.error('Reverse geocoding error:', error);
    
    // Show manual entry dialog when we can't get address details
    isUnknownLocation.value = true;
    latitude.value = lat;
    longitude.value = lng;
    showManualAddressDialog.value = true;
    
    // Try to parse fallback address if available
    if (fallbackAddress) {
      const parts = fallbackAddress.split(',').map(part => part.trim());
      if (parts.length >= 3) {
        tempCity.value = parts[0];
        tempState.value = parts[1];
        tempCountry.value = parts[parts.length - 1];
      }
    }
    
    updateMapMarker(lat, lng);
  }
};

const updateMapMarker = (lat, lon) => {
  const latLng = [lat, lon];
  mapInstance.value.setView(latLng, 13);

  if (markerRef.value) {
    markerRef.value.setLatLng(latLng);
  } else {
    markerRef.value = L.marker(latLng).addTo(mapInstance.value);
  }
};

const updateMap = (lat, lon, addressComponents) => {
  updateMapMarker(lat, lon);

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

const saveManualAddress = () => {

  addressError.value = '';

  // Validate parking address
  if (!tempAddress.value.trim()) {
    addressError.value = 'Parking address is required';
    return;
  }
  address.value = tempAddress.value;
  city.value = tempCity.value;
  state.value = tempState.value;
  country.value = tempCountry.value;
  
  isUnknownLocation.value = false;
  showManualAddressDialog.value = false;
  
  if (props.onLocationSelect) {
    props.onLocationSelect({ 
      address: tempAddress.value,
      city: tempCity.value,
      state: tempState.value,
      country: tempCountry.value,
      latitude: latitude.value,
      longitude: longitude.value
    });
  }
};

const locateMe = () => {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser");
    return;
  }
  
  isLocating.value = true;
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const lat = position.coords.latitude;
      const lng = position.coords.longitude;
      
      try {
        await reverseGeocode(lat, lng);
      } catch (error) {
        console.error("Failed to get address:", error);
        // The reverseGeocode function will handle showing the manual entry dialog
      } finally {
        isLocating.value = false;
      }
    },
    (error) => {
      isLocating.value = false;
      
      let errorMessage = "Unknown error";
      switch(error.code) {
        case error.PERMISSION_DENIED:
          errorMessage = "Location permission denied";
          break;
        case error.POSITION_UNAVAILABLE:
          errorMessage = "Location information unavailable";
          break;
        case error.TIMEOUT:
          errorMessage = "Location request timed out";
          break;
      }
      
      alert(`Error getting your location: ${errorMessage}`);
    },
    {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 0
    }
  );
};

const handleManualAddressUpdate = () => {
  isUnknownLocation.value = false;
  searchQuery.value = address.value;
  
  if (props.onLocationSelect) {
    props.onLocationSelect({ 
      address: address.value,
      city: city.value,
      state: state.value,
      country: country.value,
      latitude: latitude.value,
      longitude: longitude.value
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

    <div class="relative">
      <div ref="mapRef" class="w-full h-64 rounded-lg z-0 mb-4" />
      
      <div class="absolute top-2 right-2 bg-white p-2 rounded shadow-md z-10 text-sm text-gray-600">
        Click on map to select location
      </div>

      <button 
        @click="locateMe"
        class="absolute bottom-6 text-[0.875rem] right-2 bg-[#dd0e5dd2] text-white p-2 rounded-full shadow-md hover:bg-[#dd0e5de8] z-10 flex items-center justify-center"
        :disabled="isLocating"
        title="Use my current location"
      >
      <MapPin class="h-5 w-5" :class="isLocating ? 'text-gray-400' : 'text-white'" />
      <span>Locate Me</span>
        <span v-if="isLocating" class="ml-1 text-sm">Locating...</span>
      </button>
    </div>

    <div 
      v-if="isUnknownLocation && !showManualAddressDialog" 
      class="mb-4 p-3 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded-lg flex items-center"
    >
      <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.908 10.498c.762 1.356-.189 2.903-1.743 2.903H4.092c-1.554 0-2.505-1.547-1.743-2.903L8.257 3.099zM11 14a1 1 0 11-2 0 1 1 0 012 0zm-1-2a1 1 0 01-1-1V7a1 1 0 112 0v4a1 1 0 01-1 1z" clip-rule="evenodd" />
      </svg>
      <span>
        Could not determine exact address. Please verify or enter address details manually.
      </span>
      <button 
        @click="showManualAddressDialog = true"
        class="ml-2 px-2 py-1 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600"
      >
        Enter Address
      </button>
    </div>

    <!-- Manual Address Dialog -->
    <div v-if="showManualAddressDialog" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 w-full max-w-md">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium">Enter Address Details</h3>
          <button @click="showManualAddressDialog = false" class="text-gray-500 hover:text-gray-700">
            <X class="h-5 w-5" />
          </button>
        </div>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Enter Parking Address</label>
            <input
              type="text"
              v-model="tempAddress"
              class="w-full p-2 border rounded-lg"
              placeholder="Street address"
              required
            />
            <p v-if="addressError" class="text-red-500 text-sm mt-1">{{ addressError }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
            <input
              type="text"
              v-model="tempCity"
              class="w-full p-2 border rounded-lg"
              placeholder="City"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
            <input
              type="text"
              v-model="tempState"
              class="w-full p-2 border rounded-lg"
              placeholder="State or Province"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
            <input
              type="text"
              v-model="tempCountry"
              class="w-full p-2 border rounded-lg"
              placeholder="Country"
            />
          </div>
        </div>
        
        <div class="mt-6 flex justify-end space-x-3">
          <!-- <button
            @click="showManualAddressDialog = false"
            class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-100"
          >
            Cancel
          </button> -->
          <button
            @click="saveManualAddress"
            class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 flex items-center"
          >
            <Check class="h-4 w-4 mr-1" />
            Save
          </button>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
        <input
          type="text"
          v-model="address"
          @input="handleManualAddressUpdate"
          class="w-full p-2 border rounded-lg"
          :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
        <input
          type="text"
          v-model="city"
          @input="handleManualAddressUpdate"
          class="w-full p-2 border rounded-lg"
          :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
        <input
          type="text"
          v-model="state"
          @input="handleManualAddressUpdate"
          class="w-full p-2 border rounded-lg"
          :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
        />
      </div>
      <div class="form-group">
        <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
        <input
          type="text"
          v-model="country"
          @input="handleManualAddressUpdate"
          class="w-full p-2 border rounded-lg"
          :class="{ 'border-yellow-400 bg-yellow-50': isUnknownLocation }"
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
#mapRef {
  cursor: pointer;
}

.fixed {
  position: fixed;
}
.inset-0 {
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}
</style>