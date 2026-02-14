<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue';
import { Search, MapPin, Check, X } from 'lucide-vue-next'; // Added X for close button
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const props = defineProps({
  onLocationSelect: Function,
});

const mapRef = ref(null);
const mapInstance = ref(null);
const markerRef = ref(null);
// const searchResults = ref([]); // No longer needed with gmp-place-autocomplete-element
// const searchQuery = ref(''); // No longer needed
const isLocating = ref(false);
const addressError = ref('');
const fullAddress = ref('');
const formattedAddress = ref('');
const placeId = ref('');


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

// API Keys - User needs to ensure VITE_GOOGLE_MAPS_API_KEY is in .env
const GOOGLE_MAPS_API_KEY = import.meta.env.VITE_GOOGLE_MAPS_API_KEY;
const STADIA_API_KEY = import.meta.env.VITE_STADIA_API_KEY; // Assuming this is already set
// GOOGLE_MAPS_API_KEY is loaded via script tag for PlaceAutocompleteElement

// New state for popup flow
const showLocationPickerPopup = ref(false); // Main control for the new popup
const currentPopupStep = ref(1); // 1 for details, 2 for map
const isLoadingDetails = ref(false); // For loading indication
const mapKey = ref(0); // Key to force re-render of map container

const isStep1FormValid = computed(() => {
  return tempAddress.value.trim() !== '' &&
         tempCity.value.trim() !== '' &&
         tempState.value.trim() !== '' &&
         tempCountry.value.trim() !== '';
});

const isCityMissing = computed(() => tempCity.value.trim() === '');
const isStateMissing = computed(() => tempState.value.trim() === '');
const isCountryMissing = computed(() => tempCountry.value.trim() === '');
const shouldShowManualFields = computed(() => isCityMissing.value || isStateMissing.value || isCountryMissing.value);

const initializeMap = () => {
  if (mapRef.value && !mapInstance.value) {
    mapInstance.value = L.map(mapRef.value, {
      scrollWheelZoom: true,
      zoomControl: true,
    }).setView([20.5937, 78.9629], 5);

    // Use Stadia Maps tile layer
    L.tileLayer(`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png?api_key=${STADIA_API_KEY}`, {
      attribution: '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 20,
    }).addTo(mapInstance.value);

    mapInstance.value.on('click', async (e) => {
      if (currentPopupStep.value === 2) { // Only allow map click interaction if on map step
        const { lat, lng } = e.latlng;
        await reverseGeocodeAndPrefill(lat, lng); // Modified to prefill for step 1
      }
    });
  }
};

const autocompleteInputRef = ref(null); // Ref for the standard input
let autocompleteInstance = null; // To store the Google Autocomplete instance

onMounted(async () => {
  try {
    if (!window.googleMapsReady) {
      console.error("Google Maps SDK readiness promise (window.googleMapsReady) not found.");
      return;
    }
    await window.googleMapsReady;
    console.log("Google Maps SDK is ready.");

    if (typeof google === 'undefined' || !google.maps || !google.maps.importLibrary) {
      console.error("Google Maps 'google' object or 'importLibrary' is not available.");
      return;
    }
    
    await google.maps.importLibrary("places");
    console.log("Google Maps Places library imported.");

    if (autocompleteInputRef.value) {
      const options = {
        // types: ['geocode'], // You can re-add this if needed, but test without first
        fields: ['address_components', 'geometry', 'name', 'formatted_address', 'place_id'],
      };
      autocompleteInstance = new google.maps.places.Autocomplete(autocompleteInputRef.value, options);

      autocompleteInstance.addListener('place_changed', () => {
        isLoadingDetails.value = true;
        const place = autocompleteInstance.getPlace();

        if (!place.geometry || !place.geometry.location) {
          console.log("Autocomplete's returned place contains no geometry", place);
          isLoadingDetails.value = false;
          // Optionally clear fields or show a message
          window.alert("No details available for input: '" + place.name + "'. Please select a valid location from the suggestions.");
          return;
        }

        latitude.value = parseFloat(place.geometry.location.lat().toFixed(6));
        longitude.value = parseFloat(place.geometry.location.lng().toFixed(6));

        let streetNumber = '', route = '', locality = '', administrative_area_level_1 = '', countryName = '', postal_code = '';
        if (place.address_components) {
          place.address_components.forEach(component => {
            if (component.types.includes('street_number')) streetNumber = component.long_name;
            if (component.types.includes('route')) route = component.long_name;
            if (component.types.includes('locality')) locality = component.long_name;
            if (component.types.includes('postal_town') && !locality) locality = component.long_name;
            if (component.types.includes('administrative_area_level_1')) administrative_area_level_1 = component.long_name;
            if (component.types.includes('country')) countryName = component.long_name;
            if (component.types.includes('postal_code')) postal_code = component.long_name;
          });
        }

        tempAddress.value = `${streetNumber} ${route}`.trim() || place.formatted_address || place.name || '';
        tempCity.value = locality || '';
        tempState.value = administrative_area_level_1 || '';
        tempCountry.value = countryName || '';

        address.value = tempAddress.value;
        city.value = tempCity.value;
        state.value = tempState.value;
        country.value = tempCountry.value;
        formattedAddress.value = place.formatted_address || '';
        placeId.value = place.place_id || '';
        fullAddress.value = formattedAddress.value || `${tempAddress.value}, ${tempCity.value}, ${tempState.value}, ${tempCountry.value}`;

        currentPopupStep.value = 1;
        showLocationPickerPopup.value = true;
        if (mapInstance.value) {
          updateMapMarker(parseFloat(latitude.value), parseFloat(longitude.value));
        }
        isLoadingDetails.value = false;
      });
    } else {
      console.error("autocompleteInputRef not found on mount.");
    }
  } catch (error) {
    console.error("Error in onMounted (Google Maps Autocomplete setup):", error);
  }
});

const refreshMapView = () => {
    if (mapRef.value) {
        if (!mapInstance.value) {
            initializeMap();
        }
        if (mapInstance.value) {
            mapInstance.value.invalidateSize();
            const currentLat = parseFloat(latitude.value);
            const currentLng = parseFloat(longitude.value);

            if (!isNaN(currentLat) && !isNaN(currentLng)) {
                updateMapMarker(currentLat, currentLng); // Updates marker only
                mapInstance.value.setView([currentLat, currentLng], 13);
            } else {
                updateMapMarker(null, null); // Clear marker if coords are invalid
                mapInstance.value.setView([20.5937, 78.9629], 5); // Default view
            }
        }
    }
};

watch(showLocationPickerPopup, (newValue) => {
  if (newValue && currentPopupStep.value === 2) {
    setTimeout(refreshMapView, 100);
  }
  // Optional: clean up map when popup closes to prevent issues if re-opened
  // else if (!newValue && mapInstance.value) {
    // mapInstance.value.remove();
    // mapInstance.value = null;
  // }
});

watch(currentPopupStep, (newStep) => {
    if (showLocationPickerPopup.value && newStep === 2) {
        setTimeout(refreshMapView, 100);
    }
});


onUnmounted(() => {
  if (mapInstance.value) {
    mapInstance.value.remove();
    mapInstance.value = null;
  }
});

const extractAddressParts = (components = []) => {
  const getPart = (type) => components.find(component => component.types.includes(type))?.long_name || '';
  const streetNumber = getPart('street_number');
  const route = getPart('route');
  const city = getPart('locality') || getPart('postal_town') || getPart('administrative_area_level_2');
  const state = getPart('administrative_area_level_1');
  const countryName = getPart('country');

  return {
    streetNumber,
    route,
    city,
    state,
    countryName,
  };
};

// handleBlur, handleSearch, and handleGoogleLocationSelect are no longer needed
// as gmp-place-autocomplete-element handles its own input and selection.

// Renamed original reverseGeocode to avoid confusion, now specifically for map clicks / geolocate
const reverseGeocodeAndPrefill = async (lat, lng) => {
  isLoadingDetails.value = true;
  latitude.value = parseFloat(lat.toFixed(6));
  longitude.value = parseFloat(lng.toFixed(6));

  try {
    if (typeof google === 'undefined' || !google.maps || !google.maps.Geocoder) {
      throw new Error('Google Maps Geocoder is not available');
    }

    const geocoder = new google.maps.Geocoder();
    const result = await geocoder.geocode({
      location: { lat: latitude.value, lng: longitude.value },
    });

    const geocodeResult = result?.results?.[0];
    if (!geocodeResult) {
      throw new Error('No reverse geocoding results');
    }

    const parts = extractAddressParts(geocodeResult.address_components || []);
    const fallbackAddress = geocodeResult.formatted_address || '';
    tempAddress.value = `${parts.streetNumber} ${parts.route}`.trim() || fallbackAddress;
    tempCity.value = parts.city || '';
    tempState.value = parts.state || '';
    tempCountry.value = parts.countryName || '';

    address.value = tempAddress.value;
    city.value = tempCity.value;
    state.value = tempState.value;
    country.value = tempCountry.value;
    formattedAddress.value = geocodeResult.formatted_address || '';
    placeId.value = geocodeResult.place_id || '';
    fullAddress.value = formattedAddress.value || `${tempAddress.value}, ${tempCity.value}, ${tempState.value}, ${tempCountry.value}`;

    if (!showLocationPickerPopup.value) {
      currentPopupStep.value = 1;
      showLocationPickerPopup.value = true;
    }
    updateMapMarker(lat, lng);
  } catch (error) {
    console.error('Reverse geocoding error:', error);
    if (!showLocationPickerPopup.value) {
      currentPopupStep.value = 1;
      showLocationPickerPopup.value = true;
    }
    updateMapMarker(lat, lng);
  } finally {
    isLoadingDetails.value = false;
  }
};

const openLocationDetailsPopupWithCoords = (lat, lng, initialSearchText = '') => {
  latitude.value = lat;
  longitude.value = lng;
  // searchQuery.value = initialSearchText; // searchQuery no longer exists

  // Reset temp fields
  tempAddress.value = '';
  tempCity.value = '';
  tempState.value = '';
  tempCountry.value = '';
  
  currentPopupStep.value = 1;
  showLocationPickerPopup.value = true;

  if (lat && lng && mapInstance.value) {
    updateMapMarker(lat, lng);
  }
};


const updateMapMarker = (lat, lon) => {
  const validCoords = typeof lat === 'number' && typeof lon === 'number' && !isNaN(lat) && !isNaN(lon);

  if (!mapInstance.value) {
    // If map isn't initialized, store valid coords for when it is.
    if (validCoords) {
        latitude.value = lat;
        longitude.value = lon;
    }
    // If coords are invalid or map not ready, nothing more to do here.
    return;
  }

  if (validCoords) {
    const latLng = [lat, lon];
    if (markerRef.value) {
      markerRef.value.setLatLng(latLng);
    } else {
      markerRef.value = L.marker(latLng, {
        draggable: true,
      }).addTo(mapInstance.value);

      markerRef.value.on('dragend', async (e) => {
        const { lat: newLat, lng: newLng } = e.target.getLatLng();
        await reverseGeocodeAndPrefill(newLat, newLng);
      });
    }
  } else {
    // Coords are invalid, remove marker if it exists
    if (markerRef.value) {
      mapInstance.value.removeLayer(markerRef.value);
      markerRef.value = null;
    }
  }
};


// Called when "Continue" is clicked on the details form (step 1)
const proceedToMapStep = () => {
  addressError.value = '';
  if (!isStep1FormValid.value) {
    addressError.value = 'Parking Address, City, State, and Country are all required to proceed.';
    return;
  }

  // Clean up existing map instance before changing key, to ensure fresh init
  if (mapInstance.value) {
    mapInstance.value.remove();
    mapInstance.value = null;
    markerRef.value = null; // Also clear the marker ref
  }
  
  mapKey.value++; // Increment key to force re-render of the map container
  currentPopupStep.value = 2; // Move to map step
  // Map initialization/update is handled by the watcher for currentPopupStep, which calls refreshMapView
};

// Called when "Save" is clicked on the map step (step 2)
const finalSaveLocation = () => {
  addressError.value = '';
  if (!tempAddress.value.trim()) {
    addressError.value = 'Parking address is required for saving.';
    return;
  }

  // Final values are taken from temp fields, potentially updated by map interaction
  address.value = tempAddress.value;
  city.value = tempCity.value;
  state.value = tempState.value;
  country.value = tempCountry.value;
  // latitude and longitude are already updated by map interactions or initial selection

  // Adjust fullAddress computation to handle optional state gracefully
  const addressParts = [tempAddress.value, tempCity.value];
  if (tempState.value) {
      addressParts.push(tempState.value);
  }
  addressParts.push(tempCountry.value);
  fullAddress.value = formattedAddress.value || addressParts.filter(Boolean).join(', ').replace(/, ,/g, ',').replace(/^,|,$/g, '');
  
  showLocationPickerPopup.value = false; // Close the popup
  currentPopupStep.value = 1; // Reset step for next time
  
  if (props.onLocationSelect) {
    props.onLocationSelect({ 
      address: address.value,
      city: city.value,
      state: state.value,
      country: country.value,
      latitude: parseFloat(latitude.value),
      longitude: parseFloat(longitude.value),
      fullAddress: fullAddress.value,
      formattedAddress: formattedAddress.value,
      placeId: placeId.value,
    });
  }
  // searchQuery.value = fullAddress.value; // searchQuery no longer exists
};

const locateMe = () => {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser");
    return;
  }
  
  isLocating.value = true;
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const lat = parseFloat(position.coords.latitude.toFixed(6));
      const lng = parseFloat(position.coords.longitude.toFixed(6));
      isLocating.value = false;
      await reverseGeocodeAndPrefill(lat, lng); // This will open the popup at step 1
    },
    (error) => {
      isLocating.value = false;
      let errorMessage = "Unknown error";
      // ... (error handling as before)
      alert(`Error getting your location: ${errorMessage}`);
      // Optionally open popup for manual entry if geolocation fails
      openLocationDetailsPopupWithCoords(null, null, "Could not get current location");
    },
    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
  );
};

// This function is no longer directly used for saving, finalSaveLocation is.
// It can be removed or repurposed if there's a direct update path from main form.
// const handleManualAddressUpdate = () => { ... };

const openLocationDialog = () => {
    tempAddress.value = '';
    tempCity.value = '';
    tempState.value = '';
    tempCountry.value = '';
    latitude.value = '';
    longitude.value = '';
    formattedAddress.value = '';
    placeId.value = '';
    fullAddress.value = '';
    address.value = '';
    city.value = '';
    state.value = '';
    country.value = '';
    currentPopupStep.value = 1;
    showLocationPickerPopup.value = true;
};

defineExpose({ openLocationDialog });

</script>

<template>
  <div class="w-full p-4 border rounded-lg shadow-md">
    <!-- Standard Input for Google Places Autocomplete -->
    <div class="mb-4">
      <label for="locationSearchInput" class="block text-sm font-medium text-gray-700 mb-1">Search for a location</label>
      <input 
        id="locationSearchInput"
        type="text"
        ref="autocompleteInputRef"
        class="w-full p-2 border border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        placeholder="Enter an address, city, or landmark"
      />
      <p v-if="isLoadingDetails" class="text-sm text-gray-500 mt-1">Loading location details...</p>
    </div>
    
    <!-- New Location Picker Popup -->
    <div v-if="showLocationPickerPopup" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-[1000]">
      <div class="bg-white rounded-lg p-6 w-full max-w-lg shadow-xl">
        <!-- Step 1: Location Details Form -->
        <div v-if="currentPopupStep === 1">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">Confirm Location Details</h3>
            <button @click="showLocationPickerPopup = false" class="text-gray-500 hover:text-gray-700">
              <X class="h-6 w-6" />
            </button>
          </div>
          <p class="text-sm text-gray-600 mb-4">
            Please verify the auto-filled details below or provide them. You can fine-tune the exact spot on the map in the next step.
          </p>
          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Parking Address / Street</label>
              <input
                v-if="!tempAddress.trim()"
                type="text"
                v-model="tempAddress"
                class="w-full p-2 border rounded-lg"
                placeholder="E.g., 123 Main St"
              />
              <input
                v-else
                type="text"
                :value="tempAddress"
                class="w-full p-2 border rounded-lg bg-gray-50"
                readonly
              />
              <p v-if="addressError" class="text-red-500 text-xs mt-1">{{ addressError }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
              <input
                v-if="isCityMissing"
                type="text"
                v-model="tempCity"
                class="w-full p-2 border rounded-lg"
                placeholder="City"
              />
              <input
                v-else
                type="text"
                :value="tempCity"
                class="w-full p-2 border rounded-lg bg-gray-50"
                readonly
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
              <input
                v-if="isStateMissing"
                type="text"
                v-model="tempState"
                class="w-full p-2 border rounded-lg"
                placeholder="State / Province"
              />
              <input
                v-else
                type="text"
                :value="tempState"
                class="w-full p-2 border rounded-lg bg-gray-50"
                readonly
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
              <input
                v-if="isCountryMissing"
                type="text"
                v-model="tempCountry"
                class="w-full p-2 border rounded-lg"
                placeholder="Country"
              />
              <input
                v-else
                type="text"
                :value="tempCountry"
                class="w-full p-2 border rounded-lg bg-gray-50"
                readonly
              />
            </div>
            <p v-if="shouldShowManualFields" class="text-xs text-gray-500">
              Some details were missing from Google. Please fill the empty fields above.
            </p>
          </div>
          <div class="mt-6 flex justify-end space-x-3">
            <button @click="showLocationPickerPopup = false" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-100">Cancel</button>
            <button 
              @click="proceedToMapStep" 
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
              :disabled="!isStep1FormValid"
              :class="{ 'opacity-50 cursor-not-allowed': !isStep1FormValid }"
            >Continue to Map</button>
          </div>
        </div>

        <!-- Step 2: Map Interaction -->
        <div v-if="currentPopupStep === 2">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold">Pinpoint Location on Map</h3>
             <button @click="showLocationPickerPopup = false; currentPopupStep = 1;" class="text-gray-500 hover:text-gray-700">
                <X class="h-6 w-6" />
            </button>
          </div>
          <p class="text-sm text-gray-600 mb-1">Drag the marker to the exact vehicle location.</p>
          <p class="text-xs text-gray-500 mb-3">Map click also updates the marker.</p>
          
          <div ref="mapRef" :key="mapKey" class="w-full h-72 rounded-lg z-0 mb-4 border"></div>
           <div class="text-xs text-gray-500 mb-3">
                Current Marker: 
                Lat: {{ typeof latitude === 'number' ? latitude.toFixed(6) : (latitude || 'N/A') }}, 
                Lng: {{ typeof longitude === 'number' ? longitude.toFixed(6) : (longitude || 'N/A') }}
            </div>

          <div class="mt-6 flex justify-between items-center">
            <button @click="currentPopupStep = 1" class="px-4 py-2 border rounded-lg text-gray-700 hover:bg-gray-100">Back to Details</button>
            <button @click="finalSaveLocation" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
              <Check class="h-5 w-5 mr-2" />
              Save Location
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Display Final Selected Location (Readonly) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6 border-t pt-4">
      <h3 class="col-span-1 md:col-span-2 text-md font-semibold text-gray-800 mb-2">Selected Location:</h3>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">Full Address</label>
        <input type="text" :value="fullAddress" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">Parking Address</label>
        <input type="text" :value="address" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">City</label>
        <input type="text" :value="city" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">State/Province</label>
        <input type="text" :value="state" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">Country</label>
        <input type="text" :value="country" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">Latitude</label>
        <input type="text" :value="latitude" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
      <div class="form-group">
        <label class="block text-xs font-medium text-gray-600 mb-1">Longitude</label>
        <input type="text" :value="longitude" class="w-full p-2 border rounded-lg bg-gray-50 text-sm" readonly />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Ensure map has a good cursor */
/* The mapRef div itself might not be directly styled for cursor if events are on mapInstance */
.leaflet-container {
  cursor: pointer !important; /* Or crosshair, etc. */
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
